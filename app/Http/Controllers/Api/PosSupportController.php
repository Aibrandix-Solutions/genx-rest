<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Customer;
use App\Models\DeliveryExecutive;
use App\Models\DeliveryPlatform;
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
        abort_if(!in_array('Order', restaurant_modules()) || !user_can('Update Order'), 403);

        $validated = $request->validate([
            'order_status' => [
                'required',
                'string',
                Rule::in(array_map(fn($status) => $status->value, OrderStatus::cases())),
            ],
        ]);

        $branch = branch();
        abort_if(!$branch, 422, 'Branch context is required');

        $order = Order::query()
            ->where('id', $id)
            ->where('branch_id', $branch->id)
            ->firstOrFail();

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

        $order->update([
            'order_status' => $nextStatus,
            'status' => $nextStatus === OrderStatus::CANCELLED ? 'canceled' : $order->status,
        ]);

        if ($nextStatus === OrderStatus::CONFIRMED) {
            $order->kot->each(function ($kot) {
                $kot->update(['status' => 'in_kitchen']);
            });
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
