<?php

namespace Modules\Inventory\Livewire\Stock;

use App\Models\Branch;
use App\Models\MenuItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Attributes\On;
use Livewire\Component;
use Modules\Inventory\Entities\InventoryConsumption;
use Modules\Inventory\Entities\InventoryItem;
use Modules\Inventory\Entities\InventoryStock;
use Modules\Inventory\Entities\PurchaseLocation;

class RecordConsumption extends Component
{
    use LivewireAlert;

    public bool $show = false;

    /** Selected item context */
    public ?int $itemId = null;
    public ?string $itemName = null;
    public ?string $itemCode = null;
    public ?string $itemUnitSymbol = null;
    public float $availableStock = 0.0;

    /** Form fields */
    public $quantity = null;
    public ?int $branchId = null;
    public ?string $consumptionDate = null;

    /** Menus Used (multi-select) */
    public array $selectedMenuItemIds = [];
    public string $menuSearch = '';

    /** Note */
    public string $note = '';

    protected function rules(): array
    {
        return [
            'itemId' => 'required|exists:inventory_items,id',
            'quantity' => ['required', 'numeric', 'gt:0', 'lte:' . max(0, (float) $this->availableStock)],
            'branchId' => 'required|exists:branches,id',
            'consumptionDate' => 'required|date',
            'note' => 'nullable|string|max:255',
            'selectedMenuItemIds' => 'array',
            'selectedMenuItemIds.*' => 'integer|exists:menu_items,id',
        ];
    }

    protected function messages(): array
    {
        return [
            'quantity.lte' => __('inventory::modules.consumption.quantityExceedsStock', [
                'available' => number_format((float) $this->availableStock, 2),
                'unit' => (string) $this->itemUnitSymbol,
            ]),
            'quantity.gt' => __('inventory::modules.consumption.quantityMustBePositive'),
        ];
    }

    public function mount(): void
    {
        $this->consumptionDate = Carbon::now()->format('Y-m-d');
    }

    /**
     * Open modal for a specific inventory item.
     */
    #[On('openRecordConsumption')]
    public function openForItem(int $itemId): void
    {
        $item = InventoryItem::with('unit')
            ->where('restaurant_id', restaurant()->id)
            ->find($itemId);

        if (!$item) {
            return;
        }

        $this->resetExcept(['show']);

        $this->itemId = $item->id;
        $this->itemName = $item->name;
        $this->itemCode = $item->item_code ?: ('#' . $item->id);
        $this->itemUnitSymbol = optional($item->unit)->symbol;
        $this->availableStock = (float) InventoryStock::where('inventory_item_id', $item->id)->sum('quantity');

        $this->consumptionDate = Carbon::now()->format('Y-m-d');
        $this->branchId = branch()?->id;

        $this->show = true;
    }

    public function closeModal(): void
    {
        $this->show = false;
        $this->reset([
            'itemId', 'itemName', 'itemCode', 'itemUnitSymbol', 'availableStock',
            'quantity', 'branchId', 'note', 'selectedMenuItemIds', 'menuSearch',
        ]);
        $this->consumptionDate = Carbon::now()->format('Y-m-d');
    }

    public function toggleMenuItem(int $menuItemId): void
    {
        if (in_array($menuItemId, $this->selectedMenuItemIds, true)) {
            $this->selectedMenuItemIds = array_values(array_filter(
                $this->selectedMenuItemIds,
                fn ($id) => (int) $id !== $menuItemId
            ));
        } else {
            $this->selectedMenuItemIds[] = $menuItemId;
        }
    }

    public function removeMenuItem(int $menuItemId): void
    {
        $this->selectedMenuItemIds = array_values(array_filter(
            $this->selectedMenuItemIds,
            fn ($id) => (int) $id !== $menuItemId
        ));
    }

    public function submit(): void
    {
        $this->validate();

        try {
            DB::transaction(function () {
                // Lock the item & stock rows for safety.
                $item = InventoryItem::where('restaurant_id', restaurant()->id)
                    ->lockForUpdate()
                    ->findOrFail($this->itemId);

                // Resolve a location for this branch (so stock deduction is location-aware).
                $location = PurchaseLocation::where('restaurant_id', restaurant()->id)
                    ->where('branch_id', $this->branchId)
                    ->where('type', 'branch')
                    ->where('is_active', true)
                    ->first();

                // Snapshot the total available stock BEFORE deducting.
                $stockBefore = (float) InventoryStock::where('inventory_item_id', $item->id)->sum('quantity');

                if ($stockBefore + 1e-6 < (float) $this->quantity) {
                    throw new \Exception(__('inventory::modules.consumption.quantityExceedsStock', [
                        'available' => number_format($stockBefore, 2),
                        'unit' => (string) $this->itemUnitSymbol,
                    ]));
                }

                $remaining = (float) $this->quantity;

                // Prefer to deduct from the branch's location bucket first.
                $stockRows = InventoryStock::where('inventory_item_id', $item->id)
                    ->when($location, fn ($q) => $q->where(function ($q) use ($location) {
                        $q->where('location_id', $location->id)
                            ->orWhereNull('location_id');
                    }))
                    ->lockForUpdate()
                    ->orderByRaw('CASE WHEN location_id = ? THEN 0 ELSE 1 END', [$location?->id ?? 0])
                    ->orderByDesc('quantity')
                    ->get();

                foreach ($stockRows as $stock) {
                    if ($remaining <= 0) {
                        break;
                    }
                    $take = min((float) $stock->quantity, $remaining);
                    if ($take <= 0) {
                        continue;
                    }
                    $stock->quantity = (float) $stock->quantity - $take;
                    $stock->save();
                    $remaining -= $take;
                }

                // If we still have remaining, deduct from any other stock rows for this item.
                if ($remaining > 1e-6) {
                    $others = InventoryStock::where('inventory_item_id', $item->id)
                        ->whereNotIn('id', $stockRows->pluck('id')->all())
                        ->lockForUpdate()
                        ->orderByDesc('quantity')
                        ->get();

                    foreach ($others as $stock) {
                        if ($remaining <= 0) {
                            break;
                        }
                        $take = min((float) $stock->quantity, $remaining);
                        if ($take <= 0) {
                            continue;
                        }
                        $stock->quantity = (float) $stock->quantity - $take;
                        $stock->save();
                        $remaining -= $take;
                    }
                }

                // Compute stock AFTER deduction. Use the locked rows' fresh sum so it reflects exactly
                // what we wrote to the DB (not a stale or duplicate read).
                $stockAfter = max(0.0, $stockBefore - ((float) $this->quantity - $remaining));

                // Create the consumption record with snapshots.
                $consumption = InventoryConsumption::create([
                    'restaurant_id' => restaurant()->id,
                    'branch_id' => $this->branchId,
                    'inventory_item_id' => $item->id,
                    'location_id' => $location?->id,
                    'quantity' => $this->quantity,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'consumption_date' => $this->consumptionDate,
                    'note' => $this->note ?: null,
                    'added_by' => user()?->id,
                ]);

                if (!empty($this->selectedMenuItemIds)) {
                    $consumption->menuItems()->sync(array_unique(array_map('intval', $this->selectedMenuItemIds)));
                }
            });

            $this->alert('success', __('inventory::modules.consumption.recorded'));
            $this->dispatch('consumptionRecorded');
            $this->dispatch('stockUpdated');
            $this->closeModal();
        } catch (\Throwable $e) {
            $this->alert('error', $e->getMessage());
        }
    }

    public function getBranchesProperty()
    {
        return Branch::where('restaurant_id', restaurant()->id)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function getMenuItemSearchResultsProperty()
    {
        $query = MenuItem::query()
            ->withoutGlobalScopes()
            ->whereHas('branch', fn ($q) => $q->where('restaurant_id', restaurant()->id));

        if (trim($this->menuSearch) !== '') {
            $term = '%' . trim($this->menuSearch) . '%';
            $query->where('item_name', 'like', $term);
        }

        return $query->orderBy('item_name')->limit(20)->get(['id', 'item_name']);
    }

    public function getSelectedMenuItemsProperty()
    {
        if (empty($this->selectedMenuItemIds)) {
            return collect();
        }

        return MenuItem::query()
            ->withoutGlobalScopes()
            ->whereIn('id', $this->selectedMenuItemIds)
            ->get(['id', 'item_name']);
    }

    public function render()
    {
        return view('inventory::livewire.stock.record-consumption', [
            'branches' => $this->branches,
            'menuSearchResults' => $this->menuItemSearchResults,
            'selectedMenuItems' => $this->selectedMenuItems,
        ]);
    }
}
