<?php

namespace App\Livewire\Kot;

use App\Models\Kot;
use App\Models\KotItem;
use App\Models\Printer;
use Livewire\Component;
use App\Models\KotPlace;
use App\Traits\PrinterSetting;
use App\Models\KotCancelReason;
use App\Events\KotUpdated;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class KotCard extends Component
{
    use LivewireAlert;
    public $kot;
    public $confirmDeleteKotModal = false;
    public $kotSettings;
    public $cancelReasons;
    public $cancelReason;
    public $cancelReasonText;
    public $kotPlace;
    public $showAllKitchens = false;

    use PrinterSetting;

    public function mount($kot, $kotSettings, $showAllKitchens = false, $kotPlace = null, $cancelReasons = null)
    {
        $this->kot = $kot;
        $this->kotSettings = $kotSettings;
        $this->showAllKitchens = $showAllKitchens;
        $this->kotPlace = $kotPlace;
        $this->cancelReasons = $cancelReasons;
    }

    public function changeKotStatus($status)
    {
        $kot = Kot::with(['order', 'items'])->find($this->kot->id);
        
        if (!$kot) {
            return;
        }

        // Server-side guard: if ALL items are claimed by other kitchens, reject
        $currentKitchenId = $this->kotPlace?->id;
        if ($currentKitchenId) {
            $allClaimedByOthers = $kot->items->count() > 0 && $kot->items->every(function ($item) use ($currentKitchenId) {
                return $item->is_multi_kitchen && $item->claimed_by_kitchen_id && $item->claimed_by_kitchen_id != $currentKitchenId;
            });
            if ($allClaimedByOthers) {
                $this->dispatch('refreshKots');
                return;
            }
        }

        $previousStatus = $kot->status;

        // When a kitchen starts preparing, claim multi-kitchen items for THIS kitchen
        if ($status === 'in_kitchen' && $currentKitchenId) {
            $this->claimMultiKitchenItems($kot, $currentKitchenId);
        }

        // Map the target KOT status to the item-level status
        $itemStatus = match ($status) {
            'in_kitchen' => 'cooking',
            'food_ready' => 'ready',
            'served' => 'served',
            'pending_confirmation' => 'pending',
            default => null,
        };

        // Update only items owned by this kitchen (or unclaimed)
        if ($itemStatus) {
            KotItem::where('kot_id', $kot->id)
                ->where(function ($q) use ($currentKitchenId) {
                    $q->whereNull('claimed_by_kitchen_id')
                      ->orWhere('claimed_by_kitchen_id', $currentKitchenId);
                })
                ->update(['status' => $itemStatus]);
        }

        // Recalculate KOT status from ALL items' aggregate state
        $newKotStatus = $this->recalculateKotStatus($kot);

        // Sync Order status
        $this->syncOrderStatus($kot->order, $newKotStatus);

        if ($newKotStatus === 'food_ready' && $previousStatus !== 'food_ready') {
            $this->dispatch('playFoodReadySound');
        }

        $this->dispatch('refreshKots');
        $this->dispatch('refreshOrders');
    }

    public function changeKotItemStatus($itemId, $status)
    {
        $kotItem = KotItem::find($itemId);

        // Server-side guard: reject status change if item is claimed by another kitchen
        if ($kotItem->is_multi_kitchen && $kotItem->isClaimed()) {
            $currentKitchenId = $this->kotPlace?->id;
            if ($currentKitchenId && $kotItem->claimed_by_kitchen_id != $currentKitchenId) {
                $this->dispatch('refreshKots');
                return;
            }
        }

        // When a chef starts cooking a multi-kitchen item, claim it for THIS kitchen
        if ($status === 'cooking' && $kotItem->is_multi_kitchen && !$kotItem->isClaimed()) {
            $currentKitchenId = $this->kotPlace?->id;
            if ($currentKitchenId) {
                $kotItem->claimForKitchen($currentKitchenId);
            }
        }

        $kotItem->status = $status;
        $kotItem->save();

        $kot = Kot::with(['order', 'items'])->find($this->kot->id);
        $previousKotStatus = $kot->status;

        // Recalculate KOT status from ALL items' aggregate state
        $newKotStatus = $this->recalculateKotStatus($kot);

        // Sync Order status if KOT status changed
        if ($newKotStatus && $newKotStatus !== $previousKotStatus) {
            $this->syncOrderStatus($kot->order, $newKotStatus);

            if ($newKotStatus === 'food_ready') {
                $this->dispatch('playFoodReadySound');
            }
        }

        $this->dispatch('refreshKots');
        $this->dispatch('refreshOrders');
    }

    public function deleteKot($id)
    {
        // Validate that a cancel reason is provided
        if (!$this->cancelReason && !$this->cancelReasonText) {
            $this->alert('error', __('modules.settings.cancelReasonRequired'), [
                'toast' => true,
                'position' => 'top-end',
                'showCancelButton' => false,
                'cancelButtonText' => __('app.close'),
            ]);
            return;
        }

        $kot = Kot::with('items')->findOrFail($id);

        // Guard: prevent cancelling a KOT whose items are all claimed by another kitchen
        $currentKitchenId = $this->kotPlace?->id;
        if ($currentKitchenId) {
            $allClaimedByOthers = $kot->items->count() > 0 && $kot->items->every(function ($item) use ($currentKitchenId) {
                return $item->is_multi_kitchen && $item->claimed_by_kitchen_id && $item->claimed_by_kitchen_id != $currentKitchenId;
            });
            if ($allClaimedByOthers) {
                $this->confirmDeleteKotModal = false;
                $this->dispatch('refreshKots');
                return;
            }
        }

        $order = $kot->order;
        $kotCounts = $order->kot->count();

        // Update cancel reason info
        $kot->cancel_reason_id = $this->cancelReason;
        $kot->cancel_reason_text = $this->cancelReasonText;
        $kot->status = 'cancelled';
        $kot->save();

        // If this is the only KOT in the order, cancel the order
        if ($kotCounts === 1) {
            $order->status = 'canceled';
            $order->save();

            if ($order->table) {
                $order->table->update(['available_status' => 'available']);
            }
        }

        // Optional: soft delete kot or destroy it
        // Kot::destroy($id); // if using force delete

        $this->confirmDeleteKotModal = false;

        $this->dispatch('refreshKots');
    }

    public function printKot($kot)
    {
        // First save the image, then print
        $this->saveKotImageAndPrint($kot);
    }

    public function saveKotImageAndPrint($kot)
    {
        // First, trigger the image saving process
        $this->dispatch('saveKotImage', kotId: $kot);

        // Then proceed with the original print logic
        $this->executePrintKot($kot);
    }

    public function executePrintKot($kotId)
    {
        $kot = Kot::with(['items.menuItem', 'kotPlace.printerSetting'])->find($kotId);
        if (!$kot) return;

        // Use active kitchen view place ID if available, or fall back to KOT place ID
        $kotPlaceId = $this->kotPlace?->id ?? $kot->kitchen_place_id;
        if (!$kotPlaceId) {
            $firstItem = $kot->items->first();
            $kotPlaceId = $firstItem?->menuItem?->kot_place_id;
        }

        $kotPlace = $kotPlaceId ? KotPlace::with('printerSetting')->find($kotPlaceId) : null;
        $printerSetting = $kotPlace?->printerSetting;

        if (!$printerSetting || $printerSetting->is_active == 0) {
            $printerSetting = Printer::where('is_default', true)->first();
        }

        if ($printerSetting && $printerSetting->printing_choice === 'directPrint') {
            try {
                \App\Services\EscPosPrinterService::printKotDirect($kot, $printerSetting);
                $this->alert('success', 'KOT print sent directly to ' . ($printerSetting->name ?? 'kitchen printer'), [
                    'toast' => true,
                    'position' => 'top-end',
                    'showCancelButton' => false,
                    'cancelButtonText' => __('app.close')
                ]);
            } catch (\Throwable $e) {
                $this->alert('error', __('messages.printerNotConnected') . ': ' . $e->getMessage(), [
                    'toast' => true,
                    'position' => 'top-end',
                    'showCancelButton' => false,
                    'cancelButtonText' => __('app.close')
                ]);
            }
            return;
        }

        // Fallback for browserPopupPrint
        $url = route('kot.print', [$kot->id, $kotPlace?->id]);
        $this->dispatch('print_location', $url);
    }

    /**
     * Recalculate KOT status from all items' aggregate state.
     * Returns the new KOT status string.
     */
    protected function recalculateKotStatus(Kot $kot): string
    {
        $items = $kot->items()->get();
        $total = $items->count();

        if ($total === 0) {
            return $kot->status;
        }

        $pendingCount = $items->filter(fn($i) => in_array($i->status, ['pending', null]))->count();
        $cookingCount = $items->where('status', 'cooking')->count();
        $readyCount = $items->where('status', 'ready')->count();
        $servedCount = $items->where('status', 'served')->count();

        if ($servedCount === $total) {
            $newStatus = 'served';
        } elseif ($readyCount === $total) {
            $newStatus = 'food_ready';
        } elseif ($pendingCount === $total) {
            $newStatus = 'pending_confirmation';
        } elseif ($cookingCount > 0 || $readyCount > 0) {
            // Any mix of cooking/ready (with some pending) means still in kitchen
            $newStatus = 'in_kitchen';
        } else {
            $newStatus = $kot->status;
        }

        if ($newStatus !== $kot->status) {
            $kot->status = $newStatus;
            $kot->save();
        }

        return $newStatus;
    }

    /**
     * Sync Order status based on KOT status, checking ALL KOTs in the order.
     */
    protected function syncOrderStatus($order, string $kotStatus): void
    {
        if (!$order) {
            return;
        }

        if ($kotStatus === 'in_kitchen' && in_array($order->order_status?->value, ['placed', 'confirmed'])) {
            $order->order_status = \App\Enums\OrderStatus::PREPARING;
            $order->save();
        } elseif ($kotStatus === 'food_ready') {
            // Only mark order FOOD_READY if ALL KOTs are food_ready, served, or cancelled
            $allReady = $order->kot()
                ->whereNotIn('status', ['food_ready', 'served', 'cancelled'])
                ->doesntExist();

            if ($allReady && in_array($order->order_status?->value, ['preparing', 'placed', 'confirmed'])) {
                $order->order_status = \App\Enums\OrderStatus::FOOD_READY;
                $order->save();
            }
        } elseif ($kotStatus === 'served') {
            // Only mark order SERVED if ALL KOTs are served or cancelled
            $allServed = $order->kot()
                ->whereNotIn('status', ['served', 'cancelled'])
                ->doesntExist();

            if ($allServed && $order->order_status?->value !== 'served') {
                $order->order_status = \App\Enums\OrderStatus::SERVED;
                $order->save();
            }
        }
    }

    /**
     * Claim multi-kitchen items in this KOT for the acting kitchen.
     * Only claims items that are assigned to this kitchen via pivot.
     */
    protected function claimMultiKitchenItems(Kot $kot, int $kitchenId): void
    {
        if (!$kitchenId) {
            return;
        }

        $multiKitchenItems = KotItem::where('kot_id', $kot->id)
            ->where('is_multi_kitchen', true)
            ->whereNull('claimed_by_kitchen_id')
            ->whereHas('menuItem.kotPlaces', function ($q) use ($kitchenId) {
                $q->where('kot_places.id', $kitchenId);
            })
            ->get();

        foreach ($multiKitchenItems as $kotItem) {
            $kotItem->claimForKitchen($kitchenId);
        }
    }

    public function render()
    {
        return view('livewire.kot.kot-card');
    }
}
