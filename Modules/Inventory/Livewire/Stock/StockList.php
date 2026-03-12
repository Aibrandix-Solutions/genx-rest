<?php

namespace Modules\Inventory\Livewire\Stock;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;
use Modules\Inventory\Entities\InventoryItem;
use Modules\Inventory\Entities\InventoryItemCategory;
use Modules\Inventory\Entities\PurchaseLocation;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Inventory\Exports\StockExport;

class StockList extends Component
{
    use WithPagination;

    public $showAddStockEntry = false;
    public $search = '';
    public $category = '';
    public $stockStatus = '';
    public $locationFilter = 'all';
    public $perPage = 20;

    protected $queryString = [
        'search' => ['except' => ''],
        'category' => ['except' => ''],
        'stockStatus' => ['except' => ''],
        'locationFilter' => ['except' => 'all'],
    ];

    #[On('hideAddStockEntryModal')]
    public function hideAddStockEntryModal()
    {
        $this->showAddStockEntry = false;
    }

    #[On('stockUpdated')]
    public function refreshStock()
    {
        // Will automatically refresh due to Livewire's reactive nature
    }

    public function mount(): void
    {
        // Default to restaurant-wide view
        $this->branchFilter = 'all';
        $this->locationFilter = 'all';
    }

    public function getStockStatistics()
    {
        // Get items with stock filtered by location
        $query = InventoryItem::query()
            ->with(['stocks' => function($q) {
                if ($this->locationFilter !== 'all') {
                    $q->where('location_id', $this->locationFilter);
                }
            }]);
        
        $items = $query->get();

        $stats = [
            'available_items' => 0,
            'low_stock' => 0,
            'out_of_stock' => 0,
            'total_cost' => 0
        ];

        foreach ($items as $item) {
            // Calculate stock for the filtered location/branch
            $locationStock = $item->stocks->sum('quantity');
            
            if ($locationStock <= 0) {
                $stats['out_of_stock']++;
            } elseif ($locationStock <= $item->threshold_quantity) {
                $stats['low_stock']++;
            } else {
                $stats['available_items']++;
            }
            $stats['total_cost'] += $item->unit_purchase_price * $locationStock;
        }

        return $stats;
    }

    public function getStockItems()
    {
        // Set MySQL to non-strict mode for this query
        DB::statement("SET SESSION sql_mode=''");

        // Build the base query
        $query = InventoryItem::select('inventory_items.*')
            ->with(['category', 'unit', 'stocks' => function($q) {
                if ($this->locationFilter !== 'all') {
                    $q->where('location_id', $this->locationFilter);
                }
            }, 'stocks.location', 'stocks.branch']);

        // LEFT JOIN with inventory_stocks for aggregation
        $query->leftJoin('inventory_stocks', function($join) {
            $join->on('inventory_items.id', '=', 'inventory_stocks.inventory_item_id');
            
            // Apply location filter to join
            if ($this->locationFilter !== 'all') {
                $join->where('inventory_stocks.location_id', '=', $this->locationFilter);
            }
        });

        // Select aggregated quantity using a different column name to avoid accessor conflict
        $query->selectRaw('COALESCE(SUM(inventory_stocks.quantity), 0) as filtered_stock')
            ->selectRaw('COALESCE(SUM(inventory_stocks.quantity * inventory_items.unit_purchase_price), 0) as total_cost_value');

        // Group by item ID
        $query->groupBy('inventory_items.id');

        // Apply search filter
        if ($this->search) {
            $query->where('inventory_items.name', 'like', '%' . $this->search . '%');
        }

        // Apply category filter
        if ($this->category) {
            $query->where('inventory_items.inventory_item_category_id', $this->category);
        }

        // Apply stock status filter
        if ($this->stockStatus) {
            switch ($this->stockStatus) {
                case 'in_stock':
                    $query->havingRaw('filtered_stock > inventory_items.threshold_quantity');
                    break;
                case 'low_stock':
                    $query->havingRaw('filtered_stock > 0 AND filtered_stock <= inventory_items.threshold_quantity');
                    break;
                case 'out_of_stock':
                    $query->havingRaw('filtered_stock <= 0');
                    break;
            }
        }

        $result = $query->paginate($this->perPage);

        // Reset SQL mode back to default after query execution
        DB::statement("SET SESSION sql_mode=(SELECT @@global.sql_mode)");

        return $result;
    }

    public function getCategories()
    {
        return InventoryItemCategory::all();
    }

    public function getLocations()
    {
        return PurchaseLocation::where('restaurant_id', restaurant()->id)
            ->where('is_active', true)
            ->orderBy('type')
            ->orderBy('name')
            ->get();
    }

    public function clearFilters()
    {
        $this->reset(['search', 'category', 'stockStatus', 'locationFilter']);
        $this->locationFilter = 'all';
        $this->resetPage();
    }



    public function export()
    {
        return Excel::download(new StockExport($this->search, $this->category, $this->stockStatus, $this->locationFilter), 'stock-inventory.xlsx');
    }

    public function render()
    {
        return view('inventory::livewire.stock.stock-list', [
            'stats' => $this->getStockStatistics(),
            'stockItems' => $this->getStockItems(),
            'categories' => $this->getCategories(),
            'locations' => $this->getLocations(),
        ]);
    }
}
