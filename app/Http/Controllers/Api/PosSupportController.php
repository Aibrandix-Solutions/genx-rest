<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Events\KotUpdated;
use App\Http\Controllers\Controller;
use App\Models\ComboPack;
use App\Models\Country;
use App\Models\Customer;
use App\Models\DeliveryExecutive;
use App\Models\DeliveryPlatform;
use App\Models\Kot;
use App\Models\KotCancelReason;
use App\Models\KotItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderType;
use App\Models\Reservation;
use App\Models\RestaurantCharge;
use App\Models\Table;
use App\Models\User;
use App\Scopes\BranchScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class PosSupportController extends Controller
{
    public function getOrderNumber()
    {
        abort_if(! in_array('Order', restaurant_modules()) || ! user_can('Create Order'), 403);

        $branch = branch();
        abort_if(! $branch, 422, 'Branch context is required');

        $numberData = Order::generateOrderNumber($branch);
        $rawNumber = (string) ($numberData['order_number'] ?? '');
        $formatted = (string) ($numberData['formatted_order_number'] ?? ('Order #'.$rawNumber));

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

    /**
     * Priced combo lines for Vue POS (order type + optional delivery app), mirroring
     * Livewire Pos::addComboToCart price context before items are added to the cart.
     */
    public function previewComboPack(Request $request, int $id)
    {
        abort_if(! in_array('Order', restaurant_modules()) || ! user_can('Create Order'), 403);

        $branch = branch();
        abort_if(! $branch, 422, 'Branch context is required');

        $validated = $request->validate([
            'order_type_id' => ['nullable', 'integer', 'exists:order_types,id'],
            'delivery_app_id' => ['nullable'],
        ]);

        $combo = ComboPack::query()
            ->where('branch_id', $branch->id)
            ->with(['comboPackItems.menuItem', 'comboPackItems.menuItemVariation'])
            ->find($id);

        abort_if(! $combo, 404, 'Combo pack not found');

        if (! $combo->isAvailable()) {
            return response()->json([
                'success' => false,
                'message' => __('modules.combo.comboNotAvailable'),
            ], 422);
        }

        $orderTypeId = isset($validated['order_type_id']) ? (int) $validated['order_type_id'] : null;
        $deliveryAppId = null;

        if ($orderTypeId > 0) {
            $orderType = OrderType::query()->find($orderTypeId);
            $slug = strtolower((string) ($orderType?->slug ?? ''));

            if ($slug === 'delivery') {
                $raw = $validated['delivery_app_id'] ?? null;
                if ($raw !== null && $raw !== '' && $raw !== 'default') {
                    $candidate = (int) $raw;
                    $platform = DeliveryPlatform::query()
                        ->where('id', $candidate)
                        ->where('is_active', true)
                        ->first();

                    if ($platform) {
                        $deliveryAppId = (int) $platform->id;
                    }
                }
            }
        }

        foreach ($combo->comboPackItems as $comboItem) {
            $comboItem->menuItem?->setPriceContext($orderTypeId, $deliveryAppId);
            if ($comboItem->menuItemVariation) {
                $comboItem->menuItemVariation->setPriceContext($orderTypeId, $deliveryAppId);
            }
        }

        $priced = $combo->calculateComboItemPrices($orderTypeId, $deliveryAppId);

        $lines = collect($priced)->map(function (array $row) {
            $ci = $row['combo_item'];
            $mid = (int) $ci->menu_item_id;
            $vid = $ci->menu_item_variation_id ? (int) $ci->menu_item_variation_id : null;
            $qty = (int) $ci->quantity;
            $unitPrice = (float) ($row['price'] ?? 0);
            $origUnit = (float) ($row['original_price'] ?? $unitPrice);

            return [
                'menu_item_id' => $mid,
                'menu_item_variation_id' => $vid,
                'qty' => $qty,
                'unit_price' => round($unitPrice, 2),
                'original_unit_price' => round($origUnit, 2),
                'combo_original_unit_price' => round($origUnit, 2),
                'combo_discount_per_unit' => round(max(0, $origUnit - $unitPrice), 2),
                'item_name' => (string) ($ci->menuItem?->item_name ?? ''),
                'variation_name' => (string) ($ci->menuItemVariation?->variation ?? ''),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'combo_pack_id' => (int) $combo->id,
            'name' => (string) $combo->getTranslation('name', app()->getLocale()),
            'lines' => $lines,
        ]);
    }

    public function cancelReasons()
    {
        abort_if(! in_array('Order', restaurant_modules()), 403);

        $branch = branch();
        abort_if(! $branch, 422, 'Branch context is required');

        return response()->json(
            KotCancelReason::query()
                ->where('restaurant_id', $branch->restaurant_id)
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
                ->map(fn ($platform) => [
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

        if (! $restaurant || ! $branch) {
            return response()->json([]);
        }

        return response()->json(
            User::withoutGlobalScope(BranchScope::class)
                ->where(function ($query) use ($branch) {
                    $query->where('branch_id', $branch->id)
                        ->orWhereNull('branch_id');
                })
                ->role('waiter_'.$restaurant->id)
                ->where('restaurant_id', $restaurant->id)
                ->select('id', 'name')
                ->orderBy('name')
                ->get()
                ->map(fn ($waiter) => [
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
                'default_order_type_id' => (int) ($user?->default_order_type_id ?? restaurant()->default_order_type_id ?? 0),
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
                $subQuery->where('name', 'like', '%'.$search.'%')
                    ->orWhere('phone', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%');
            });
        }

        return response()->json(
            $query->latest('id')
                ->limit(20)
                ->get()
                ->map(fn ($customer) => [
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

        if (! empty($validated['customer_id'])) {
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
                ->map(fn ($charge) => [
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
        abort_if(! $branch, 422, 'Branch context is required');

        $currentUserId = (int) auth()->id();
        $isAdmin = user_can('Manage Settings') || user_can('Manage Order') || user_can('Manage Table');

        $tables = Table::query()
            ->with([
                'area:id,area_name',
                'tableSession.lockedByUser:id,name',
                'activeOrder:id,table_id,order_number,formatted_order_number,status,waiter_id',
                'activeOrder.waiter:id,name',
            ])
            ->where('branch_id', $branch->id)
            ->orderBy('table_code')
            ->get()
            ->map(function ($table) use ($currentUserId) {
                $session = $table->tableSession;
                $isLocked = $session ? $session->isLocked() : false;
                $lockedByCurrentUser = $isLocked && (int) ($session->locked_by_user_id ?? 0) === $currentUserId;
                $isRunning = (bool) $table->activeOrder;

                // Derive the effective available_status robustly so the grid colour
                // always matches reality:
                //   - If an active order exists → 'running' (authoritative).
                //   - Else if the stored column says 'reserved' → keep 'reserved'.
                //   - Otherwise → 'available' (never trust a stale 'running' on a table
                //     whose order has already been billed/canceled/deleted).
                $storedStatus = (string) ($table->available_status ?? 'available');
                if ($isRunning) {
                    $effectiveStatus = 'running';
                } elseif ($storedStatus === 'reserved') {
                    $effectiveStatus = 'reserved';
                } else {
                    $effectiveStatus = 'available';
                }

                // Build active-order details for UI enrichment
                $activeOrder = $table->activeOrder;
                $orderNumber = null;
                if ($activeOrder) {
                    $orderNumber = $activeOrder->formatted_order_number
                        ?: ($activeOrder->order_number ? '#' . $activeOrder->order_number : null);
                }

                return [
                    'id' => (int) $table->id,
                    'table_code' => (string) $table->table_code,
                    'status' => (string) ($table->status ?? 'active'),
                    'active_order_id' => $activeOrder ? (int) $activeOrder->id : null,
                    'active_order_number' => $orderNumber,
                    'active_order_status' => $activeOrder ? (string) $activeOrder->status : null,
                    'active_order_waiter' => $activeOrder?->waiter?->name,
                    'available_status' => $effectiveStatus,
                    'area_id' => (int) ($table->area_id ?? 0),
                    'area_name' => (string) ($table->area?->area_name ?? 'Unknown Area'),
                    'seating_capacity' => (int) ($table->seating_capacity ?? 0),
                    'is_locked' => $isLocked,
                    'is_locked_by_current_user' => $lockedByCurrentUser,
                    'is_locked_by_other_user' => $isLocked && ! $lockedByCurrentUser,
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
        abort_if(! in_array('Order', restaurant_modules()) || ! user_can('Update Order'), 403);

        $validated = $request->validate([
            'waiter_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $branch = branch();
        abort_if(! $branch, 422, 'Branch context is required');

        $restaurant = restaurant();
        abort_if(! $restaurant, 422, 'Restaurant context is required');

        // Constrain assignment to branch-scoped waiters of this restaurant
        // (mirrors PosBootstrapService::freshDeliveryExecutives/waiters loader).
        if (! empty($validated['waiter_id'])) {
            $isValidWaiter = User::query()
                ->where('id', (int) $validated['waiter_id'])
                ->where('restaurant_id', $restaurant->id)
                ->where(function ($q) use ($branch) {
                    $q->where('branch_id', $branch->id)->orWhereNull('branch_id');
                })
                ->role('waiter_'.$restaurant->id)
                ->exists();

            abort_if(! $isValidWaiter, 422, 'Selected waiter is not assignable to this branch.');
        }

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

    /**
     * Attach / detach / swap the customer on an existing POS order.
     *
     * Legacy parity (pos/order_detail.blade.php + pos/kot_items.blade.php):
     * the "Update Customer Details" / "Remove Customer" affordances on a
     * linked order persist to the Order immediately. The Vue POS calls this
     * endpoint from `handleRemoveCustomer` / `handleSaveCustomer`.
     */
    public function updateOrderCustomer(Request $request, int $id)
    {
        abort_if(! in_array('Order', restaurant_modules()), 403);

        $validated = $request->validate([
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
        ]);

        $branch = branch();
        abort_if(! $branch, 422, 'Branch context is required');

        $order = Order::query()
            ->where('id', $id)
            ->where('branch_id', $branch->id)
            ->firstOrFail();

        $isBilledOrPaid = in_array((string) $order->status, ['billed', 'paid', 'payment_due'], true);
        abort_if($isBilledOrPaid && ! user_can('Edit Billed Order'), 403);
        abort_if(! $isBilledOrPaid && ! user_can('Update Order'), 403);

        // Do not allow removing the customer from an order that still carries
        // an outstanding "due" balance — only registered customers may have
        // dues (mirrors Order::canRecordDueBalance()).
        $customerId = $validated['customer_id'] ?? null;
        if ($customerId === null && $order->status === 'payment_due') {
            return response()->json([
                'success' => false,
                'message' => __('modules.order.customerRequiredForDuePayment'),
            ], 422);
        }

        $order->update([
            'customer_id' => $customerId,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'order_id' => (int) $order->id,
                'customer_id' => $order->customer_id ? (int) $order->customer_id : null,
            ],
        ]);
    }

    public function updateOrderItemNote(Request $request, int $id)
    {
        abort_if(! in_array('Order', restaurant_modules()), 403);

        $validated = $request->validate([
            'note' => ['nullable', 'string'],
            'kot_item_id' => ['nullable', 'integer'],
            'order_item_id' => ['nullable', 'integer'],
        ]);

        abort_if(empty($validated['kot_item_id']) && empty($validated['order_item_id']), 422, 'Order item reference is required.');

        $branch = branch();
        abort_if(! $branch, 422, 'Branch context is required');

        $order = Order::query()
            ->where('id', $id)
            ->where('branch_id', $branch->id)
            ->firstOrFail();

        $isBilledOrPaid = in_array((string) $order->status, ['billed', 'paid', 'payment_due'], true);
        abort_if($isBilledOrPaid && ! user_can('Edit Billed Order'), 403);
        abort_if(! $isBilledOrPaid && ! user_can('Update Order'), 403);

        $note = trim((string) ($validated['note'] ?? ''));
        $note = $note !== '' ? $note : null;

        $kotItem = null;
        if (! empty($validated['kot_item_id'])) {
            $kotItem = KotItem::query()
                ->where('id', (int) $validated['kot_item_id'])
                ->whereHas('kot', fn ($query) => $query->where('order_id', $order->id))
                ->firstOrFail();

            $kotItem->update(['note' => $note]);
            event(new KotUpdated($kotItem->kot));
        }

        if (! empty($validated['order_item_id'])) {
            OrderItem::query()
                ->where('id', (int) $validated['order_item_id'])
                ->where('order_id', $order->id)
                ->update(['note' => $note]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'order_id' => (int) $order->id,
                'kot_item_id' => $kotItem ? (int) $kotItem->id : null,
                'order_item_id' => ! empty($validated['order_item_id']) ? (int) $validated['order_item_id'] : null,
                'note' => $note,
            ],
        ]);
    }

    /**
     * Attach / move / detach a table on an existing POS order.
     *
     * Legacy parity (Pos.php::setTable): once an order has been created, picking
     * a different table updates `orders.table_id` immediately and releases the
     * previous table's `available_status` ("available"), marking the new one
     * "running" when the order is same-day. The Vue POS calls this from
     * `applySelectedTable`/`handleConfirmTableChange` so the order-table link
     * persists without waiting for the next KOT save.
     */
    public function updateOrderTable(Request $request, int $id)
    {
        abort_if(! in_array('Order', restaurant_modules()) || ! user_can('Update Order'), 403);

        $validated = $request->validate([
            'table_id' => ['nullable', 'integer'],
        ]);

        $branch = branch();
        abort_if(! $branch, 422, 'Branch context is required');

        $order = Order::query()
            ->where('id', $id)
            ->where('branch_id', $branch->id)
            ->firstOrFail();

        $newTableId = null;
        $newTable = null;
        if (! empty($validated['table_id'])) {
            $newTable = Table::query()
                ->where('id', (int) $validated['table_id'])
                ->where('branch_id', $branch->id)
                ->first();

            abort_if(! $newTable, 422, 'Selected table is not available in this branch.');

            // Another order already occupies the target table — legacy Pos.php
            // never lets two orders share a running table, so mirror that guard.
            $conflict = Order::query()
                ->where('branch_id', $branch->id)
                ->where('table_id', $newTable->id)
                ->whereIn('status', ['kot', 'billed'])
                ->where('id', '!=', $order->id)
                ->exists();

            if ($conflict) {
                return response()->json([
                    'success' => false,
                    'message' => __('messages.tableAlreadyRunning', ['table' => $newTable->table_code])
                        ?: 'This table already has an active order.',
                ], 422);
            }

            $newTableId = (int) $newTable->id;
        }

        $previousTableId = $order->table_id ? (int) $order->table_id : null;

        $order->update(['table_id' => $newTableId]);

        // Mirror legacy tables.available_status transitions on switch:
        //   - previous table → 'available' (if it was held by this order)
        //   - new table → 'running' when the order is still active (kot/billed)
        if ($previousTableId && $previousTableId !== $newTableId) {
            Table::query()
                ->where('id', $previousTableId)
                ->where('branch_id', $branch->id)
                ->update(['available_status' => 'available']);

            // Also release any session lock the previous table was holding for this
            // order / cashier so the freed table becomes immediately selectable again
            // (OrderObserver::updated only fires on status changes, not on table_id
            // changes, so we have to do this manually here).
            $previousTable = Table::with('tableSession')->find($previousTableId);
            if ($previousTable && $previousTable->tableSession) {
                $session = $previousTable->tableSession;
                if ($session->isOrderLock() && (int) ($session->order_id ?? 0) === (int) $order->id) {
                    $previousTable->unlockFromOrder($order->id);
                } elseif ((int) ($session->locked_by_user_id ?? 0) === (int) auth()->id()) {
                    $session->releaseLock();
                }
            }
        }

        // Re-lock the new table for this order when appropriate — the current
        // user already holds a user-lock (TableAssignmentModal called /lock before
        // reaching here), but for kot/billed orders we also want the order-lock so
        // the observer-based unlock-on-bill/cancel path keeps working.
        if ($newTableId) {
            if (in_array($order->status, ['kot', 'billed'], true)) {
                Table::query()
                    ->where('id', $newTableId)
                    ->where('branch_id', $branch->id)
                    ->update(['available_status' => 'running']);

                $restaurant = restaurant();
                if ($restaurant && ($restaurant->enable_table_lock_on_order ?? false)) {
                    $newTableModel = Table::find($newTableId);
                    if ($newTableModel) {
                        $lockUserId = (int) ($order->waiter_id ?? auth()->id());
                        $newTableModel->lockForOrder($lockUserId, (int) $order->id);
                    }
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'order_id' => (int) $order->id,
                'table_id' => $newTableId,
                'table_code' => $newTable?->table_code ? (string) $newTable->table_code : null,
            ],
        ]);
    }

    public function updateOrderStatus(Request $request, int $id)
    {
        $validated = $request->validate([
            'order_status' => [
                'required',
                'string',
                Rule::in(array_map(fn ($status) => $status->value, OrderStatus::cases())),
            ],
            'cancel_reason_id' => ['nullable', 'integer', 'exists:kot_cancel_reasons,id'],
            'cancel_reason_text' => ['nullable', 'string', 'max:500'],
        ]);

        $branch = branch();
        abort_if(! $branch, 422, 'Branch context is required');

        $order = Order::query()
            ->where('id', $id)
            ->where('branch_id', $branch->id)
            ->firstOrFail();

        $requiredPermission = $validated['order_status'] === OrderStatus::CANCELLED->value ? 'Delete Order' : 'Update Order';
        abort_if(! in_array('Order', restaurant_modules()) || ! user_can($requiredPermission), 403);

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

        abort_if(! in_array($validated['order_status'], $allowedStatuses, true), 422, 'Invalid order status for this order type.');

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
        abort_if(! in_array('Order', restaurant_modules()) || ! user_can('Update Order'), 403);

        $validated = $request->validate([
            'delivery_executive_id' => ['nullable', 'integer', 'exists:delivery_executives,id'],
        ]);

        $branch = branch();
        abort_if(! $branch, 422, 'Branch context is required');

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

            abort_if(! $isValidExecutive, 422, 'Selected delivery executive is not available.');
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
        abort_if(! in_array('Order', restaurant_modules()) || ! user_can('Update Order'), 403);

        $validated = $request->validate([
            'delivery_fee' => ['nullable', 'numeric', 'min:0'],
        ]);

        $branch = branch();
        abort_if(! $branch, 422, 'Branch context is required');

        $order = Order::query()
            ->where('id', $id)
            ->where('branch_id', $branch->id)
            ->firstOrFail();

        DB::transaction(function () use ($order, $validated) {
            $order->update([
                'delivery_fee' => (float) ($validated['delivery_fee'] ?? 0),
            ]);
            $this->recomputeOrderFinancialsFromPersistedItems($order->fresh());
        });

        $order->refresh();

        return response()->json([
            'success' => true,
            'message' => __('messages.updateSuccess'),
            'data' => [
                'order_id' => (int) $order->id,
                'delivery_fee' => (float) ($order->delivery_fee ?? 0),
                'sub_total' => (float) ($order->sub_total ?? 0),
                'total' => (float) ($order->total ?? 0),
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
        abort_if(! in_array('Order', restaurant_modules()), 403);
        abort_if(! user_can('Delete KOT Item'), 403);

        $validated = $request->validate([
            'new_quantity' => ['required', 'integer', 'min:0'],
            'reason' => ['required', 'string', 'min:3'],
            'order_item_id' => ['nullable', 'integer', 'exists:order_items,id'],
        ]);

        $branch = branch();
        abort_if(! $branch, 422, 'Branch context is required');

        $order = Order::query()
            ->with(['kot.items'])
            ->where('id', $orderId)
            ->where('branch_id', $branch->id)
            ->firstOrFail();

        /** @var \App\Models\KotItem $kotItem */
        $kotItem = \App\Models\KotItem::query()
            ->with(['kot', 'menuItem', 'menuItemVariation.menuItem', 'modifierOptions'])
            ->whereHas('kot', fn ($q) => $q->where('order_id', $order->id))
            ->where('id', $kotItemId)
            ->firstOrFail();

        $newQuantity = (int) $validated['new_quantity'];
        $reason = $validated['reason'];
        $quantityBefore = (int) $kotItem->quantity;
        $clientOrderItemId = isset($validated['order_item_id']) ? (int) $validated['order_item_id'] : null;

        // If reducing to 0 or below — treat as a full delete
        if ($newQuantity <= 0) {
            // Re-use removeKotItem logic inline
            \App\Support\KotAdjustmentLogger::log($kotItem, 'deleted', $reason, $quantityBefore, 0);

            $matched = $this->findOrderItemMatchingKotItem($order, $kotItem, $clientOrderItemId);
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
            $matched = $this->findOrderItemMatchingKotItem($order, $kotItem, $clientOrderItemId);
            if ($matched) {
                $unitPrice = $quantityBefore > 0
                    ? round((float) ($matched->amount ?? 0) / $quantityBefore, 4)
                    : (float) ($matched->price ?? 0);

                $matched->update([
                    'quantity' => $newQuantity,
                    'amount' => round($unitPrice * $newQuantity, 2),
                ]);
            }
        }

        $this->recomputeOrderFinancialsFromPersistedItems($order->fresh());

        // Handle fully empty order
        $allKotsGone = ! $order->kot()->exists();
        $noOrderItems = $order->items()->count() === 0;

        if ($allKotsGone && $noOrderItems) {
            // Every reduce/remove above logs a KotItemAdjustment, so the order must
            // be preserved as `canceled` for audit; release the table regardless.
            if ($order->table_id) {
                Table::query()->where('id', $order->table_id)->update(['available_status' => 'available']);
            }

            $order->update([
                'status' => 'canceled',
                'order_status' => \App\Enums\OrderStatus::CANCELLED,
                'sub_total' => 0,
                'total' => 0,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'KOT item updated. Order has no remaining items.',
                'data' => ['order_cancelled_or_deleted' => true],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => $newQuantity <= 0 ? 'KOT item removed successfully.' : 'KOT item quantity updated.',
            'data' => [
                'order_id' => (int) $order->id,
                'sub_total' => (float) $order->sub_total,
                'total' => (float) $order->total,
                'order_cancelled_or_deleted' => false,
            ],
        ]);
    }

    public function removeKotItem(Request $request, int $orderId, int $kotItemId)
    {
        abort_if(! in_array('Order', restaurant_modules()), 403);
        abort_if(! user_can('Delete KOT Item') && ! user_can('Update Order'), 403);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:3'],
            'order_item_id' => ['nullable', 'integer', 'exists:order_items,id'],
        ]);

        $branch = branch();
        abort_if(! $branch, 422, 'Branch context is required');

        $order = Order::query()
            ->with(['kot.items'])
            ->where('id', $orderId)
            ->where('branch_id', $branch->id)
            ->firstOrFail();

        $clientOrderItemId = isset($validated['order_item_id']) ? (int) $validated['order_item_id'] : null;

        /** @var \App\Models\KotItem|null $kotItem */
        $kotItem = \App\Models\KotItem::query()
            ->with(['kot', 'menuItem', 'menuItemVariation.menuItem', 'modifierOptions'])
            ->whereHas('kot', fn ($q) => $q->where('order_id', $order->id))
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
        $matchedOrderItem = $this->findOrderItemMatchingKotItem($order, $kotItem, $clientOrderItemId);
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

        $this->recomputeOrderFinancialsFromPersistedItems($order->fresh());

        // If the order has no items left, cancel it (preserve audit trail) or delete it
        $allKotsGone = ! $order->kot()->exists();
        $noOrderItems = $order->items()->count() === 0;

        if ($allKotsGone && $noOrderItems) {
            // Every removeKotItem call logs a KotItemAdjustment above, so the order
            // must be preserved as `canceled` for audit; release the table regardless.
            if ($order->table_id) {
                Table::query()->where('id', $order->table_id)->update(['available_status' => 'available']);
            }

            $order->update([
                'status' => 'canceled',
                'order_status' => \App\Enums\OrderStatus::CANCELLED,
                'sub_total' => 0,
                'total' => 0,
            ]);

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
        abort_if(! in_array('Order', restaurant_modules()) || ! user_can('Delete Order'), 403);

        $branch = branch();
        abort_if(! $branch, 422, 'Branch context is required');

        $order = Order::query()
            ->where('id', $id)
            ->where('branch_id', $branch->id)
            ->firstOrFail();

        if ($order->table_id) {
            Table::query()->where('id', $order->table_id)->update(['available_status' => 'available']);
        }

        // Mirror legacy delete: detach modifier pivots on each KotItem, then remove rows.
        $kotIds = Kot::query()->where('order_id', $order->id)->pluck('id');
        if ($kotIds->isNotEmpty()) {
            $kotItemRows = \App\Models\KotItem::query()->whereIn('kot_id', $kotIds->all())->get();
            foreach ($kotItemRows as $kit) {
                $kit->modifierOptions()->detach();
            }
            \App\Models\KotItem::query()->whereIn('kot_id', $kotIds->all())->delete();
        }
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
        abort_if(! $branch, 422, 'Branch context is required');

        $rows = Reservation::query()
            ->with('table:id,table_code,branch_id')
            ->where('branch_id', $branch->id)
            ->whereDate('reservation_date_time', now()->toDateString())
            ->whereIn('reservation_status', ['Confirmed', 'Checked_In'])
            ->orderBy('reservation_date_time')
            ->get();

        return response()->json(
            $rows->map(fn ($reservation) => [
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

        // After a successful unlock, make sure the stored available_status is correct.
        // Legacy left this column alone on unlock, which produced "stuck running" tiles
        // whenever an order had been deleted/billed without triggering the observer
        // path (e.g. admin cleanup, stale rows migrated from older installs). Reconcile
        // it here: if the table has no active order, it should be 'available' (unless
        // explicitly reserved).
        if (($result['success'] ?? false)) {
            $table->loadMissing('activeOrder:id,table_id');
            if (! $table->activeOrder) {
                $storedStatus = (string) ($table->available_status ?? 'available');
                if ($storedStatus !== 'reserved') {
                    $table->forceFill(['available_status' => 'available'])->saveQuietly();
                }
            }
        }

        return response()->json($result, ($result['success'] ?? false) ? 200 : 422);
    }

    /**
     * Acquire a user (non-order) lock on a table, matching legacy Pos::setTable().
     * Mirrors Table::lockForUser() which:
     *  - honours restaurant->table_lock_timeout_minutes (and disable_table_lock_timeout)
     *  - returns 422 with locked_by/locked_at when another user is holding the table
     * Order locks (created by OrderObserver) are still respected via canBeAccessedByUser().
     */
    public function lockTable(int $id)
    {
        $table = Table::query()->where('branch_id', branch()->id)->findOrFail($id);

        $userId = (int) auth()->id();
        $result = $table->lockForUser($userId);

        return response()->json($result, ($result['success'] ?? false) ? 200 : 422);
    }

    private function recomputeOrderFinancialsFromPersistedItems(Order $order): void
    {
        $order->loadMissing('taxes');
        $remainingItems = $order->items()->get();
        $subtotal = $remainingItems->sum(fn ($i) => (float) ($i->amount ?? 0));
        $extrasTotal = (float) $order->extras()->sum('amount');

        $totalTax = 0.0;
        if (($order->tax_mode ?? 'item') === 'order') {
            $taxPercents = $order->taxes()
                ->join('taxes', 'order_taxes.tax_id', '=', 'taxes.id')
                ->pluck('taxes.tax_percent');
            foreach ($taxPercents as $taxPercent) {
                $totalTax += (($subtotal + $extrasTotal) * ((float) $taxPercent / 100));
            }
        } else {
            $totalTax = (float) $order->items()->sum('tax_amount');
        }

        $total = round(
            $subtotal + $extrasTotal + $totalTax - (float) ($order->discount_amount ?? 0)
                + (($order->order_type ?? null) === 'delivery' ? (float) ($order->delivery_fee ?? 0) : 0),
            2
        );

        $order->update([
            'sub_total' => round($subtotal, 2),
            'total' => max(0, $total),
            'total_tax_amount' => round($totalTax, 2),
        ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, \App\Models\ModifierOption>
     */
    private function modifierOptionsCollectionForSignature(KotItem|OrderItem $row): \Illuminate\Support\Collection
    {
        if ($row->relationLoaded('modifierOptions')) {
            return $row->modifierOptions;
        }

        return $row->modifierOptions()->get();
    }

    private function modifierPivotSignatureForOrderItemMatch(\Illuminate\Support\Collection $options): string
    {
        return $options
            ->mapWithKeys(fn ($o) => [(int) $o->id => (int) ($o->pivot->quantity ?? 1)])
            ->filter(fn ($qty) => (int) $qty > 0)
            ->sortKeys()
            ->map(fn ($qty, $id) => ((int) $id).'x'.((int) $qty))
            ->values()
            ->implode('|');
    }

    private function normalizedNoteForOrderItemMatch(?string $note): string
    {
        return preg_replace('/\s+/', ' ', trim((string) ($note ?? '')));
    }

    private function findOrderItemMatchingKotItem(Order $order, KotItem $kotItem, ?int $clientOrderItemId = null): ?OrderItem
    {
        if ($clientOrderItemId) {
            $byId = OrderItem::query()
                ->where('order_id', $order->id)
                ->where('id', $clientOrderItemId)
                ->first();
            if ($byId && (int) $byId->menu_item_id === (int) $kotItem->menu_item_id) {
                return $byId;
            }
        }

        $kotItem->loadMissing('modifierOptions');
        $targetSig = $this->modifierPivotSignatureForOrderItemMatch(
            $this->modifierOptionsCollectionForSignature($kotItem)
        );
        $targetNote = $this->normalizedNoteForOrderItemMatch($kotItem->note);

        $candidates = OrderItem::query()
            ->where('order_id', $order->id)
            ->where('menu_item_id', $kotItem->menu_item_id)
            ->where('quantity', $kotItem->quantity)
            ->when($kotItem->menu_item_variation_id, function ($q) use ($kotItem) {
                $q->where('menu_item_variation_id', $kotItem->menu_item_variation_id);
            }, function ($q) {
                $q->whereNull('menu_item_variation_id');
            })
            ->when($kotItem->combo_pack_id, function ($q) use ($kotItem) {
                $q->where('is_combo_item', true)->where('combo_pack_id', $kotItem->combo_pack_id);
            }, function ($q) {
                $q->where(fn ($qq) => $qq->where('is_combo_item', false)->orWhereNull('is_combo_item'))
                    ->whereNull('combo_pack_id');
            })
            ->when(
                Schema::hasColumn('order_items', 'combo_instance_key') && Schema::hasColumn('kot_items', 'combo_instance_key'),
                function ($q) use ($kotItem) {
                    $key = $kotItem->combo_instance_key;
                    if ($key === null || $key === '') {
                        $q->where(function ($qq) {
                            $qq->whereNull('combo_instance_key')->orWhere('combo_instance_key', '');
                        });
                    } else {
                        $q->where('combo_instance_key', $key);
                    }
                }
            )
            ->with('modifierOptions')
            ->orderBy('id')
            ->get();

        foreach ($candidates as $oi) {
            if ($this->normalizedNoteForOrderItemMatch($oi->note) !== $targetNote) {
                continue;
            }
            $oiSig = $this->modifierPivotSignatureForOrderItemMatch(
                $this->modifierOptionsCollectionForSignature($oi)
            );
            if ($oiSig === $targetSig) {
                return $oi;
            }
        }

        return null;
    }

    /**
     * Get customer reward points balance and max redeemable for POS.
     *
     * Called when a customer is selected in the Vue POS to populate
     * the redemption modal with correct limits.
     */
    public function customerRewardBalance(Request $request)
    {
        abort_if(! in_array('Order', restaurant_modules()), 403);

        $validated = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'order_subtotal' => ['nullable', 'numeric', 'min:0'],
        ]);

        $restaurant = restaurant();
        abort_if(! $restaurant, 422, 'Restaurant context is required');

        $settings = \App\Models\RewardSetting::getForRestaurant($restaurant->id);

        if (! $settings || ! $settings->enable_reward_point) {
            return response()->json([
                'success' => true,
                'data' => [
                    'enabled' => false,
                    'available_points' => 0,
                    'max_redeemable' => 0,
                    'amount_per_point' => 1,
                    'display_name' => 'Reward',
                    'can_redeem' => false,
                ],
            ]);
        }

        $customer = Customer::findOrFail((int) $validated['customer_id']);
        $balance = \App\Models\RewardBalance::getForCustomer($customer->id, $restaurant->id);
        $availablePoints = $balance->available_points;
        $orderSubtotal = (float) ($validated['order_subtotal'] ?? 0);

        $rewardService = app(\App\Services\RewardPointsService::class);
        $maxRedeemable = $rewardService->calculateMaxRedeemablePoints(
            $customer,
            $restaurant->id,
            $orderSubtotal
        );

        $canRedeem = user_can('Redeem Reward Points') && $availablePoints > 0 && $maxRedeemable > 0;

        return response()->json([
            'success' => true,
            'data' => [
                'enabled' => true,
                'available_points' => $availablePoints,
                'max_redeemable' => $maxRedeemable,
                'amount_per_point' => (float) ($settings->redeem_amount_per_unit_point ?? 1),
                'display_name' => $settings->reward_point_display_name ?? 'Reward',
                'can_redeem' => $canRedeem,
                'minimum_order_total_to_redeem' => (float) ($settings->minimum_order_total_to_redeem ?? 0),
            ],
        ]);
    }
}
