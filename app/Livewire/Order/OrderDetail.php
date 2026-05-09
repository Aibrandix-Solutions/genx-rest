<?php

namespace App\Livewire\Order;

use App\Models\Tax;
use App\Models\Order;
use App\Models\Table;
use App\Models\Printer;
use Livewire\Component;
use App\Models\MenuItem;
use App\Models\OrderTax;
use App\Models\OrderItem;
use App\Models\OrderCharge;
use Livewire\Attributes\On;
use App\Traits\PrinterSetting;
use App\Models\KotCancelReason;
use App\Models\DeliveryExecutive;
use App\Models\Kot;
use App\Models\KotItem;
use App\Models\User;
use App\Scopes\BranchScope;
use App\Support\KotAdjustmentLogger;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use App\Livewire\Customer\AddCustomer;
use Illuminate\Support\Facades\DB;

class OrderDetail extends Component
{

    use LivewireAlert, PrinterSetting;

    public $order;
    public $taxes;
    public $total = 0;
    public $subTotal = 0;
    public $showOrderDetail = false;
    public $showAddCustomerModal = false;
    public $showTableModal = false;
    public $cancelOrderModal = false;
    public $deleteOrderModal = false;
    public $tableNo;
    public $tableId;
    public $orderStatus;
    public $discountAmount = 0;
    public $deliveryExecutives;
    public $deliveryExecutive;
    public $orderProgressStatus;
    public $fromPos = null;
    public $confirmDeleteModal = false;
    public $cancelReasons;
    public $cancelReason;
    public $cancelReasonText;
    public $totalTaxAmount = 0;
    public $taxMode;
    public $currencyId;
    public $users;
    public $selectWaiter;
    public $showRemovalReasonModal = false;
    public $removalReason = '';
    public $pendingOrderItemId = null;
    public $pendingComboPackId = null;
    public $pendingComboGroupKey = null;
    public $showDiscountModal = false;
    public $discountValue = null;
    public $discountType = 'fixed';

    public function mount()
    {
        $this->total = 0;
        $this->subTotal = 0;
        $this->taxes = Tax::all();
        $this->deliveryExecutives = DeliveryExecutive::where('status', 'available')->get();
        if ($this->order) {
            $this->deliveryExecutive = $this->order->delivery_executive_id;
        }
        $this->cancelReasons = KotCancelReason::where('cancel_order', true)->get();

        $this->users = User::withoutGlobalScope(BranchScope::class)
            ->where(function ($q) {
                return $q->where('branch_id', branch()->id)
                    ->orWhereNull('branch_id');
            })
            ->role('waiter_' . restaurant()->id)
            ->where('restaurant_id', restaurant()->id)
            ->get();
    }

    public function printOrder($orderId)
    {
        // Validate orderId
        if (!$orderId) {
            $this->alert('error', __('messages.orderNotFound'), [
                'toast' => true,
                'position' => 'top-end',
                'showCancelButton' => false,
                'cancelButtonText' => __('app.close')
            ]);
            return;
        }

        $orderPlaces = \App\Models\MultipleOrder::with('printerSetting')->get();

        $printerSetting = null;
        foreach ($orderPlaces as $orderPlace) {
            if ($orderPlace->printerSetting) {
                $printerSetting = $orderPlace->printerSetting;
                break;
            }
        }

        try {
            switch ($printerSetting?->printing_choice) {
                case 'directPrint':
                    $this->handleOrderPrint($orderId);
                    break;
                default:
                    $url = route('orders.print', $orderId);
                    $this->dispatch('print_location', $url);
                    break;
            }
        } catch (\Throwable $e) {
            $this->alert('error', __('messages.printerNotConnected') . ' : ' . $e->getMessage(), [
                'toast' => true,
                'position' => 'top-end',
                'showCancelButton' => false,
                'cancelButtonText' => __('app.close')
            ]);
        }
    }

    #[On('showOrderDetail')]
    public function showOrder($id, $fromPos = null)
    {
        $this->order = Order::with('items', 'items.menuItem', 'items.menuItemVariation', 'items.comboPack', 'payments', 'cancelReason')->find($id);
        $this->orderStatus = $this->order->status;
        $this->fromPos = $fromPos;
        $this->orderProgressStatus = $this->order->order_status->value;
        $restaurant = restaurant();
        $this->currencyId = $restaurant->currency_id;
        $this->taxMode = $this->order?->tax_mode ?? ($this->restaurant->tax_mode ?? 'order');

        if ($this->taxMode === 'item') {
            $this->totalTaxAmount = $this->order?->total_tax_amount ?? 0;
        }

        $this->selectWaiter = $this->order->waiter_id;
        $this->showOrderDetail = true;
    }

    /**
     * Triggered from AddPayment when "direct print after payment" is enabled,
     * reuses the same print path as the order detail Print button.
     */
    #[On('receiptPrintFromPayment')]
    public function onReceiptPrintFromPayment(mixed $id = null): void
    {
        if (is_array($id)) {
            $id = $id['id'] ?? $id['orderId'] ?? null;
        }
        if ($id) {
            $this->printOrder((int) $id);
        }
    }

    #[On('setTable')]
    public function setTable(Table $table)
    {
        $this->tableNo = $table->table_code;
        $this->tableId = $table->id;

        if ($this->order) {
            $currentOrder = Order::where('id', $this->order->id)->first();

            Table::where('id', $currentOrder->table_id)->update([
                'available_status' => 'available'
            ]);

            $currentOrder->update(['table_id' => $table->id]);

            if ($this->order->date_time->format('d-m-Y') == now()->format('d-m-Y')) {
                Table::where('id', $this->tableId)->update([
                    'available_status' => 'running'
                ]);
            }

            $this->order->fresh();
            $this->dispatch('showOrderDetail', id: $this->order->id);
        }

        $this->dispatch('posOrderSuccess');
        $this->dispatch('refreshOrders');
        $this->dispatch('refreshPos');

        $this->showTableModal = false;
    }

    public function saveOrderStatus()
    {
        if ($this->order) {
            Order::where('id', $this->order->id)->update(['status' => $this->orderStatus]);

            $this->dispatch('posOrderSuccess');
            $this->dispatch('refreshOrders');
            $this->dispatch('refreshPos');
        }
    }

    public function showAddCustomer($id)
    {
        $this->order = Order::find($id);
        $this->showAddCustomerModal = true;
    }

    public function showDeleteItemModal($id)
    {
        $this->promptOrderItemRemoval($id);
    }

    public function promptOrderItemRemoval($id): void
    {
        if (!user_can('Delete KOT Item')) {
            $this->alert('error', __('messages.kotDeletePermissionDenied'), [
                'toast' => true,
                'position' => 'top-end',
                'showCancelButton' => false,
                'cancelButtonText' => __('app.close')
            ]);
            return;
        }

        if ($this->order && in_array($this->order->status, ['billed', 'paid', 'payment_due'], true) && !user_can('Edit Billed Order')) {
            $this->alert('error', __('messages.editBilledOrderPermissionDenied'), [
                'toast' => true,
                'position' => 'top-end',
                'showCancelButton' => false,
                'cancelButtonText' => __('app.close')
            ]);
            return;
        }

        $this->pendingOrderItemId = $id;
        $this->pendingComboPackId = null;
        $this->removalReason = '';
        $this->showRemovalReasonModal = true;
    }

    public function cancelOrderItemRemoval(): void
    {
        $this->showRemovalReasonModal = false;
        $this->removalReason = '';
        $this->pendingOrderItemId = null;
        $this->pendingComboPackId = null;
        $this->pendingComboGroupKey = null;
    }

    public function confirmOrderItemRemoval(): void
    {
        $this->validate([
            'removalReason' => 'required|string|min:3',
        ]);

        if (!$this->pendingOrderItemId && !$this->pendingComboPackId && !$this->pendingComboGroupKey) {
            return;
        }

        if ($this->pendingComboGroupKey) {
            $this->executeComboGroupRemoval($this->pendingComboGroupKey, $this->removalReason);
        } elseif ($this->pendingComboPackId) {
            // Backward compatibility for old state shape.
            $this->executeComboGroupRemoval('pack:' . (int) $this->pendingComboPackId, $this->removalReason);
        } else {
            $this->performOrderItemDeletion($this->pendingOrderItemId, $this->removalReason);
        }
        $this->cancelOrderItemRemoval();
    }

    public function deleteOrderItems($id)
    {
        if ($this->order && in_array($this->order->status, ['billed', 'paid', 'payment_due'], true) && !user_can('Edit Billed Order')) {
            $this->alert('error', __('messages.editBilledOrderPermissionDenied'), [
                'toast' => true,
                'position' => 'top-end',
                'showCancelButton' => false,
                'cancelButtonText' => __('app.close')
            ]);
            return;
        }

        $this->performOrderItemDeletion($id);
    }

    public function removeComboGroup(string $comboGroupKey): void
    {
        if (!$this->order) {
            return;
        }

        if ($this->order->status === 'canceled') {
            return;
        }

        if (!user_can('Delete KOT Item')) {
            $this->alert('error', __('messages.kotDeletePermissionDenied'), [
                'toast' => true,
                'position' => 'top-end',
                'showCancelButton' => false,
                'cancelButtonText' => __('app.close')
            ]);
            return;
        }

        if (in_array($this->order->status, ['billed', 'paid', 'payment_due'], true) && !user_can('Edit Billed Order')) {
            $this->alert('error', __('messages.editBilledOrderPermissionDenied'), [
                'toast' => true,
                'position' => 'top-end',
                'showCancelButton' => false,
                'cancelButtonText' => __('app.close')
            ]);
            return;
        }

        $comboItemIds = $this->getComboOrderItemIdsByGroupKey($comboGroupKey);

        if (empty($comboItemIds)) {
            return;
        }

        $this->pendingOrderItemId = null;
        $this->pendingComboPackId = null;
        $this->pendingComboGroupKey = $this->normalizeComboGroupKey($comboGroupKey);
        $this->removalReason = '';
        $this->showRemovalReasonModal = true;
    }

    public function removeComboGroupByOrderItem(int $orderItemId): void
    {
        if (!$this->order) {
            return;
        }

        $orderItem = $this->order->items()
            ->whereNotNull('combo_pack_id')
            ->find($orderItemId);

        if (!$orderItem) {
            return;
        }

        $instanceKey = $this->extractComboInstanceKey($orderItem->note);
        $comboGroupKey = $instanceKey
            ? 'instance:' . $instanceKey
            : 'pack:' . (int) $orderItem->combo_pack_id;

        $this->removeComboGroup($comboGroupKey);
    }

    protected function executeComboGroupRemoval(string $comboGroupKey, string $note): void
    {
        if (!$this->order) {
            return;
        }

        $comboItemIds = $this->getComboOrderItemIdsByGroupKey($comboGroupKey);

        if (empty($comboItemIds)) {
            return;
        }

        foreach ($comboItemIds as $orderItemId) {
            $this->performOrderItemDeletion((int) $orderItemId, $note, false);
        }

        $this->alert('success', __('messages.orderItemDeleted'), [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close')
        ]);
    }

    protected function performOrderItemDeletion($id, ?string $note = null, bool $notify = true): void
    {
        $orderItem = OrderItem::find($id);

        if ($orderItem) {
            $kotItems = KotItem::where('menu_item_id', $orderItem->menu_item_id)
                ->where(function ($query) use ($orderItem) {
                    if ($orderItem->menu_item_variation_id) {
                        $query->where('menu_item_variation_id', $orderItem->menu_item_variation_id);
                    } else {
                        $query->whereNull('menu_item_variation_id');
                    }
                })
                ->whereHas('kot', function ($query) use ($orderItem) {
                    $query->where('order_id', $orderItem->order_id);
                })
                ->get();

            if ($kotItems->isNotEmpty()) {
                foreach ($kotItems as $kotItem) {
                    KotAdjustmentLogger::log(
                        $kotItem,
                        'deleted',
                        $note ?: __('modules.order.deleteOrderItemMessage'),
                        $kotItem->quantity,
                        0
                    );

                    $kotItem->delete();
                }
            } else {
                // No KOT items — item was billed directly; log against the order item itself
                KotAdjustmentLogger::logOrderItem(
                    $orderItem,
                    'deleted_from_order',
                    $note ?: __('modules.order.deleteOrderItemMessage'),
                    $orderItem->quantity,
                    0
                );
            }
        }

        OrderItem::destroy($id);

        if ($this->order) {
            $this->order->refresh();

            if ($this->order->items->count() === 0) {
                $this->deleteOrder($this->order->id);
                return;
            }

            $this->recalculateOrderTotals();

            // Keep payment records in sync with the revised total
            if (in_array($this->order->status, ['paid', 'payment_due'])) {
                $this->scalePaymentsToNewTotal($this->total);
            }
        }

        if ($notify) {
            $this->alert('success', __('messages.orderItemDeleted'), [
                'toast' => true,
                'position' => 'top-end',
                'showCancelButton' => false,
                'cancelButtonText' => __('app.close')
            ]);
        }

        $this->dispatch('refreshPos');
    }

    protected function extractComboInstanceKey(?string $note): ?string
    {
        if (!$note) {
            return null;
        }

        if (preg_match('/\[COMBO_INSTANCE:([^\]]+)\]/', $note, $matches) && !empty($matches[1])) {
            return trim((string) $matches[1]);
        }

        return null;
    }

    protected function normalizeComboGroupKey(?string $comboGroupKey): ?string
    {
        if ($comboGroupKey === null) {
            return null;
        }

        $comboGroupKey = trim((string) $comboGroupKey);
        if ($comboGroupKey === '') {
            return null;
        }

        if (is_numeric($comboGroupKey)) {
            return 'pack:' . (int) $comboGroupKey;
        }

        if (str_starts_with($comboGroupKey, 'instance:') || str_starts_with($comboGroupKey, 'pack:')) {
            return $comboGroupKey;
        }

        return $comboGroupKey;
    }

    protected function getComboOrderItemIdsByGroupKey(?string $comboGroupKey): array
    {
        if (!$this->order) {
            return [];
        }

        $normalizedKey = $this->normalizeComboGroupKey($comboGroupKey);
        if (!$normalizedKey) {
            return [];
        }

        $comboItems = $this->order->items()
            ->whereNotNull('combo_pack_id')
            ->get(['id', 'combo_pack_id', 'note']);

        if (str_starts_with($normalizedKey, 'instance:')) {
            $instanceKey = substr($normalizedKey, strlen('instance:'));
            if ($instanceKey === '') {
                return [];
            }

            return $comboItems
                ->filter(fn ($item) => $this->extractComboInstanceKey($item->note) === $instanceKey)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();
        }

        if (str_starts_with($normalizedKey, 'pack:')) {
            $comboPackId = (int) substr($normalizedKey, strlen('pack:'));
            if ($comboPackId <= 0) {
                return [];
            }

            return $comboItems
                ->where('combo_pack_id', $comboPackId)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();
        }

        return [];
    }

    public function updatedOrderProgressStatus($value)
    {
        if (empty($this->order) || is_null($value)) {
            return;
        }

        // DEBUG: Log what we're trying to save
        \Log::info('Order Status Update', [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'new_status' => $value,
            'order_type' => $this->order->order_type,
        ]);

        $this->order->update(['order_status' => $value]);
        $this->orderProgressStatus = $value;

        if ($value === 'food_ready') {
            $this->dispatch('food_ready_sound');
            $this->alert('success', __('messages.foodReady'), [
                'toast' => true,
                'position' => 'top-end'
            ]);
        }

        // DEBUG: Log what was actually saved
        $this->order->refresh();
        \Log::info('Order Status After Save', [
            'order_id' => $this->order->id,
            'saved_status' => $this->order->order_status->value,
        ]);

        if ($value === 'confirmed') {
            // If this order came from customer site and was held for staff confirmation,
            // it may not have KOTs yet. Generate them now so kitchen can start.
            if ($this->order->kot()->count() === 0) {
                $transactionId = uniqid('TXN_', true) . '_' . random_int(100000, 999999);

                $kot = Kot::create([
                    'branch_id' => $this->order->branch_id,
                    'kot_number' => (Kot::generateKotNumber($this->order->branch) + 1),
                    'order_id' => $this->order->id,
                    'order_type_id' => $this->order->order_type_id,
                    'token_number' => Kot::generateTokenNumber($this->order->branch_id, $this->order->order_type_id),
                    'note' => $this->order->note ?? null,
                    'transaction_id' => $transactionId,
                ]);

                foreach ($this->order->items as $orderItem) {
                    $kotItem = KotItem::create([
                        'kot_id' => $kot->id,
                        'menu_item_id' => $orderItem->menu_item_id,
                        'menu_item_variation_id' => $orderItem->menu_item_variation_id,
                        'quantity' => $orderItem->quantity,
                        'transaction_id' => $transactionId,
                        'note' => $orderItem->note,
                    ]);

                    $sync = [];
                    foreach ($orderItem->modifierOptions as $modifier) {
                        $qty = (int) ($modifier->pivot->quantity ?? 1);
                        $sync[$modifier->id] = ['quantity' => max(1, $qty)];
                    }
                    if (!empty($sync)) {
                        $kotItem->modifierOptions()->sync($sync);
                    }
                }

                $this->order->update(['status' => 'kot']);
            }

            $this->order->kot->each(function ($kot) {
                $kot->update(['status' => 'in_kitchen']);
            });
        }

        $this->dispatch('posOrderSuccess');
        $this->dispatch('refreshOrders');
        $this->dispatch('refreshPos');
    }

    public function saveOrder($action)
    {

        switch ($action) {
        case 'bill':
            $successMessage = __('messages.billedSuccess');
            $status = 'billed';
            // Billing closes the table (free it for new guests)
            $tableStatus = 'available';
                break;

        case 'kot':
            return $this->redirect(route('pos.kot', $this->order->id), navigate: true);
        }

        $taxes = Tax::all();

        $this->order->update([
            'date_time' => now(),
            'status' => $status
        ]);

        if ($status == 'billed') {
            $totalTaxAmount = 0;

            foreach ($this->order->kot as $kot) {
                foreach ($kot->items as $item) {
                    $price = (($item->menu_item_variation_id) ? $item->menuItemVariation->price : $item->menuItem->price);
                    $amount = $price * $item->quantity;

                    // Calculate tax for item-level taxation
                    $taxAmount = 0;
                    $taxPercentage = 0;
                    $taxBreakup = null;

                    if ($this->taxMode === 'item') {
                        $menuItem = $item->menuItem;
                        $taxes = $menuItem->taxes ?? collect();
                        $isInclusive = restaurant()->tax_inclusive ?? false;

                        if ($taxes->isNotEmpty()) {
                            $taxResult = MenuItem::calculateItemTaxes($price, $taxes, $isInclusive);
                            $taxAmount = $taxResult['tax_amount'] * $item->quantity;
                            $taxPercentage = $taxResult['tax_percentage'];
                            $taxBreakup = json_encode($taxResult['tax_breakdown']);
                            $totalTaxAmount += $taxAmount;
                        }
                    }

                    OrderItem::create([
                        'order_id' => $this->order->id,
                        'menu_item_id' => $item->menu_item_id,
                        'menu_item_variation_id' => $item->menu_item_variation_id,
                        'quantity' => $item->quantity,
                        'price' => $price,
                        'amount' => $amount,
                        'tax_amount' => $taxAmount,
                        'tax_percentage' => $taxPercentage,
                        'tax_breakup' => $taxBreakup,
                    ]);
                }
            }

            if ($this->taxMode === 'order') {
                foreach ($taxes as $value) {
                    OrderTax::create([
                        'order_id' => $this->order->id,
                        'tax_id' => $value->id
                    ]);
                }
            }

            $this->total = 0;
            $this->subTotal = 0;

            foreach ($this->order->load('items')->items as $value) {
                if ($this->taxMode === 'item') {
                    $isInclusive = restaurant()->tax_inclusive ?? false;
                    if ($isInclusive) {
                        // For inclusive tax: subtract tax from amount to get subtotal
                        $this->subTotal += ($value->amount - ($value->tax_amount ?? 0));
                    } else {
                        // For exclusive tax: amount is subtotal
                        $this->subTotal += $value->amount;
                    }
                } else {
                    $this->subTotal += $value->amount;
                }
                $this->total += $value->amount;
            }

            // Calculate taxes for order-level taxation
            if ($this->taxMode === 'order') {
                foreach ($taxes as $value) {
                    $taxAmount = ($value->tax_percent / 100) * $this->subTotal;
                    $this->total += $taxAmount;
                    $totalTaxAmount += $taxAmount;
                }
            } elseif ($this->taxMode === 'item') {
                $isInclusive = restaurant()->tax_inclusive ?? false;
                if (!$isInclusive) {
                    // For exclusive taxes, add tax to total
                    $this->total += $totalTaxAmount;
                }
            }

            // Apply discounts
            if ($this->order->discount_type === 'percent') {
                $this->discountAmount = round(($this->subTotal * $this->order->discount_value) / 100, 2);
            } elseif ($this->order->discount_type === 'fixed') {
                $this->discountAmount = min($this->order->discount_value, $this->subTotal);
            }

            $this->total -= $this->discountAmount ?? 0;

            Order::where('id', $this->order->id)->update([
                'sub_total' => $this->subTotal,
                'total' => $this->total,
                'discount_amount' => $this->discountAmount,
                'total_tax_amount' => $totalTaxAmount,
            ]);
        }

        Table::where('id', $this->tableId)->update([
            'available_status' => $tableStatus
        ]);


        $this->alert('success', $successMessage, [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close')
        ]);

        if ($status == 'billed') {
            $this->dispatch('showOrderDetail', id: $this->order->id);
            $this->dispatch('posOrderSuccess');
            $this->dispatch('refreshOrders');
            $this->dispatch('resetPos');
        }
    }

    public function showPayment($id)
    {
        $this->dispatch('showPaymentModal', id: $id);
    }

    public function cancelOrderStatus($id)
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

        if ($id) {
            $order = Order::find($id);

            if ($order) {
                $order->update([
                    'status' => 'canceled',
                    'order_status' => 'cancelled',
                    'cancel_reason_id' => $this->cancelReason,
                    'cancel_reason_text' => $this->cancelReasonText,
                ]);

                // Update table status
                if ($order->table_id) {
                    $table = Table::find($order->table_id);

                    if ($table) {
                        $table->update(['available_status' => 'available']);

                        // Release table session lock if exists
                        if ($table->tableSession) {
                            if ($table->tableSession->isOrderLock() && $table->tableSession->order_id === $order->id) {
                                $table->unlockFromOrder($order->id);
                            } else {
                                $table->tableSession->releaseLock();
                            }
                        }
                    }
                }


                $this->alert('success', __('messages.orderCanceled'), [
                    'toast' => true,
                    'position' => 'top-end',
                    'showCancelButton' => false,
                    'cancelButtonText' => __('app.close'),
                ]);

                $this->confirmDeleteModal = false;
                $this->cancelReason = null;
                $this->cancelReasonText = null;

                return $this->redirect(route('pos.index'), navigate: true);
            }
        }
    }

    public function cancelOrder($id)
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

        $order = Order::find($id);

        if ($order) {
            $order->update([
                'status' => 'canceled',
                'order_status' => 'cancelled',
                'cancel_reason_id' => $this->cancelReason,
                'cancel_reason_text' => $this->cancelReasonText,

            ]);
            $order->kot()->delete();
            $order->payments()->delete();

            if ($order->table_id) {
                Table::where('id', $order->table_id)->update([
                    'available_status' => 'available',
                ]);
            }
            $this->cancelOrderModal = false;
            $this->confirmDeleteModal = false;
            $this->cancelReason = null;
            $this->cancelReasonText = null;
            $this->dispatch('showOrderDetail', id: $this->order->id);
            $this->dispatch('posOrderSuccess');
            $this->dispatch('refreshOrders');

            $this->alert('success', __('messages.orderCanceled'), [
                'toast' => true,
                'position' => 'top-end',
                'showCancelButton' => false,
                'cancelButtonText' => __('app.close')
            ]);

            if ($this->fromPos) {
                return $this->redirect(route('pos.index'), navigate: true);
            } else {
                $this->dispatch('resetPos');
            }
        }
    }

    public function paymentReceived($orderId, $status)
    {
        $order = Order::with('payments')->find($orderId);

        if (!$order) {
            $this->alert('error', __('messages.orderNotFound'), [
                'toast' => true,
                'position' => 'top-end',
                'showCancelButton' => false,
                'cancelButtonText' => __('app.close')
            ]);
            return;
        }

        if ($status === 'received') {
            $amountPaid = $order->payments->sum('amount');
            $order->update([
                'status' => 'paid',
                'amount_paid' => $amountPaid
            ]);
        } elseif ($status === 'not_received') {
            $latestPayment = $order->payments->last();
            if ($latestPayment) {
                $latestPayment->delete();
            }
            $order->update(['status' => 'payment_due']);
        }

        $this->alert('success', __('messages.statusUpdated'), [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close')
        ]);

        $this->dispatch('showOrderDetail', id: $this->order->id);
        $this->dispatch('refreshOrders');
        $this->dispatch('refreshPos');
    }

    public function deleteOrder($id)
    {
        $order = Order::find($id);

        if (!$order) {
            $this->alert('error', __('messages.orderNotFound'), [
                'toast' => true,
                'position' => 'top-end',
                'showCancelButton' => false,
                'cancelButtonText' => __('app.close')
            ]);
            return;
        }

        if ($order->table_id) {
            Table::where('id', $order->table_id)->update(['available_status' => 'available']);
        }
        // Delete associated KOT records
        $order->kot()->delete();

        $hasAdjustments = \App\Models\KotItemAdjustment::where('order_id', $order->id)->exists();

        if ($hasAdjustments) {
            $order->update([
                'status' => 'canceled',
                'order_status' => \App\Enums\OrderStatus::CANCELLED,
                'sub_total' => 0,
                'total' => 0,
                'discount_amount' => 0,
                'total_tax_amount' => 0,
            ]);

            $this->alert('success', __('messages.orderCanceled'), [
                'toast' => true,
                'position' => 'top-end',
                'showCancelButton' => false,
                'cancelButtonText' => __('app.close')
            ]);
        } else {
            $order->delete();

        $this->alert('success', __('messages.orderDeleted'), [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close')
        ]);
        }


        $this->deleteOrderModal = false;
        $this->showOrderDetail = false;
        $order = null;
        $this->order = null;

        if ($this->fromPos) {
            return $this->redirect(route('pos.index'), navigate: true);
        }
        else {

            $this->dispatch('refreshOrders');
            $this->dispatch('refreshPos');
            $this->dispatch('refreshKots');
        }

    }

    public function saveDeliveryExecutive()
    {
        $this->order->update(['delivery_executive_id' => $this->deliveryExecutive]);
        $this->order->fresh();
        $this->alert('success', __('messages.deliveryExecutiveAssigned'), [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close')
        ]);
    }

    public function removeCharge($chargeId)
    {
        if ($this->order && in_array($this->order->status, ['paid', 'payment_due']) && !user_can('Edit Billed Order')) {
            $this->alert('error', __('messages.editBilledOrderPermissionDenied'), [
                'toast' => true,
                'position' => 'top-end',
                'showCancelButton' => false,
                'cancelButtonText' => __('app.close')
            ]);
            return;
        }

        $charge = OrderCharge::find($chargeId);

        if ($charge) {
            $charge->delete();
            $this->order->refresh();

            // Recalculate order totals properly
            $this->recalculateOrderTotals();
        }
    }

    public function updatePaymentMethod($id, $paymentMethod)
    {
        if (!$id || !$paymentMethod || !$this->order) {
            return;
        }

        $payment = $this->order->payments()->whereId($id)->first();

        if (!$payment) {
            return;
        }

        if ($paymentMethod === 'due') {
            $this->order->refresh();
            if (!$this->order->canRecordDueBalance()) {
                $this->alert('warning', __('modules.order.customerRequiredForDuePayment'), [
                    'toast' => true,
                    'position' => 'top-end',
                    'showCancelButton' => false,
                    'cancelButtonText' => __('app.close'),
                ]);
                $this->dispatch(
                    'showAddCustomerModal',
                    id: $this->order->id,
                    customerId: null,
                    fromPos: (bool) $this->fromPos,
                    forDuePayment: true,
                    preferDueAfterAttach: false
                )->to(AddCustomer::class);

                return;
            }
        }

        $payment->payment_method = $paymentMethod;
        $payment->save();

        $hasPaymentDue = $this->order->payments->contains('payment_method', 'due');

        $newStatus = $hasPaymentDue ? 'payment_due' : 'paid';

        if ($this->order->status !== $newStatus) {
            $this->order->status = $newStatus;
            $this->order->save();
        }

        $this->alert('success', __('messages.statusUpdated'), [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close')
        ]);

        $this->dispatch('showOrderDetail', id: $this->order->id);
        $this->dispatch('refreshOrders');
    }

    public function updatedSelectWaiter($value)
    {
        if ($this->order) {
            $this->order->update(['waiter_id' => $value ?: null]);

            $this->alert('success', __('messages.waiterUpdated'), [
                'toast' => true,
                'position' => 'top-end',
                'showCancelButton' => false,
                'cancelButtonText' => __('app.close')
            ]);
        }
    }

    /**
     * Recalculate order totals including all components
     */
    /**
     * Reduce overpaid payment amounts so their sum equals the new order total.
     * Works from the most-recent payment backwards, never letting any amount go below zero.
     */
    private function scalePaymentsToNewTotal(float $newTotal): void
    {
        $payments = $this->order->payments()
            ->where('payment_method', '!=', 'due')
            ->orderBy('id')
            ->get();

        $excess = round($payments->sum('amount') - $newTotal, 2);

        if ($excess <= 0) {
            return;
        }

        // Reduce from the most recent payment first
        foreach ($payments->sortByDesc('id') as $payment) {
            if ($excess <= 0) {
                break;
            }
            $canReduce = min((float) $payment->amount, $excess);
            $payment->update(['amount' => round($payment->amount - $canReduce, 2)]);
            $excess = round($excess - $canReduce, 2);
        }

        $this->order->update([
            'amount_paid' => $this->order->payments()
                ->where('payment_method', '!=', 'due')
                ->sum('amount'),
        ]);

        $this->order->refresh();
        $this->order->load('payments');
    }

    public function recalculateOrderTotals()
    {
        if (!$this->order) {
            return;
        }

        // Refresh the order to get latest data
        $this->order->refresh();
        $this->order->load(['items', 'charges', 'taxes', 'payments']);

        // Reset totals
        $this->subTotal = 0;
        $this->total = 0;
        $totalTaxAmount = 0;

        // Calculate subtotal from items
        foreach ($this->order->items as $item) {
            if ($this->taxMode === 'item') {
                $isInclusive = restaurant()->tax_inclusive ?? false;
                if ($isInclusive) {
                    // For inclusive tax: subtract tax from amount to get subtotal
                    $this->subTotal += ($item->amount - ($item->tax_amount ?? 0));
                } else {
                    // For exclusive tax: amount is subtotal
                    $this->subTotal += $item->amount;
                }
            } else {
                $this->subTotal += $item->amount;
            }
        }

        // Calculate taxes
        if ($this->taxMode === 'order') {
            // Order-level taxation
            foreach ($this->order->taxes as $orderTax) {
                $taxAmount = ($orderTax->tax->tax_percent / 100) * $this->subTotal;
                $totalTaxAmount += $taxAmount;
            }
        } else {
            // Item-level taxation
            $isInclusive = restaurant()->tax_inclusive ?? false;
            foreach ($this->order->items as $item) {
                $totalTaxAmount += ($item->tax_amount ?? 0);
            }

            if (!$isInclusive) {
                // For exclusive taxes, add tax to total
                $this->total += $totalTaxAmount;
            }
        }

        // Start with subtotal + taxes
        $this->total = $this->subTotal + $totalTaxAmount;

        // Apply discount
        $discountAmount = 0;
        if ($this->order->discount_type === 'percent') {
            $discountAmount = round(($this->subTotal * $this->order->discount_value) / 100, 2);
        } elseif ($this->order->discount_type === 'fixed') {
            $discountAmount = $this->order->discount_value;
        }
        $this->total -= $discountAmount;

        // Add charges (delivery fees, service charges, etc.)
        foreach ($this->order->charges as $charge) {
            $chargeAmount = $charge->charge->getAmount($this->subTotal - $discountAmount);
            $this->total += $chargeAmount;
        }

        // Add tip
        if ($this->order->tip_amount > 0) {
            $this->total += $this->order->tip_amount;
        }

        // Add delivery fee
        if ($this->order->order_type === 'delivery' && !is_null($this->order->delivery_fee)) {
            $this->total += $this->order->delivery_fee;
        }

        // Update the order in database
        $this->order->update([
            'sub_total' => $this->subTotal,
            'total' => $this->total,
            'discount_amount' => $discountAmount,
            'total_tax_amount' => $totalTaxAmount
        ]);
    }

    /**
     * Get the display price for an item (base price without tax for inclusive items)
     */
    public function getItemDisplayPrice($key)
    {
        if ($this->taxMode === 'item' && isset($this->orderItemTaxDetails[$key])) {
            return $this->orderItemTaxDetails[$key]['display_price'] ?? 0;
        }

        // Check if we have session data arrays (for active POS session)
        if (isset($this->orderItemList[$key])) {
            $basePrice = isset($this->orderItemVariation[$key]) ? $this->orderItemVariation[$key]->price : $this->orderItemList[$key]->price;
            $modifierPrice = $this->orderItemModifiersPrice[$key] ?? 0;
            return $basePrice + $modifierPrice;
        }

        // For existing order items (when viewing order details), use the saved price from database
        if ($this->order && isset($this->order->items[$key])) {
            $orderItem = $this->order->items[$key];
            
            // For combo items, use the saved price (which is the discounted price)
            // The price field in order_items already contains the final price after combo discount
            if ($orderItem->is_combo_item) {
                // Return the price per unit (price field contains the discounted price per unit)
                return $orderItem->price;
            }
            
            // For non-combo items, check if we need to calculate with tax
            $basePrice = !is_null($orderItem->menuItemVariation) ? $orderItem->menuItemVariation->price : $orderItem->menuItem->price;
            $modifierPrice = $orderItem->modifierOptions->sum('price');

            // If tax is inclusive, calculate the display price without tax
            if (restaurant()->tax_inclusive && restaurant()->tax_mode === 'item') {
                $menuItem = $orderItem->menuItem;
                $taxes = $menuItem->taxes ?? collect();
                $itemPriceWithModifiers = $basePrice + $modifierPrice;

                if ($taxes->isNotEmpty()) {
                    $taxPercent = $taxes->sum('tax_percent');
                    $displayPrice = $itemPriceWithModifiers / (1 + $taxPercent / 100);
                    return $displayPrice;
                }
            }

            return $basePrice + $modifierPrice;
        }

        return 0;
    }

    public function showAddDiscount()
    {
        if (!user_can('Edit Billed Order')) {
            $this->alert('error', __('messages.noPermission'), [
                'toast' => true, 'position' => 'top-end',
                'showCancelButton' => false, 'cancelButtonText' => __('app.close'),
            ]);
            return;
        }
        $this->discountType  = $this->order->discount_type  ?? 'fixed';
        $this->discountValue = $this->order->discount_value ?? null;
        $this->showDiscountModal = true;
    }

    public function applyDiscount()
    {
        if (!user_can('Edit Billed Order')) {
            return;
        }

        $this->validate(['discountValue' => 'required|numeric|min:0']);

        $subTotal = (float) $this->order->sub_total;

        if ($this->discountType === 'percent') {
            if ($this->discountValue > 100) {
                $this->addError('discountValue', __('messages.discountCannotExceedTotal'));
                return;
            }
            $discountAmount = round(($subTotal * $this->discountValue) / 100, 2);
        } else {
            if ($this->discountValue > $subTotal) {
                $this->addError('discountValue', __('messages.discountCannotExceedTotal'));
                return;
            }
            $discountAmount = (float) $this->discountValue;
        }

        // Re-calculate total: add back any existing discount then subtract the new one
        $oldDiscount = (float) ($this->order->discount_amount ?? 0);
        $newTotal    = max(0, ($this->order->total + $oldDiscount) - $discountAmount);
        $statusBefore = $this->order->status;

        try {
            DB::transaction(function () use ($discountAmount, $newTotal, $statusBefore) {
                $this->order->update([
                    'discount_type'   => $this->discountType,
                    'discount_value'  => $this->discountValue,
                    'discount_amount' => $discountAmount,
                    'total'           => $newTotal,
                ]);

                $this->order->refresh();
                $this->order->load('payments');

                if (in_array($statusBefore, ['paid', 'payment_due'], true)) {
                    $this->scalePaymentsToNewTotal($newTotal);
                    $this->order->refresh();

                    $amountPaid = $this->order->payments()
                        ->where('payment_method', '!=', 'due')
                        ->sum('amount');
                    $newStatus = ($amountPaid >= $newTotal - 0.0001) ? 'paid' : 'payment_due';
                    if ($newStatus === 'payment_due' && !$this->order->canRecordDueBalance()) {
                        throw new \RuntimeException(__('modules.order.customerRequiredForDuePayment'));
                    }
                    if ($this->order->status !== $newStatus) {
                        $this->order->update(['status' => $newStatus]);
                    }
                }
            });
        } catch (\RuntimeException $e) {
            // Ensure component state matches rolled-back database values.
            if ($this->order) {
                $this->order->refresh();
                $this->order->load('payments');
            }
            $this->alert('warning', $e->getMessage(), [
                'toast' => true,
                'position' => 'top-end',
                'showCancelButton' => false,
                'cancelButtonText' => __('app.close'),
            ]);

            return;
        }

        $this->order->refresh();
        $this->showDiscountModal = false;

        $this->alert('success', __('modules.order.discountApplied'), [
            'toast' => true, 'position' => 'top-end',
            'showCancelButton' => false, 'cancelButtonText' => __('app.close'),
        ]);

        $this->dispatch('refreshPos');
        $this->dispatch('refreshOrders');
    }

    public function removeDiscount()
    {
        if (!user_can('Edit Billed Order')) {
            return;
        }

        $oldDiscount = (float) ($this->order->discount_amount ?? 0);
        $newTotal    = $this->order->total + $oldDiscount;
        $statusBefore = $this->order->status;

        if (in_array($statusBefore, ['paid', 'payment_due'], true)) {
            $amountPaid = $this->order->payments()
                ->where('payment_method', '!=', 'due')
                ->sum('amount');

            if ($amountPaid < $newTotal - 0.0001 && !$this->order->canRecordDueBalance()) {
                $this->alert('warning', __('modules.order.customerRequiredForDuePayment'), [
                    'toast' => true,
                    'position' => 'top-end',
                    'showCancelButton' => false,
                    'cancelButtonText' => __('app.close'),
                ]);
                $this->dispatch(
                    'showAddCustomerModal',
                    id: $this->order->id,
                    customerId: null,
                    fromPos: (bool) $this->fromPos,
                    forDuePayment: true,
                    preferDueAfterAttach: false
                )->to(AddCustomer::class);

                return;
            }
        }

        $this->order->update([
            'discount_type'   => null,
            'discount_value'  => null,
            'discount_amount' => null,
            'total'           => $newTotal,
        ]);

        // If the order was paid and the new total exceeds what was collected, mark as payment_due
        if (in_array($statusBefore, ['paid', 'payment_due'], true)) {
            $this->order->refresh();
            $amountPaid = $this->order->payments()
                ->where('payment_method', '!=', 'due')
                ->sum('amount');

            if ($amountPaid < $newTotal - 0.0001) {
                $shortfall = round($newTotal - $amountPaid, 2);
                $this->order->payments()->create([
                    'payment_method' => 'due',
                    'amount'         => $shortfall,
                    'order_id'       => $this->order->id,
                ]);
                $this->order->update(['status' => 'payment_due']);
            }
        }

        $this->order->refresh();

        $this->alert('success', __('modules.order.discountRemoved'), [
            'toast' => true, 'position' => 'top-end',
            'showCancelButton' => false, 'cancelButtonText' => __('app.close'),
        ]);

        $this->dispatch('refreshPos');
        $this->dispatch('refreshOrders');
    }

    public function render()
    {
        return view('livewire.order.order-detail');
    }

}
