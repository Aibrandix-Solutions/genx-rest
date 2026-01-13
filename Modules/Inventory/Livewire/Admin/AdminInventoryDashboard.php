<?php

namespace Modules\Inventory\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\Inventory\Entities\InventoryItem;
use Modules\Inventory\Entities\PurchaseLocation;
use App\Models\Branch;
use App\Scopes\BranchScope;
use Illuminate\Support\Facades\DB;

class AdminInventoryDashboard extends Component
{
    use WithPagination;

    public $search = '';
    public $locationFilter = '';
    public $branchFilter = '';
    public $stockStatus = '';
    public $category = '';
    public $perPage = 20;

    protected $queryString = [
        'search' => ['except' => ''],
        'locationFilter' => ['except' => ''],
        'branchFilter' => ['except' => ''],
        'stockStatus' => ['except' => ''],
        'category' => ['except' => ''],
    ];

    public $locations = [];
    public $branches = [];

    public function mount(): void
    {
        abort_if(!user_can('View Admin Inventory Dashboard'), 403);
        $this->loadFilters();
    }

    public function render()
    {
        return view('inventory::livewire.admin.admin-inventory-dashboard', [
            'items' => $this->getInventoryItems(),
            'stats' => $this->getStats(),
        ]);
    }

    public function getStats(): array
    {
        $query = InventoryItem::withoutGlobalScope(BranchScope::class)
            ->where('inventory_items.restaurant_id', restaurant()->id);

        $totalItems = $query->count();
        $totalStock = DB::table('inventory_stocks')
            ->whereIn('inventory_items.id', InventoryItem::withoutGlobalScope(BranchScope::class)
                ->where('restaurant_id', restaurant()->id)
                ->pluck('id'))
            ->leftJoin('inventory_items', 'inventory_items.id', '=', 'inventory_stocks.inventory_item_id')
            ->sum('inventory_stocks.quantity');

        $totalValue = DB::table('inventory_stocks')
            ->whereIn('inventory_items.id', InventoryItem::withoutGlobalScope(BranchScope::class)
                ->where('restaurant_id', restaurant()->id)
                ->pluck('id'))
            ->leftJoin('inventory_items', 'inventory_items.id', '=', 'inventory_stocks.inventory_item_id')
            ->selectRaw('SUM(inventory_stocks.quantity * inventory_items.unit_purchase_price) as total')
            ->value('total') ?? 0;

        $lowStockCount = 0;
        $outOfStockCount = 0;

        foreach (InventoryItem::withoutGlobalScope(BranchScope::class)
                    ->where('restaurant_id', restaurant()->id)
                    ->get() as $item) {
            $totalQty = $item->stocks->sum('quantity');
            if ($totalQty <= 0) {
                $outOfStockCount++;
            } elseif ($totalQty <= $item->threshold_quantity) {
                $lowStockCount++;
            }
        }

        return [
            'total_items' => $totalItems,
            'total_stock' => $totalStock,
            'total_value' => $totalValue,
            'low_stock_count' => $lowStockCount,
            'out_of_stock_count' => $outOfStockCount,
        ];
    }

    public function getInventoryItems()
    {
        $query = InventoryItem::withoutGlobalScope(BranchScope::class)
            ->where('inventory_items.restaurant_id', restaurant()->id)
            ->select(
                'inventory_items.*',
                DB::raw('SUM(CASE WHEN inventory_stocks.location_id IS NOT NULL THEN inventory_stocks.quantity ELSE 0 END) as total_quantity'),
                DB::raw('SUM(CASE WHEN inventory_stocks.location_id IS NOT NULL THEN inventory_stocks.quantity * inventory_items.unit_purchase_price ELSE 0 END) as total_cost_value')
            );

        // Filter by location if selected
        if ($this->locationFilter) {
            $query->leftJoin('inventory_stocks', 'inventory_items.id', '=', 'inventory_stocks.inventory_item_id')
                ->where('inventory_stocks.location_id', $this->locationFilter);
        } else {
            $query->leftJoin('inventory_stocks', 'inventory_items.id', '=', 'inventory_stocks.inventory_item_id');
        }

        // Filter by branch if selected
        if ($this->branchFilter) {
            $query->where('inventory_items.branch_id', $this->branchFilter);
        }

        // Filter by category
        if ($this->category) {
            $query->where('inventory_items.category_id', $this->category);
        }

        // Filter by stock status
        if ($this->stockStatus) {
            if ($this->stockStatus === 'in-stock') {
                $query->havingRaw('total_quantity > ?', [$this->getBranchItem('threshold_quantity') ?? 0]);
            } elseif ($this->stockStatus === 'low-stock') {
                $query->havingRaw('total_quantity > 0 AND total_quantity <= ?', [$this->getBranchItem('threshold_quantity') ?? 0]);
            } elseif ($this->stockStatus === 'out-of-stock') {
                $query->havingRaw('total_quantity <= 0');
            }
        }

        // Filter by search
        if ($this->search) {
            $query->where('inventory_items.name', 'like', '%' . $this->search . '%');
        }

        return $query->with(['category', 'unit', 'branch', 'stocks.location'])
            ->groupBy('inventory_items.id')
            ->paginate($this->perPage);
    }

    protected function loadFilters(): void
    {
        $this->locations = PurchaseLocation::where('restaurant_id', restaurant()->id)
            ->where('is_active', true)
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        $this->branches = Branch::where('restaurant_id', restaurant()->id)
            ->orderBy('name')
            ->get();
    }

    private function getBranchItem($field)
    {
        return null; // Helper for status filtering
    }

    public function export()
    {
        \Maatwebsite\Excel\Facades\Excel::download(
            new \Modules\Inventory\Exports\StockExport($this->getInventoryItems()),
            'admin-inventory-' . now()->format('Y-m-d-H-i-s') . '.xlsx'
        );
    }
}
