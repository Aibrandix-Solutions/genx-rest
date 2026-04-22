<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Customer;
use App\Models\DeliveryExecutive;
use App\Models\DeliveryPlatform;
use App\Models\Kot;
use App\Models\KotCancelReason;
use App\Models\Order;
use App\Models\OrderType;
use App\Models\Reservation;
use App\Models\RestaurantCharge;
use App\Models\Table;
use App\Models\User;
use App\Scopes\BranchScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class PosSupportController extends Controller
{
    public function getOrderNumber()
    {
        abort_if(!in_array('Order', restaurant_modules()) || !user_can('Create Order'), 403);

        $branch = branch();
        abort_if(!$branch, 422, 'Branch context is required');

        $numberData = Order::generateOrderNumber($branch);
        $rawNumber = (string) ($numberData['order_number'] ?? '');
        $formatted = (string) ($numberData['formatted_order_number'] ?? ('Order #' . $rawNumber));

        return response()->json([$rawNumber, $formatted]);
    }

    public function orderTypes()
    {
        return response()->json(
            OrderType::query()
                ->where('is_active', true)
                ->select('id', 'order_type_name', 'slug')
                ->orderBy('order_type_name')
                ->get()
        );
    }

    public function cancelReasons()
    {
        abort_if(!in_array('Order', restaurant_modules()), 403);

        $branch = branch();
        abort_if(!$branch, 422, 'Branch context is required');

        return response()->json(
            KotCancelReason::query()
                ->where('cancel_order', true)
                ->orderBy('reason')
                ->get(['id', 'reason'])
                ->map(fn (KotCancelReason $reason) => [
                    'id' => (int) $reason->id,
                    'reason' => (string) $reason->reason,
                ])
                ->values()
        );
    }

    public function deliveryPlatforms()
    {
        return response()->json(
            DeliveryPlatform::query()
                ->where('is_active', true)
                ->select('id', 'name', 'logo')
                ->orderBy('name')
                ->get()
                ->map(fn($platform) => [
                    'id' => (int) $platform->id,
                    'name' => (string) $platform->name,
                    'logo_url' => $platform->logo_url,
                ])->values()
        );
    }

    public function waiters()
    {
        $restaurant = restaurant();
        $branch = branch();

        if (!$restaurant || !$branch) {
            return response()->json([]);
        }

        return response()->json(
            User::withoutGlobalScope(BranchScope::class)
                ->where(function ($query) use ($branch) {
                    $query->where('branch_id', $branch->id)
                        ->orWhereNull('branch_id');
                })
                ->role('waiter_' . $restaurant->id)
                ->where('restaurant_id', $restaurant->id)
                ->select('id', 'name')
                ->orderBy('name')
                ->get()
                ->map(fn($waiter) => [
                    'id' => (int) $waiter->id,
                    'name' => (string) $waiter->name,
                ])->values()
        );
    }

    public function saveOrderPreferences(Request $request)
    {
        $validated = $request->validate([
            'order_type_id' => ['required', 'integer', 'exists:order_types,id'],
            'set_as_default_order_type' => ['nullable', 'boolean'],
            'selected_delivery_app' => ['nullable', 'string'],
        ]);

        $user = auth()->user();
        $orderType = OrderType::query()->findOrFail((int) $validated['order_type_id']);
        $setAsDefault = (bool) ($validated['set_as_default_order_type'] ?? false);

        if ($user) {
            if ($setAsDefault) {
                $user->update(['default_order_type_id' => (int) $orderType->id]);
            } elseif ((int) ($user->default_order_type_id ?? 0) === (int) $orderType->id) {
                $user->update(['default_order_type_id' => null]);
            }
        }

        $selectedDeliveryApp = $validated['selected_delivery_app'] ?? null;
        if ($orderType->slug !== 'delivery') {
            session()->forget('pos.delivery_app_id');
        } elseif ($selectedDeliveryApp === 'default' || $selectedDeliveryApp === null || $selectedDeliveryApp === '') {
            session()->put('pos.delivery_app_id', 'default');
        } else {
            $platformId = (int) $selectedDeliveryApp;
            $platformExists = DeliveryPlatform::query()
                ->where('id', $platformId)
                ->where('is_active', true)
                ->exists();

            if ($platformExists) {
                session()->put('pos.delivery_app_id', $platformId);
            } else {
                session()->put('pos.delivery_app_id', 'default');
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'order_type_id' => (int) $orderType->id,
                'set_as_default_order_type' => $setAsDefault,
                'selected_delivery_app' => session()->get('pos.delivery_app_id'),
            ],
        ]);
    }

    public function phoneCodes()
    {
        $codes = Country::query()
            ->pluck('phonecode')
            ->filter()
            ->unique()
            ->values();

        return response()->json($codes);
    }

    public function customers(Request $request)
    {
        $search = trim((string) $request->get('search', ''));

        $query = Customer::query()->select('id', 'name', 'email', 'phone', 'phone_code', 'delivery_address');

        if ($search !== '') {
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('name', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        return response()->json(
            $query->latest('id')
                ->limit(20)
                ->get()
                ->map(fn($customer) => [
                    'id' => (int) $customer->id,
                    'name' => (string) ($customer->name ?? ''),
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                    'phone_code' => $customer->phone_code,
                    'address' => $customer->delivery_address,
                    'delivery_address' => $customer->delivery_address,
                ])->values()
        );
    }

    public function storeCustomer(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'name' => ['required', 'string', 'max:191'],
            'phone' => ['required', 'string', 'max:50'],
            'phone_code' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:191'],
            'address' => ['nullable', 'string'],
        ]);

        if (!empty($validated['customer_id'])) {
            $customer = Customer::query()->findOrFail((int) $validated['customer_id']);
            $customer->update([
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'phone_code' => $validated['phone_code'],
                'email' => $validated['email'] ?? null,
                'delivery_address' => $validated['address'] ?? null,
            ]);
        } else {
            $customer = Customer::query()->updateOrCreate(
                [
                    'phone' => $validated['phone'],
                    'phone_code' => $validated['phone_code'],
                ],
                [
                    'name' => $validated['name'],
                    'email' => $validated['email'] ?? null,
                    'delivery_address' => $validated['address'] ?? null,
                ]
            );
        }

        return response()->json([
            'success' => true,
            'customer' => [
                'id' => (int) $customer->id,
                'name' => (string) ($customer->name ?? ''),
                'email' => $customer->email,
                'phone' => $customer->phone,
                'phone_code' => $customer->phone_code,
                'address' => $customer->delivery_address,
                'delivery_address' => $customer->delivery_address,
            ],
        ]);
    }

    public function extraCharges(string $orderType)
    {
        $slug = strtolower(str_replace(' ', '_', trim($orderType)));

        $query = RestaurantCharge::query()->whereJsonContains('order_types', $slug);

        if (Schema::hasColumn('restaurant_charges', 'is_enabled')) {
            $query->where('is_enabled', true);
        }

        return response()->json(
            $query->orderBy('id')
                ->get(['id', 'charge_name', 'charge_type', 'charge_value'])
                ->map(fn($charge) => [
                    'id' => (int) $charge->id,
                    'charge_name' => (string) $charge->charge_name,
                    'charge_type' => (string) $charge->charge_type,
                    'charge_value' => (float) ($charge->charge_value ?? 0),
                    'amount' => 0,
                ])->values()
        );
    }

    public function tables()
    {
        $branch = branch();
        abort_if(!$branch, 422, 'Branch context is required');

        $currentUserId = (int) auth()->id();
        $isAdmin = user_can('Manage Settings') || user_can('Manage Order') || user_can('Manage Table');

        $tables = Table::query()
            ->with(['area:id,area_name', 'tableSession.lockedByUser:id,name', 'activeOrder:id,table_id'])
            ->where('branch_id', $branch->id)
            ->orderBy('table_code')
            ->get()
            ->map(function ($table) use ($currentUserId) {
                $session = $table->tableSession;
                $isLocked = $session ? $session->isLocked() : false;
                $lockedByCurrentUser = $isLocked && (int) ($session->locked_by_user_id ?? 0) === $currentUserId;
                $isRunning = (bool) $table->activeOrder;

                return [
                    'id' => (int) $table->id,
                    'table_code' => (string) $table->table_code,
                    'status' => (string) ($table->status ?? 'active'),
                    'active_order_id' => $table->activeOrder ? (int) $table->activeOrder->id : null,
                    'available_status' => $isRunning ? 'running' : (string) ($table->available_status ?? 'available'),
                    'area_id' => (int) ($table->area_id ?? 0),
                    'area_name' => (string) ($table->area?->area_name ?? 'Unknown Area'),
                    'seating_capacity' => (int) ($table->seating_capacity ?? 0),
                    'is_locked' => $isLocked,
                    'is_locked_by_current_user' => $lockedByCurrentUser,
                    'is_locked_by_other_user' => $isLocked && !$lockedByCurrentUser,
                    'locked_by_user_name' => $session?->lockedByUser?->name,
                    'locked_at' => $session?->locked_at?->format('H:i'),
                ];
            })->values();

        return response()->json([
            'tables' => $tables,
            'is_admin' => (bool) $isAdmin,
        ]);
    }

    public function updateOrderWaiter(Request $request, int $id)
    {
        abort_if(!in_array('Order', restaurant_modules()) || !user_can('Update Order'), 403);

        $validated = $request->validate([
            'waiter_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $branch = branch();
        abort_if(!$branch, 422, 'Branch context is required');

        $order = Order::query()
            ->where('id', $id)
            ->where('branch_id', $branch->id)
            ->firstOrFail();

        $order->update([
            'waiter_id' => $validated['waiter_id'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => __('messages.waiterUpdated'),
            'data' => [
                'order_id' => (int) $order->id,
                'waiter_id' => $order->waiter_id ? (int) $order->waiter_id : null,
            ],
        ]);
    }

    public function updateOrderStatus(Request $request, int $id)
    {
        $validated = $request->validate([
            'order_status' => [
                'required',
                'string',
                Rule::in(array_map(fn($status) => $status->value, OrderStatus::cases())),
            ],
            'cancel_reason_id' => ['nullable', 'integer', 'exists:kot_cancel_reasons,id'],
            'cancel_reason_text' => ['nullable', 'string', 'max:500'],
        ]);

        $branch = branch();
        abort_if(!$branch, 422, 'Branch context is required');

        $order = Order::query()
            ->where('id', $id)
            ->where('branch_id', $branch->id)
            ->firstOrFail();

        $requiredPermission = $validated['order_status'] === OrderStatus::CANCELLED->value ? 'Delete Order' : 'Update Order';
        abort_if(!in_array('Order', restaurant_modules()) || !user_can($requiredPermission), 403);

        $allowedStatuses = match ((string) ($order->order_type ?? 'dine_in')) {
            'delivery' => [
                OrderStatus::PLACED->value,
                OrderStatus::CONFIRMED->value,
                OrderStatus::PREPARING->value,
                OrderStatus::FOOD_READY->value,
                OrderStatus::OUT_FOR_DELIVERY->value,
                OrderStatus::DELIVERED->value,
                OrderStatus::CANCELLED->value,
            ],
            'pickup' => [
                OrderStatus::PLACED->value,
                OrderStatus::CONFIRMED->value,
                OrderStatus::PREPARING->value,
                OrderStatus::FOOD_READY->value,
                OrderStatus::READY_FOR_PICKUP->value,
                OrderStatus::DELIVERED->value,
                OrderStatus::CANCELLED->value,
            ],
            default => [
                OrderStatus::PLACED->value,
                OrderStatus::CONFIRMED->value,
                OrderStatus::PREPARING->value,
                OrderStatus::FOOD_READY->value,
                OrderStatus::SERVED->value,
                OrderStatus::CANCELLED->value,
            ],
        };

        abort_if(!in_array($validated['order_status'], $allowedStatuses, true), 422, 'Invalid order status for this order type.');

        $nextStatus = OrderStatus::from($validated['order_status']);

        if ($nextStatus === OrderStatus::CANCELLED && empty($validated['cancel_reason_id']) && empty($validated['cancel_reason_text'])) {
            abort(422, __('modules.settings.cancelReasonRequired'));
        }

        $order->update([
            'order_status' => $nextStatus,
            'status' => $nextStatus === OrderStatus::CANCELLED ? 'canceled' : $order->status,
            'cancel_reason_id' => $nextStatus === OrderStatus::CANCELLED ? ($validated['cancel_reason_id'] ?? null) : $order->cancel_reason_id,
            'cancel_reason_text' => $nextStatus === OrderStatus::CANCELLED ? ($validated['cancel_reason_text'] ?? null) : $order->cancel_reason_text,
        ]);

        if ($nextStatus === OrderStatus::CANCELLED && $order->table_id) {
            Table::query()->where('id', $order->table_id)->update(['available_status' => 'available']);
        }

        if ($nextStatus === OrderStatus::CONFIRMED) {
            $order->kot()
                ->where('status', 'pending')
                ->update(['status' => 'in_kitchen']);
        }
        return response()->json([
            'success' => true,
            'message' => __('messages.updateSuccess'),
            'data' => [
                'order_id' => (int) $order->id,
                'order_status' => $order->order_status?->value ?? (string) ($order->order_status ?? ''),
            ],
        ]);
    }

    public function updateOrderDeliveryExecutive(Request $request, int $id)
    {
        abort_if(!in_array('Order', restaurant_modules()) || !user_can('Update Order'), 403);

        $validated = $request->validate([
            'delivery_executive_id' => ['nullable', 'integer', 'exists:delivery_executives,id'],
        ]);

        $branch = branch();
        abort_if(!$branch, 422, 'Branch context is required');

        $order = Order::query()
            ->where('id', $id)
            ->where('branch_id', $branch->id)
            ->firstOrFail();

        $deliveryExecutiveId = $validated['delivery_executive_id'] ?? null;

        if ($deliveryExecutiveId) {
            $isValidExecutive = DeliveryExecutive::query()
                ->where('id', $deliveryExecutiveId)
                ->where('status', 'available')
                ->exists();

            abort_if(!$isValidExecutive, 422, 'Selected delivery executive is not available.');
        }

        $order->update([
            'delivery_executive_id' => $deliveryExecutiveId,
        ]);

        return response()->json([
            'success' => true,
            'message' => __('messages.deliveryExecutiveAssigned'),
            'data' => [
                'order_id' => (int) $order->id,
                'delivery_executive_id' => $order->delivery_executive_id ? (int) $order->delivery_executive_id : null,
            ],
        ]);
    }

    public function updateOrderDeliveryFee(Request $request, int $id)
    {
        abort_if(!in_array('Order', restaurant_modules()) || !user_can('Update Order'), 403);

        $validated = $request->validate([
            'delivery_fee' => ['nullable', 'numeric', 'min:0'],
        ]);

        $branch = branch();
        abort_if(!$branch, 422, 'Branch context is required');

        $order = Order::query()
            ->where('id', $id)
            ->where('branch_id', $branch->id)
            ->firstOrFail();

        $order->update([
            'delivery_fee' => (float) ($validated['delivery_fee'] ?? 0),
        ]);

        return response()->json([
            'success' => true,
            'message' => __('messages.updateSuccess'),
            'data' => [
                'order_id' => (int) $order->id,
                'delivery_fee' => (float) ($order->delivery_fee ?? 0),
            ],
        ]);
    }

    /**
     * Reduce (decrement) the quantity of a KOT item.
     * If new_quantity <= 0, delegates to actual deletion (removeKotItem).
     * Logs a quantity_updated entry to KotItemAdjustment.
     */
    public function reduceKotItem(Request $request, int $orderId, int $kotItemId)
    {
        abort_if(!in_array('Order', restaurant_modules()), 403);
        abort_if(!user_can('Delete KOT Item'), 403);

        $validated = $request->validate([
            'new_quantity' => ['required', 'integer', 'min:0'],
            'reason'       => ['required', 'string', 'min:3'],
        ]);

        $branch = branch();
        abort_if(!$branch, 422, 'Branch context is required');

        $order = Order::query()
            ->with(['kot.items'])
            ->where('id', $orderId)
            ->where('branch_id', $branch->id)
            ->firstOrFail();

        /** @var \App\Models\KotItem $kotItem */
        $kotItem = \App\Models\KotItem::query()
            ->with(['kot', 'menuItem', 'menuItemVariation.menuItem'])
            ->whereHas('kot', fn($q) => $q->where('order_id', $order->id))
            ->where('id', $kotItemId)
            ->firstOrFail();

        $newQuantity  = (int) $validated['new_quantity'];
        $reason       = $validated['reason'];
        $quantityBefore = (int) $kotItem->quantity;

        // If reducing to 0 or below — treat as a full delete
        if ($newQuantity <= 0) {
            // Re-use removeKotItem logic inline
            \App\Support\KotAdjustmentLogger::log($kotItem, 'deleted', $reason, $quantityBefore, 0);

            $orderItemQuery = \App\Models\OrderItem::query()
                ->where('order_id', $order->id)
                ->where('menu_item_id', $kotItem->menu_item_id)
                ->where('quantity', $kotItem->quantity);

            if ($kotItem->menu_item_variation_id) {
                $orderItemQuery->where('menu_item_variation_id', $kotItem->menu_item_variation_id);
            } else {
                $orderItemQuery->whereNull('menu_item_variation_id');
            }

            if ($kotItem->combo_pack_id) {
                $orderItemQuery->where('is_combo_item', true)->where('combo_pack_id', $kotItem->combo_pack_id);
            } else {
                $orderItemQuery->where(fn($q) => $q->where('is_combo_item', false)->orWhereNull('is_combo_item'))
                    ->whereNull('combo_pack_id');
            }

            $matched = $orderItemQuery->orderBy('id')->first();
            if ($matched) {
                $matched->modifierOptions()->detach();
                $matched->delete();
            }

            $kotItem->modifierOptions()->detach();
            $kotItem->delete();

            $kot = $kotItem->kot;
            $kot->refresh();
            if ($kot->items()->count() === 0) {
                $kot->delete();
            }
        } else {
            // Log quantiy_updated
            \App\Support\KotAdjustmentLogger::log($kotItem, 'quantity_updated', $reason, $quantityBefore, $newQuantity);

            // Update KotItem quantity
            $kotItem->update(['quantity' => $newQuantity]);

            // Update matching OrderItem quantity + amount
            $orderItemQuery = \App\Models\OrderItem::query()
                ->where('order_id', $order->id)
                ->where('menu_item_id', $kotItem->menu_item_id)
                ->where('quantity', $quantityBefore); // match old qty

            if ($kotItem->menu_item_variation_id) {
                $orderItemQuery->where('menu_item_variation_id', $kotItem->menu_item_variation_id);
            } else {
                $orderItemQuery->whereNull('menu_item_variation_id');
            }

            if ($kotItem->combo_pack_id) {
                $orderItemQuery->where('is_combo_item', true)->where('combo_pack_id', $kotItem->combo_pack_id);
            } else {
                $orderItemQuery->where(fn($q) => $q->where('is_combo_item', false)->orWhereNull('is_combo_item'))
                    ->whereNull('combo_pack_id');
            }

            $matched = $orderItemQuery->orderBy('id')->first();
            if ($matched) {
                $unitPrice = $quantityBefore > 0
                    ? round((float) ($matched->amount ?? 0) / $quantityBefore, 4)
                    : (float) ($matched->price ?? 0);

                $matched->update([
                    'quantity' => $newQuantity,
                    'amount'   => round($unitPrice * $newQuantity, 2),
                ]);
            }
        }

        // Recalculate order totals
        $remainingItems = $order->items()->get();
        $subtotal = $remainingItems->sum(fn($i) => (float) ($i->amount ?? 0));

        $totalTax = 0.0;
        if (($order->tax_mode ?? 'item') === 'order') {
            $taxes = \App\Models\Tax::query()->select('id', 'tax_percent')->get();
            foreach ($taxes as $tax) {
                $totalTax += $subtotal * ((float) $tax->tax_percent / 100);
            }
        }

        $total = round($subtotal + $totalTax - (float) ($order->discount_amount ?? 0), 2);
        $order->update([
            'sub_total'        => round($subtotal, 2),
            'total'            => max(0, $total),
            'total_tax_amount' => round($totalTax, 2),
        ]);

        // Handle fully empty order
        $allKotsGone  = !$order->kot()->exists();
        $noOrderItems = $order->items()->count() === 0;

        if ($allKotsGone && $noOrderItems) {
            $hasAdjustments = \App\Models\KotItemAdjustment::where('order_id', $order->id)->exists();
            if ($hasAdjustments) {
                $order->update([
                    'status'       => 'canceled',
                    'order_status' => \App\Enums\OrderStatus::CANCELLED,
                    'sub_total'    => 0,
                    'total'        => 0,
                ]);
            } else {
                if ($order->table_id) {
                    Table::query()->where('id', $order->table_id)->update(['available_status' => 'available']);
                }
                $order->delete();
            }

            return response()->json([
                'success' => true,
                'message' => 'KOT item updated. Order has no remaining items.',
                'data'    => ['order_cancelled_or_deleted' => true],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => $newQuantity <= 0 ? 'KOT item removed successfully.' : 'KOT item quantity updated.',
            'data'    => [
                'order_id'                => (int) $order->id,
                'sub_total'               => (float) $order->sub_total,
                'total'                   => (float) $order->total,
                'order_cancelled_or_deleted' => false,
            ],
        ]);
    }

    public function removeKotItem(Request $request, int $orderId, int $kotItemId)
    {
        abort_if(!in_array('Order', restaurant_modules()), 403);
        abort_if(!user_can('Delete KOT Item') && !user_can('Update Order'), 403);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:3'],
        ]);

        $branch = branch();
        abort_if(!$branch, 422, 'Branch context is required');

        $order = Order::query()
            ->with(['kot.items'])
            ->where('id', $orderId)
            ->where('branch_id', $branch->id)
            ->firstOrFail();

        /** @var \App\Models\KotItem|null $kotItem */
        $kotItem = \App\Models\KotItem::query()
            ->with(['kot', 'menuItem', 'menuItemVariation.menuItem'])
            ->whereHas('kot', fn($q) => $q->where('order_id', $order->id))
            ->where('id', $kotItemId)
            ->firstOrFail();

        $kot = $kotItem->kot;
        $quantityBefore = (int) $kotItem->quantity;

        // Log the adjustment (mirrors KotAdjustmentLogger::log)
        \App\Support\KotAdjustmentLogger::log(
            $kotItem,
            'deleted',
            $validated['reason'],
            $quantityBefore,
            0
        );

        // Remove corresponding order_items row (mirror legacy deletePersistedOrderItemForKotLine)
        $orderItemQuery = \App\Models\OrderItem::query()
            ->where('order_id', $order->id)
            ->where('menu_item_id', $kotItem->menu_item_id)
            ->where('quantity', $kotItem->quantity);

        if ($kotItem->menu_item_variation_id) {
            $orderItemQuery->where('menu_item_variation_id', $kotItem->menu_item_variation_id);
        } else {
            $orderItemQuery->whereNull('menu_item_variation_id');
        }

        if ($kotItem->combo_pack_id) {
            $orderItemQuery->where('is_combo_item', true)->where('combo_pack_id', $kotItem->combo_pack_id);
        } else {
            $orderItemQuery->where(fn($q) => $q->where('is_combo_item', false)->orWhereNull('is_combo_item'))
                ->whereNull('combo_pack_id');
        }

        $matchedOrderItem = $orderItemQuery->orderBy('id')->first();
        if ($matchedOrderItem) {
            $matchedOrderItem->modifierOptions()->detach();
            $matchedOrderItem->delete();
        }

        // Delete the KotItem
        $kotItem->modifierOptions()->detach();
        $kotItem->delete();

        // Delete the KOT if it has no items left
        $kot->refresh();
        $kotIsEmpty = $kot->items()->count() === 0;
        if ($kotIsEmpty) {
            $kot->delete();
        }

        // Recalculate order totals from remaining order_items
        $remainingItems = $order->items()->get();
        $subtotal = $remainingItems->sum(fn($i) => (float) ($i->amount ?? 0));

        // Recalculate taxes (order-level tax mode)
        $totalTax = 0.0;
        if (($order->tax_mode ?? 'item') === 'order') {
            $taxes = \App\Models\Tax::query()->select('id', 'tax_percent')->get();
            foreach ($taxes as $tax) {
                $totalTax += $subtotal * ((float) $tax->tax_percent / 100);
            }
        }

        $total = round($subtotal + $totalTax - (float) ($order->discount_amount ?? 0), 2);

        $order->update([
            'sub_total' => round($subtotal, 2),
            'total' => max(0, $total),
            'total_tax_amount' => round($totalTax, 2),
        ]);

        // If the order has no items left, cancel it (preserve audit trail) or delete it
        $allKotsGone = !$order->kot()->exists();
        $noOrderItems = $order->items()->count() === 0;

        if ($allKotsGone && $noOrderItems) {
            $hasAdjustments = \App\Models\KotItemAdjustment::where('order_id', $order->id)->exists();

            if ($hasAdjustments) {
                $order->update([
                    'status' => 'canceled',
                    'order_status' => \App\Enums\OrderStatus::CANCELLED,
                    'sub_total' => 0,
                    'total' => 0,
                ]);
            } else {
                if ($order->table_id) {
                    Table::query()->where('id', $order->table_id)->update(['available_status' => 'available']);
                }
                $order->delete();
            }

            return response()->json([
                'success' => true,
                'message' => 'KOT item removed. Order has no remaining items.',
                'data' => ['order_cancelled_or_deleted' => true],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'KOT item removed successfully.',
            'data' => [
                'order_id' => (int) $order->id,
                'sub_total' => (float) $order->sub_total,
                'total' => (float) $order->total,
                'order_cancelled_or_deleted' => false,
            ],
        ]);
    }

    public function deleteOrder(int $id)
    {
        abort_if(!in_array('Order', restaurant_modules()) || !user_can('Delete Order'), 403);

        $branch = branch();
        abort_if(!$branch, 422, 'Branch context is required');

        $order = Order::query()
            ->where('id', $id)
            ->where('branch_id', $branch->id)
            ->firstOrFail();

        if ($order->table_id) {
            Table::query()->where('id', $order->table_id)->update(['available_status' => 'available']);
        }

        // Mirror legacy delete behavior by removing all KOT rows and then deleting the order.
        Kot::query()->where('order_id', $order->id)->delete();
        $order->delete();

        return response()->json([
            'success' => true,
            'message' => __('messages.orderDeleted'),
            'data' => [
                'order_id' => (int) $id,
            ],
        ]);
    }

    public function reservationsToday()
    {
        $branch = branch();
        abort_if(!$branch, 422, 'Branch context is required');

        $rows = Reservation::query()
            ->with('table:id,table_code,branch_id')
            ->where('branch_id', $branch->id)
            ->whereDate('reservation_date_time', now()->toDateString())
            ->whereIn('reservation_status', ['Confirmed', 'Checked_In'])
            ->orderBy('reservation_date_time')
            ->get();

        return response()->json(
            $rows->map(fn($reservation) => [
                'id' => (int) $reservation->id,
                'table_code' => (string) ($reservation->table?->table_code ?? '-'),
                'party_size' => (int) ($reservation->party_size ?? 0),
                'time' => optional($reservation->reservation_date_time)->format('H:i'),
            ])->values()
        );
    }

    public function unlockTable(int $id)
    {
        $table = Table::query()->where('branch_id', branch()->id)->findOrFail($id);

        $userId = (int) auth()->id();
        $forceUnlock = (bool) (user_can('Manage Settings') || user_can('Manage Order') || user_can('Manage Table'));

        $result = $table->unlock($userId, $forceUnlock);

        return response()->json($result, ($result['success'] ?? false) ? 200 : 422);
    }
}
