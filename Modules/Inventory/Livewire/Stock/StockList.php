<?php

namespace Modules\Inventory\Livewire\Stock;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;
use Modules\Inventory\Entities\InventoryItem;
use Modules\Inventory\Entities\InventoryItemCategory;
use Modules\Inventory\Entities\InventoryStock;
use Modules\Inventory\Entities\PurchaseLocation;
use Modules\Inventory\Entities\PurchaseOrder;
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

    // Stock by Location modal state
    public $showStockLocationsModal = false;
    public $selectedItem = null;

    // Recent Purchases (within Stock by Location modal)
    public bool $expandedPurchases = false;
    public int $purchasesPerPage = 10;

    /** Fixed number of rows shown in the collapsed (recent) view. */
    public const RECENT_PURCHASES_LIMIT = 5;

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

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategory(): void
    {
        $this->resetPage();
    }

    public function updatingStockStatus(): void
    {
        $this->resetPage();
    }

    public function updatingLocationFilter(): void
    {
        $this->resetPage();
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

        // Apply search filter (name OR item_code)
        if ($this->search !== '') {
            $term = '%' . $this->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('inventory_items.name', 'like', $term)
                  ->orWhere('inventory_items.item_code', 'like', $term);
            });
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

    /**
     * Open the modal that shows the stock breakdown of a single inventory item
     * across all of the restaurant's purchase locations.
     */
    public function viewStockLocations(int $itemId): void
    {
        $this->selectedItem = InventoryItem::with(['unit', 'category'])->find($itemId);

        if (!$this->selectedItem) {
            return;
        }

        // Reset purchase view state every time the modal opens for a fresh item.
        $this->expandedPurchases = false;
        $this->resetPage('purchasesPage');

        $this->showStockLocationsModal = true;
    }

    public function closeStockLocationsModal(): void
    {
        $this->showStockLocationsModal = false;
        $this->selectedItem = null;
        $this->expandedPurchases = false;
    }

    /**
     * Toggle between "recent 5" and the full paginated list of purchases.
     */
    public function toggleExpandedPurchases(): void
    {
        $this->expandedPurchases = !$this->expandedPurchases;
        if ($this->expandedPurchases) {
            $this->resetPage('purchasesPage');
        }
    }

    /**
     * Hide the stock-by-location modal and open the existing
     * Purchase Order detail modal. We resolve the model ourselves and
     * dispatch the same `viewPurchaseOrder` event the Purchases page uses,
     * but bypass the BranchScope so cross-branch purchases (which we
     * legitimately surface in this restaurant-wide popup) still load.
     */
    public function openPurchaseOrder(int $purchaseOrderId): void
    {
        $purchaseOrder = PurchaseOrder::withoutGlobalScopes()
            ->whereHas('branch', fn ($q) => $q->where('restaurant_id', restaurant()->id))
            ->find($purchaseOrderId);

        if (!$purchaseOrder) {
            return;
        }

        $this->showStockLocationsModal = false;
        $this->dispatch('viewPurchaseOrder', purchaseOrder: $purchaseOrder->id);
    }

    /**
     * Build a per-location breakdown for the currently selected item.
     * Includes all active locations even when there is no stock row, so the
     * modal can show a complete picture (zero quantity rows included).
     */
    public function getLocationBreakdownProperty()
    {
        if (!$this->selectedItem) {
            return collect();
        }

        $locations = PurchaseLocation::where('restaurant_id', restaurant()->id)
            ->where('is_active', true)
            ->with('branch')
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        $stocksByLocation = InventoryStock::query()
            ->where('inventory_item_id', $this->selectedItem->id)
            ->selectRaw('location_id, SUM(quantity) as total_quantity')
            ->groupBy('location_id')
            ->pluck('total_quantity', 'location_id');

        return $locations->map(function (PurchaseLocation $location) use ($stocksByLocation) {
            $qty = (float) ($stocksByLocation[$location->id] ?? 0);

            return (object) [
                'id' => $location->id,
                'name' => $location->display_name ?? $location->name,
                'type' => ucfirst((string) $location->type),
                'quantity' => $qty,
                'cost_value' => $qty * (float) $this->selectedItem->unit_purchase_price,
            ];
        });
    }

    /**
     * Purchases that contain the selected item. Bypasses the BranchScope
     * (applied by `HasBranch` on PurchaseOrder) so the popup shows
     * purchases across the whole restaurant, mirroring the cross-branch
     * stock breakdown above.
     *
     * Returns an Eloquent collection (limit 5) when collapsed, or a
     * LengthAwarePaginator when "View All" has been toggled.
     */
    public function getItemPurchasesProperty()
    {
        if (!$this->selectedItem) {
            return collect();
        }

        $itemId = (int) $this->selectedItem->id;
        $restaurantId = (int) restaurant()->id;

        $query = PurchaseOrder::query()
            ->withoutGlobalScopes()
            // Restrict to purchases belonging to this restaurant via branch.
            ->whereHas('branch', fn ($q) => $q->where('restaurant_id', $restaurantId))
            ->whereHas('items', fn ($q) => $q->where('inventory_item_id', $itemId))
            ->with([
                'supplier:id,name',
                'branch:id,name,restaurant_id',
                'location:id,name,type,branch_id',
                'location.branch:id,name',
                // Only the matching line items so we can show qty / unit_price.
                'items' => fn ($q) => $q->where('inventory_item_id', $itemId),
            ])
            ->orderByDesc('order_date')
            ->orderByDesc('id');

        if (!$this->expandedPurchases) {
            return $query->limit(self::RECENT_PURCHASES_LIMIT)->get();
        }

        return $query->paginate($this->purchasesPerPage, ['*'], 'purchasesPage');
    }

    /**
     * Total number of purchases for the selected item (used for the
     * "View All (N)" badge on the toggle button).
     */
    public function getItemPurchasesTotalProperty(): int
    {
        if (!$this->selectedItem) {
            return 0;
        }

        $itemId = (int) $this->selectedItem->id;
        $restaurantId = (int) restaurant()->id;

        return (int) PurchaseOrder::query()
            ->withoutGlobalScopes()
            ->whereHas('branch', fn ($q) => $q->where('restaurant_id', $restaurantId))
            ->whereHas('items', fn ($q) => $q->where('inventory_item_id', $itemId))
            ->count();
    }

    public function render()
    {
        return view('inventory::livewire.stock.stock-list', [
            'stats' => $this->getStockStatistics(),
            'stockItems' => $this->getStockItems(),
            'categories' => $this->getCategories(),
            'locations' => $this->getLocations(),
            'locationBreakdown' => $this->locationBreakdown,
            'itemPurchases' => $this->itemPurchases,
            'itemPurchasesTotal' => $this->itemPurchasesTotal,
        ]);
    }
}
