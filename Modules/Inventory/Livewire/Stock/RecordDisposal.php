<?php

namespace Modules\Inventory\Livewire\Stock;

use App\Models\Branch;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Attributes\On;
use Livewire\Component;
use Modules\Inventory\Entities\InventoryDisposal;
use Modules\Inventory\Entities\InventoryItem;
use Modules\Inventory\Entities\InventoryStock;
use Modules\Inventory\Entities\PurchaseLocation;

class RecordDisposal extends Component
{
    use LivewireAlert;

    public bool $show = false;

    /** Selected item context */
    public ?int  $itemId         = null;
    public ?string $itemName     = null;
    public ?string $itemCode     = null;
    public ?string $itemUnitSymbol = null;
    public float   $availableStock = 0.0;

    /** Form fields */
    public        $quantity     = null;
    public ?int   $branchId     = null;
    public ?string $disposalDate = null;
    public string  $reason      = '';

    protected function rules(): array
    {
        return [
            'itemId'      => 'required|exists:inventory_items,id',
            'quantity'    => ['required', 'numeric', 'gt:0', 'lte:' . max(0, (float) $this->availableStock)],
            'branchId'    => 'required|exists:branches,id',
            'disposalDate' => 'required|date',
            'reason'      => 'nullable|string|max:255',
        ];
    }

    protected function messages(): array
    {
        return [
            'quantity.lte' => __('inventory::modules.disposal.quantityExceedsStock', [
                'available' => number_format((float) $this->availableStock, 2),
                'unit'      => (string) $this->itemUnitSymbol,
            ]),
            'quantity.gt' => __('inventory::modules.disposal.quantityMustBePositive'),
        ];
    }

    public function mount(): void
    {
        $this->disposalDate = Carbon::now()->format('Y-m-d');
    }

    #[On('openRecordDisposal')]
    public function openForItem(int $itemId): void
    {
        $item = InventoryItem::with('unit')
            ->where('restaurant_id', restaurant()->id)
            ->find($itemId);

        if (!$item) {
            return;
        }

        $this->resetExcept(['show']);

        $this->itemId          = $item->id;
        $this->itemName        = $item->name;
        $this->itemCode        = $item->item_code ?: ('#' . $item->id);
        $this->itemUnitSymbol  = optional($item->unit)->symbol;
        $this->availableStock  = (float) InventoryStock::where('inventory_item_id', $item->id)->sum('quantity');

        $this->disposalDate = Carbon::now()->format('Y-m-d');
        $this->branchId     = branch()?->id;

        $this->show = true;
    }

    public function closeModal(): void
    {
        $this->show = false;
        $this->reset([
            'itemId', 'itemName', 'itemCode', 'itemUnitSymbol', 'availableStock',
            'quantity', 'branchId', 'reason',
        ]);
        $this->disposalDate = Carbon::now()->format('Y-m-d');
    }

    public function submit(): void
    {
        $this->validate();

        try {
            DB::transaction(function () {
                $item = InventoryItem::where('restaurant_id', restaurant()->id)
                    ->lockForUpdate()
                    ->findOrFail($this->itemId);

                $location = PurchaseLocation::where('restaurant_id', restaurant()->id)
                    ->where('branch_id', $this->branchId)
                    ->where('type', 'branch')
                    ->where('is_active', true)
                    ->first();

                $stockBefore = (float) InventoryStock::where('inventory_item_id', $item->id)->sum('quantity');

                if ($stockBefore + 1e-6 < (float) $this->quantity) {
                    throw new \Exception(__('inventory::modules.disposal.quantityExceedsStock', [
                        'available' => number_format($stockBefore, 2),
                        'unit'      => (string) $this->itemUnitSymbol,
                    ]));
                }

                $remaining = (float) $this->quantity;

                // Deduct from branch location first, then others.
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
                    if ($remaining <= 0) break;
                    $take = min((float) $stock->quantity, $remaining);
                    if ($take <= 0) continue;
                    $stock->quantity = (float) $stock->quantity - $take;
                    $stock->save();
                    $remaining -= $take;
                }

                if ($remaining > 1e-6) {
                    $others = InventoryStock::where('inventory_item_id', $item->id)
                        ->whereNotIn('id', $stockRows->pluck('id')->all())
                        ->lockForUpdate()
                        ->orderByDesc('quantity')
                        ->get();

                    foreach ($others as $stock) {
                        if ($remaining <= 0) break;
                        $take = min((float) $stock->quantity, $remaining);
                        if ($take <= 0) continue;
                        $stock->quantity = (float) $stock->quantity - $take;
                        $stock->save();
                        $remaining -= $take;
                    }
                }

                $stockAfter = max(0.0, $stockBefore - ((float) $this->quantity - $remaining));

                InventoryDisposal::create([
                    'restaurant_id'     => restaurant()->id,
                    'branch_id'         => $this->branchId,
                    'inventory_item_id' => $item->id,
                    'location_id'       => $location?->id,
                    'quantity'          => $this->quantity,
                    'stock_before'      => $stockBefore,
                    'stock_after'       => $stockAfter,
                    'reason'            => $this->reason ?: null,
                    'disposal_date'     => $this->disposalDate,
                    'added_by'          => user()?->id,
                ]);
            });

            $this->alert('success', __('inventory::modules.disposal.recorded'));
            $this->dispatch('disposalRecorded');
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

    public function render()
    {
        return view('inventory::livewire.stock.record-disposal', [
            'branches' => $this->branches,
        ]);
    }
}
