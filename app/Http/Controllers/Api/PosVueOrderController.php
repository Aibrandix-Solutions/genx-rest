<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Events\SendOrderBillEvent;
use App\Http\Controllers\Controller;
use App\Models\BranchPaymentAccountSetting;
use App\Services\OrderPaymentBalanceSync;
use App\Services\Pos\OrderItemLinePricing;
use App\Models\ComboPack;
use App\Models\Customer;
use App\Models\DeliveryPlatform;
use App\Models\Kot;
use App\Models\KotItem;
use App\Models\KotPlace;
use App\Models\MenuItem;
use App\Models\MenuItemVariation;
use App\Models\ModifierOption;
use App\Models\Order;
use App\Models\OrderExtra;
use App\Models\OrderItem;
use App\Models\OrderTax;
use App\Models\OrderType;
use App\Models\Payment;
use App\Models\RewardSetting;
use App\Models\RewardTransaction;
use App\Models\Table;
use App\Models\TableSession;
use App\Models\Tax;
use App\Models\User;
use App\Services\Pos\BillSecondaryActionResolver;
use App\Services\Pos\PosHotelSupport;
use App\Services\RewardPointsService;
use Modules\Hotel\Entities\Reservation as HotelReservation;
use Modules\Hotel\Services\OrderFolioSettlement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class PosVueOrderController extends Controller
{
    public function show(int $id)
    {
        $canAccess = user_can('View Order') || user_can('Create Order') || user_can('Update Order');
        abort_if(! in_array('Order', restaurant_modules()) || ! $canAccess, 403);

        $branch = branch();
        abort_if(! $branch, 422, 'Branch context is required');

        $order = Order::query()
            ->with([
                'customer:id,name,email,phone,phone_code,delivery_address',
                'items.modifierOptions',
                'items.menuItem',
                'items.menuItemVariation',
                'kot.items.modifierOptions',
                'kot.items.menuItem',
                'kot.items.menuItemVariation',
                'table:id,table_code',
                'waiter:id,name',
                'hotelReservation.room.roomType',
                'hotelReservation.guest',
                'splitOrders.items',
                'payments',
            ])
            ->where('id', $id)
            ->where('branch_id', $branch->id)
            ->firstOrFail();

        // Backfill safety net: older Vue KOT orders could keep an order-lock on
        // table_sessions.order_id while orders.table_id stayed null. Recover the
        // missing link so table badge + orders list are consistent before billing.
        if (empty($order->table_id) && in_array((string) $order->status, ['kot', 'billed'], true)) {
            $lockedTableId = TableSession::query()
                ->where('order_id', $order->id)
                ->where('locked_by_order', true)
                ->whereHas('table', function ($query) use ($branch) {
                    $query->where('branch_id', $branch->id);
                })
                ->value('table_id');

            if ($lockedTableId) {
                $order->update(['table_id' => (int) $lockedTableId]);
                $order->loadMissing('table:id,table_code');
            }
        }

        $resolveUnitPrice = static function ($item): float {
            $unitPrice = (float) ($item->price ?? 0);
            $qty = (int) ($item->quantity ?? 0);
            $amount = (float) ($item->amount ?? 0);

            // Legacy-created rows may store base price in `price` while `amount`
            // includes modifier add-ons. Prefer amount/qty when modifiers exist.
            if (($item->modifierOptions?->count() ?? 0) > 0 && $qty > 0 && $amount > 0) {
                $unitPrice = round($amount / $qty, 2);
            }

            if ($unitPrice <= 0 && $qty > 0 && $amount > 0) {
                $unitPrice = round($amount / $qty, 2);
            }

            if ($unitPrice <= 0) {
                $unitPrice = (float) ($item->menuItemVariation?->price ?? 0);
            }

            return $unitPrice > 0 ? $unitPrice : 0.0;
        };

        $orderItemsSorted = $order->items->sortBy('id')->values();
        $modifierSignatureForRow = static function ($row): string {
            return $row->modifierOptions
                ->mapWithKeys(fn ($opt) => [(int) $opt->id => (int) ($opt->pivot->quantity ?? 1)])
                ->filter(fn ($qty) => (int) $qty > 0)
                ->sortKeys()
                ->map(fn ($qty, $id) => ((int) $id).'x'.((int) $qty))
                ->values()
                ->implode('|');
        };
        $lineMatchKeyForRow = static function ($row) use ($modifierSignatureForRow): string {
            $qty = (int) ($row->quantity ?? $row->qty ?? 0);
            $notePart = preg_replace('/\s+/', ' ', trim((string) ($row->note ?? '')));

            return (int) ($row->combo_pack_id ?? 0)
                .':'.(int) $row->menu_item_id
                .':'.(int) ($row->menu_item_variation_id ?? 0)
                .':'.$modifierSignatureForRow($row)
                .':'.$qty
                .':'.$notePart;
        };
        $packIdsForSlots = $orderItemsSorted->pluck('combo_pack_id')->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();
        $slotCountByPackId = self::comboPackSlotCounts($packIdsForSlots);
        $comboInstanceByOrderItemId = self::comboInstanceKeyMapForRows($orderItemsSorted, $slotCountByPackId);
        $comboNamesByPackId = self::comboPackNamesById($packIdsForSlots);

        $orderItemLineQueues = [];
        foreach ($orderItemsSorted as $oi) {
            $orderItemLineQueues[$lineMatchKeyForRow($oi)][] = $oi;
        }

        $lineQueues = $orderItemLineQueues;
        $orderItemToKotItemMap = [];

        $kots = $order->kot->map(function ($kot) use ($resolveUnitPrice, &$lineQueues, $lineMatchKeyForRow, &$orderItemToKotItemMap) {
            $kotItemsSorted = $kot->items->sortBy('id')->values();
            $kotPackIds = $kotItemsSorted->pluck('combo_pack_id')->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();
            $kotSlotCounts = self::comboPackSlotCounts($kotPackIds);
            $kotComboInstanceByItemId = self::comboInstanceKeyMapForRows($kotItemsSorted, $kotSlotCounts);
            $kotComboNames = self::comboPackNamesById($kotPackIds);

            $kotLines = $kotItemsSorted->map(function ($item) use ($resolveUnitPrice, $kotComboInstanceByItemId, $kotComboNames, &$lineQueues, $lineMatchKeyForRow, &$orderItemToKotItemMap) {
                $comboInstanceKey = $item->combo_pack_id
                    ? ($kotComboInstanceByItemId[(int) $item->id] ?? null)
                    : null;

                $modifierQtyMap = $item->modifierOptions
                    ->mapWithKeys(fn ($opt) => [(int) $opt->id => (int) ($opt->pivot->quantity ?? 1)])
                    ->all();

                $qty = (int) ($item->quantity ?? 1);
                $packId = $item->combo_pack_id ? (int) $item->combo_pack_id : null;

                // Resolve modifier-inclusive / combo-discounted price from the
                // matching order item. Use queues so identical lines map in order.
                $matchedOrderItem = null;
                $matchKey = $lineMatchKeyForRow($item);
                if (! empty($lineQueues[$matchKey])) {
                    $matchedOrderItem = array_shift($lineQueues[$matchKey]);
                    if ($matchedOrderItem) {
                        $orderItemToKotItemMap[$matchedOrderItem->id] = (int) $item->id;
                    }
                }

                if ($matchedOrderItem) {
                    $matchedPrice = (float) ($matchedOrderItem->price ?? 0);
                    $unitPrice = $matchedPrice > 0
                        ? round($matchedPrice, 2)
                        : $resolveUnitPrice($item);
                } else {
                    $unitPrice = $resolveUnitPrice($item);
                }

                $amount = $matchedOrderItem
                    ? (float) ($matchedOrderItem->amount ?? 0)
                    : (float) ($item->amount ?? 0);
                if ($amount <= 0 && $unitPrice > 0 && $qty > 0) {
                    $amount = round($unitPrice * $qty, 2);
                }

                $comboPackName = $packId ? (string) ($kotComboNames[$packId] ?? '') : '';
                $comboDiscountPerUnit = 0.0;
                if ($packId && $matchedOrderItem) {
                    $matchedQty = (int) ($matchedOrderItem->quantity ?? 0);
                    $matchedDiscount = (float) ($matchedOrderItem->combo_discount_amount ?? 0);
                    if ($matchedQty > 0 && $matchedDiscount > 0) {
                        $comboDiscountPerUnit = round($matchedDiscount / $matchedQty, 2);
                    }
                }

                $comboOriginalUnit = null;
                if ($packId) {
                    if ($matchedOrderItem) {
                        $matchedQty = (int) ($matchedOrderItem->quantity ?? 0);
                        $matchedOriginal = (float) ($matchedOrderItem->original_price ?? 0);
                        if ($matchedQty > 0 && $matchedOriginal > 0) {
                            $comboOriginalUnit = round($matchedOriginal / $matchedQty, 2);
                        }
                    }
                    if ($comboOriginalUnit === null && $unitPrice > 0) {
                        $comboOriginalUnit = round($unitPrice + $comboDiscountPerUnit, 2);
                    }
                }

                return [
                    'kot_item_id' => (int) $item->id,
                    'order_item_id' => $matchedOrderItem ? (int) $matchedOrderItem->id : null,
                    'menu_item_id' => (int) $item->menu_item_id,
                    'item_name' => (string) ($item->menuItem?->item_name ?? ''),
                    'menu_item_variation_id' => $item->menu_item_variation_id ? (int) $item->menu_item_variation_id : null,
                    'variation_name' => (string) ($item->menuItemVariation?->variation ?? ''),
                    'qty' => $qty,
                    'unit_price' => $unitPrice,
                    'amount' => $amount,
                    'note' => (string) ($item->note ?? ''),
                    'status' => (string) ($item->status ?? ''),
                    'combo_pack_id' => $packId,
                    'combo_pack_name' => $comboPackName !== '' ? $comboPackName : null,
                    'combo_discount' => $comboDiscountPerUnit > 0 ? $comboDiscountPerUnit : null,
                    'combo_original_unit_price' => $comboOriginalUnit,
                    'combo_instance_key' => $comboInstanceKey,
                    'modifier_option_quantities' => $modifierQtyMap,
                    'modifier_option_details' => $item->modifierOptions->map(fn ($o) => [
                        'id' => (int) $o->id,
                        'name' => (string) $o->name,
                        'price' => (float) $o->price,
                    ])->all(),
                ];
            })->values();

            return [
                'id' => (int) $kot->id,
                'kot_number' => (string) ($kot->kot_number ?? ''),
                'created_at' => $kot->created_at ? $kot->created_at->toIso8601String() : null,
                'status' => (string) ($kot->status ?? ''),
                'kitchen_place_id' => $kot->kitchen_place_id ? (int) $kot->kitchen_place_id : null,
                'lines' => $kotLines,
            ];
        })->values();

        $lines = $orderItemsSorted->map(function ($item) use ($resolveUnitPrice, $comboInstanceByOrderItemId, $comboNamesByPackId, $orderItemToKotItemMap) {
            $comboInstanceKey = $item->combo_pack_id
                ? ($comboInstanceByOrderItemId[(int) $item->id] ?? null)
                : null;

            $modifierQtyMap = $item->modifierOptions
                ->mapWithKeys(fn ($opt) => [(int) $opt->id => (int) ($opt->pivot->quantity ?? 1)])
                ->all();

            $qty = (int) ($item->quantity ?? 1);
            $unitPrice = $resolveUnitPrice($item);
            $amount = (float) ($item->amount ?? 0);
            if ($amount <= 0 && $unitPrice > 0 && $qty > 0) {
                $amount = round($unitPrice * $qty, 2);
            }

            $packId = $item->combo_pack_id ? (int) $item->combo_pack_id : null;
            $comboPackName = $packId ? (string) ($comboNamesByPackId[$packId] ?? '') : '';
            $comboDiscountPerUnit = 0.0;
            if ($packId && $qty > 0 && (float) ($item->combo_discount_amount ?? 0) > 0) {
                $comboDiscountPerUnit = round((float) $item->combo_discount_amount / $qty, 2);
            }

            $comboOriginalUnit = null;
            if ($packId && $qty > 0) {
                if ((float) ($item->original_price ?? 0) > 0) {
                    $comboOriginalUnit = round((float) $item->original_price / $qty, 2);
                } else {
                    $comboOriginalUnit = round($unitPrice + $comboDiscountPerUnit, 2);
                }
            }

            return [
                'order_item_id' => (int) $item->id,
                'kot_item_id' => $orderItemToKotItemMap[$item->id] ?? null,
                'menu_item_id' => (int) $item->menu_item_id,
                'item_name' => (string) ($item->menuItem?->item_name ?? ''),
                'menu_item_variation_id' => $item->menu_item_variation_id ? (int) $item->menu_item_variation_id : null,
                'variation_name' => (string) ($item->menuItemVariation?->variation ?? ''),
                'qty' => $qty,
                'unit_price' => $unitPrice,
                'amount' => $amount,
                'note' => $item->note,
                'combo_pack_id' => $packId,
                'combo_pack_name' => $comboPackName !== '' ? $comboPackName : null,
                'combo_discount' => $comboDiscountPerUnit > 0 ? $comboDiscountPerUnit : null,
                'combo_original_unit_price' => $comboOriginalUnit,
                'combo_instance_key' => $comboInstanceKey,
                'modifier_option_quantities' => $modifierQtyMap,
                'modifier_option_details' => $item->modifierOptions->map(fn ($o) => [
                    'id' => (int) $o->id,
                    'name' => (string) $o->name,
                    'price' => (float) $o->price,
                ])->all(),
                ...OrderItemLinePricing::linePayloadFromModel($item),
            ];
        })->values();

        $customerPhone = null;
        if ($order->customer?->phone) {
            $phoneCode = trim((string) ($order->customer->phone_code ?? ''));
            $phone = trim((string) $order->customer->phone);
            $customerPhone = $phoneCode !== '' ? $phoneCode.$phone : $phone;
        }

        $paidQuantities = [];
        $paidSplitOrders = $order->splitOrders()
            ->where('status', 'paid')
            ->with('items')
            ->get();

        foreach ($paidSplitOrders as $splitOrder) {
            foreach ($splitOrder->items as $splitItem) {
                $orderItemId = $splitItem->order_item_id;
                $paidQuantities[$orderItemId] = ($paidQuantities[$orderItemId] ?? 0) + $splitItem->quantity;
            }
        }

        $restaurantId = $order->branch?->restaurant_id ?? restaurant()?->id;
        $waiters = \App\Models\User::role('Waiter_' . $restaurantId)->get(['id', 'name']);
        $deliveryExecutives = \App\Models\DeliveryExecutive::where('status', 'available')
            ->orWhere('id', $order->delivery_executive_id)
            ->get(['id', 'name']);
        $cancelReasons = \App\Models\KotCancelReason::where('cancel_order', true)->get(['id', 'reason']);

        return response()->json([
            'success' => true,
            'data' => [
                'waiters' => $waiters,
                'delivery_executives' => $deliveryExecutives,
                'cancel_reasons' => $cancelReasons,
                'order' => [
                    'id' => (int) $order->id,
                    'order_number' => (string) ($order->order_number ?? ''),
                    'formatted_order_number' => (string) ($order->show_formatted_order_number ?? ''),
                    'status' => (string) $order->status,
                    'split_type' => (string) $order->split_type,
                    'paid_item_quantities' => (object) $paidQuantities,
                    'split_orders' => $paidSplitOrders->map(fn($so) => [
                        'id' => $so->id,
                        'amount' => (float) $so->amount,
                        'payment_method' => $so->payment_method,
                        'status' => $so->status,
                        'items' => $so->items->map(fn($si) => [
                            'order_item_id' => $si->order_item_id,
                            'quantity' => $si->quantity,
                        ]),
                    ]),
                    'order_status' => $order->order_status?->value ?? (string) ($order->order_status ?? ''),
                    'order_type' => (string) ($order->order_type ?? 'dine_in'),
                    'order_type_id' => $order->order_type_id ? (int) $order->order_type_id : null,
                    'delivery_app_id' => $order->delivery_app_id ? (int) $order->delivery_app_id : null,
                    'delivery_executive_id' => $order->delivery_executive_id ? (int) $order->delivery_executive_id : null,
                    'delivery_fee' => (float) ($order->delivery_fee ?? 0),
                    'waiter_id' => $order->waiter_id ? (int) $order->waiter_id : null,
                    'waiter_name' => $order->waiter?->name ? (string) $order->waiter->name : null,
                    'customer_id' => $order->customer_id ? (int) $order->customer_id : null,
                    'customer' => $order->customer ? [
                        'id' => (int) $order->customer->id,
                        'name' => (string) ($order->customer->name ?? ''),
                        'email' => $order->customer->email,
                        'phone' => $order->customer->phone,
                        'phone_code' => $order->customer->phone_code,
                        'address' => $order->customer->delivery_address,
                        'delivery_address' => $order->customer->delivery_address,
                    ] : null,
                    'delivery_address' => (string) ($order->delivery_address ?? $order->customer?->delivery_address ?? ''),
                    'customer_phone' => $customerPhone,
                    // Legacy parity (Pos.php mount): the linked-order view hydrates
                    // $this->tableId/$this->tableNo from $order->table_id so the
                    // "Table X" badge survives a reload of /pos/kot/{id}.
                    'table_id' => $order->table_id ? (int) $order->table_id : null,
                    'table_code' => $order->table?->table_code ? (string) $order->table->table_code : null,
                    'hotel_reservation_id' => $order->hotel_reservation_id ? (int) $order->hotel_reservation_id : null,
                    'hotel_reservation' => PosHotelSupport::formatReservation($order->hotelReservation),
                    'customer_lat' => $order->customer_lat !== null ? (float) $order->customer_lat : null,
                    'customer_lng' => $order->customer_lng !== null ? (float) $order->customer_lng : null,
                    'note' => (string) ($order->note ?? ''),
                    'sub_total' => (float) ($order->sub_total ?? 0),
                    'total' => (float) ($order->total ?? 0),
            'discount_type' => $order->discount_type ? (string) $order->discount_type : null,
            'discount_value' => $order->discount_value !== null ? (float) $order->discount_value : 0.0,
            'discount_amount' => (float) ($order->discount_amount ?? 0),
                    'reward_point_discount' => (float) ($order->reward_point_discount ?? 0),
                    'reward_points_redeemed' => (int) ($order->reward_points_redeemed ?? 0),
                    'reward_points_earned' => (int) ($order->reward_points_earned ?? 0),
                    // Legacy parity (Pos.php mount): custom_extras loaded from order_extras.
                    // Only surfaced when the setting is enabled so the UI never appears
                    // for restaurants that have it turned off.
                    'allow_custom_order_extras' => (bool) (restaurant()->allow_custom_order_extras ?? false),
                    'show_kot_print' => (bool) (restaurant()->receiptSetting?->show_kot_print ?? true),
                    'custom_extras' => (restaurant()->allow_custom_order_extras ?? false)
                        ? $order->extras()->orderBy('id')->get(['note', 'amount'])
                            ->map(fn ($extra) => [
                                'note' => $extra->note,
                                'amount' => (float) $extra->amount,
                            ])->values()
                        : [],
                    'permissions' => [
                        'can_update_order' => (bool) user_can('Update Order'),
                        'can_delete_order' => (bool) user_can('Delete Order'),
                        'can_edit_billed_order' => (bool) user_can('Edit Billed Order'),
                        'can_delete_kot_item' => (bool) user_can('Delete KOT Item'),
                        'can_redeem_reward_points' => (bool) user_can('Redeem Reward Points'),
                    ],
                    'lines' => $lines,
                    'kots' => $kots,
                    'show_room_charge' => (bool) PosHotelSupport::showRoomChargePayment(),
                    'in_house_reservations' => PosHotelSupport::showRoomChargePayment() ? PosHotelSupport::checkedInReservationsForPos()->values()->all() : [],
                    'can_add_tip' => (bool) (restaurant()->enable_tip_pos && $order->status !== 'paid'),
                    'tip_amount' => (float) ($order->tip_amount ?? 0),
                    'tip_note' => (string) ($order->tip_note ?? ''),
                    'currency_symbol' => (string) (restaurant()?->currency?->symbol ?? 'Rs'),
                    'amount_paid' => (float) $order->nonDuePaymentsSum(),
                    'due_amount' => (float) $order->outstandingAmount(),
                    'payments' => $order->payments->map(fn ($p) => [
                        'id' => (int) $p->id,
                        'payment_method' => (string) $p->payment_method,
                        'amount' => (float) $p->amount,
                        'created_at' => $p->created_at ? $p->created_at->toIso8601String() : null,
                        'formatted_date' => $p->created_at ? $p->created_at->timezone(timezone())->translatedFormat('d M, Y h:i A') : '',
                    ])->values()->all(),
                ],
            ],
        ]);
    }

    public function store(Request $request)
    {
        abort_if(! in_array('Order', restaurant_modules()), 403);

        $validated = $request->validate([
            'order_id' => ['nullable', 'integer', 'exists:orders,id'],
            'action' => ['nullable', 'string', Rule::in(['kot', 'bill'])],
            'open_payment' => ['nullable', 'boolean'],
            'secondary_action' => ['nullable', 'string', Rule::in(['payment', 'print'])],
            'order_type_id' => ['nullable', 'integer', 'exists:order_types,id'],
            'delivery_app_id' => ['nullable'],
            'delivery_executive_id' => ['nullable', 'integer', 'exists:delivery_executives,id'],
            'delivery_fee' => ['nullable', 'numeric', 'min:0'],
            'waiter_id' => ['nullable', 'integer', 'exists:users,id'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            // Legacy parity (Pos.php::saveOrder): table_id is persisted on both
            // create and update paths (`'table_id' => $this->tableId` and
            // `'table_id' => $this->tableId ?? $order->table_id`). Without this
            // the Vue POS created orders with no DB-level table linkage, which
            // broke `Table::activeOrder`, the "running" tables grid, and the
            // OrderObserver auto-lock. Constrain to the current branch.
            'table_id' => ['nullable', 'integer'],
            'note' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.menu_item_id' => ['required', 'integer', 'exists:menu_items,id'],
            'lines.*.menu_item_variation_id' => ['nullable', 'integer', 'exists:menu_item_variations,id'],
            'lines.*.qty' => ['required', 'integer', 'min:1'],
            'lines.*.note' => ['nullable', 'string'],
            'lines.*.modifier_option_quantities' => ['nullable', 'array'],
            'lines.*.modifier_option_quantities.*' => ['nullable', 'integer', 'min:1'],
            'lines.*.combo_pack_id' => ['nullable', 'integer', 'exists:combo_packs,id'],
            'lines.*.combo_instance_key' => ['nullable', 'string'],
            'lines.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'lines.*.discount_type' => ['nullable', 'string', Rule::in(['fixed', 'percent'])],
            'lines.*.discount_value' => ['nullable', 'numeric', 'min:0'],
            // Legacy parity (Pos.php::normalizeOrderExtras / syncOrderExtras):
            // optional per-order custom extras, each with {amount, note}.
            'custom_extras' => ['nullable', 'array'],
            'custom_extras.*.amount' => ['nullable', 'numeric', 'min:0'],
            'custom_extras.*.note' => ['nullable', 'string'],
            // Discount fields persisted on the order.
            'discount_type' => ['nullable', 'string', Rule::in(['fixed', 'percent'])],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            // Reward points redemption fields
            'reward_points_redeemed' => ['nullable', 'integer', 'min:0'],
            'reward_point_discount' => ['nullable', 'numeric', 'min:0'],
            // New-KOT mode flag: when the cart is an "append-only" delta for an
            // existing order, the store path must preserve existing items/KOTs
            // (mirrors Pos.php::$appendOnlyKotSave).
            'append_kot' => ['nullable', 'boolean'],
            // Legacy saveOrder('kot', 'bill', 'payment'): KOT first, then bill + payment modal.
            'bill_after_kot' => ['nullable', 'boolean'],
            'hotel_reservation_id' => ['nullable', 'integer'],
        ]);

        $editingOrderId = isset($validated['order_id']) ? (int) $validated['order_id'] : null;
        $action = $validated['action'] ?? 'kot';
        $secondaryAction = $validated['secondary_action'] ?? null;
        $openPayment = (bool) ($validated['open_payment'] ?? false);
        $billAfterKot = (bool) ($validated['bill_after_kot'] ?? false);
        $status = ($action === 'bill' || $billAfterKot) ? 'billed' : 'kot';
        $billFollowUp = app(BillSecondaryActionResolver::class)->resolve(
            ($action === 'bill' || $billAfterKot) ? 'bill' : $action,
            $billAfterKot && $openPayment ? 'payment' : $secondaryAction
        );
        $opensImmediatePayment = ($action === 'bill' || $billAfterKot)
            && ($openPayment || $secondaryAction === 'payment' || ($billFollowUp['open_payment'] ?? false));
        // Append-only KOT save is valid only for `kot` action against an existing order.
        // Legacy parity (Pos.php::$appendOnlyKotSave): the New KOT screen posts
        // a delta of new lines only. Regardless of action (kot or bill), the
        // server must preserve existing items/KOTs/extras. Status transitions
        // on bill are still applied via the update payload below.
        $appendKot = $editingOrderId && ! empty($validated['append_kot']);

        $branch = branch();
        $restaurant = restaurant();
        abort_if(! $branch || ! $restaurant, 422, 'Branch/restaurant context is required');
        $posUserId = auth()->id();

        // Cache schema checks outside the transaction loop — Schema::hasColumn()
        // hits the DB on every call. These columns are added by migrations and
        // never removed, so a per-process in-memory cache is safe.
        static $hasOrderItemsComboInstanceKey = null;
        static $hasKotItemsComboInstanceKey = null;
        if ($hasOrderItemsComboInstanceKey === null) {
            $hasOrderItemsComboInstanceKey = \Illuminate\Support\Facades\Schema::hasColumn('order_items', 'combo_instance_key');
        }
        if ($hasKotItemsComboInstanceKey === null) {
            $hasKotItemsComboInstanceKey = \Illuminate\Support\Facades\Schema::hasColumn('kot_items', 'combo_instance_key');
        }

        if (! empty($validated['waiter_id'])) {
            abort_unless(
                User::isAssignableWaiter((int) $validated['waiter_id'], (int) $restaurant->id, (int) $branch->id),
                422,
                'Selected waiter is not assignable to this branch.'
            );
        }

        if ($editingOrderId) {
            $orderForPermission = Order::query()
                ->where('id', $editingOrderId)
                ->where('branch_id', $branch->id)
                ->firstOrFail();

            $billedLifecycle = ['billed', 'paid', 'payment_due'];
            $currentStatus = (string) $orderForPermission->status;

            // Resulting `status` after this request must mirror the update payload in the
            // transaction (see ~530–539): full replace always applies $status; append mode
            // only changes status on `bill:`, otherwise the row keeps its current status.
            if ($appendKot) {
                $targetStatus = ($action === 'bill' || $billAfterKot)
                    ? 'billed'
                    : $currentStatus;
            } else {
                $targetStatus = $status;
            }

            $isTransitioningOnBilled = in_array($currentStatus, $billedLifecycle, true)
                && in_array($targetStatus, $billedLifecycle, true);

            abort_if($isTransitioningOnBilled && ! user_can('Edit Billed Order'), 403);
            abort_if(! $isTransitioningOnBilled && ! user_can('Update Order'), 403);
        } else {
            abort_if(! user_can('Create Order'), 403);
        }

        $orderType = null;
        if (! empty($validated['order_type_id'])) {
            $orderType = OrderType::query()->find($validated['order_type_id']);
        }

        $orderTypeValue = $orderType?->slug
            ?: ($orderType?->type ? strtolower((string) $orderType->type) : 'dine_in');

        if (! in_array($orderTypeValue, ['dine_in', 'delivery', 'pickup', 'room_service'], true)) {
            $orderTypeValue = 'dine_in';
        }

        $hotelReservationId = isset($validated['hotel_reservation_id'])
            ? (int) $validated['hotel_reservation_id']
            : null;

        if ($orderTypeValue === 'room_service') {
            abort_if(! PosHotelSupport::isRoomServiceEnabled(), 422, 'Room service is not enabled.');
            abort_if($hotelReservationId <= 0, 422, __('modules.order.selectRoom'));

            $hotelReservation = HotelReservation::query()
                ->where('id', $hotelReservationId)
                ->where('branch_id', branch()->id)
                ->where('status', HotelReservation::STATUS_CHECKED_IN)
                ->first();

            abort_if(! $hotelReservation, 422, __('modules.order.selectRoom'));
        } else {
            $hotelReservationId = null;
        }

        $deliveryAppId = null;
        $sessionDeliveryAppId = null;
        if ($orderTypeValue === 'delivery') {
            $deliveryAppRaw = $validated['delivery_app_id'] ?? null;
            if ($deliveryAppRaw === 'default' || $deliveryAppRaw === null || $deliveryAppRaw === '') {
                $deliveryAppId = null;
                $sessionDeliveryAppId = 'default';
            } else {
                $candidateId = (int) $deliveryAppRaw;
                $deliveryApp = DeliveryPlatform::query()
                    ->where('id', $candidateId)
                    ->where('is_active', true)
                    ->first();

                if ($deliveryApp) {
                    $deliveryAppId = (int) $deliveryApp->id;
                    $sessionDeliveryAppId = $deliveryAppId;
                } else {
                    $sessionDeliveryAppId = 'default';
                }
            }
        } else {
            $sessionDeliveryAppId = false;
        }

        // Resolve the selected table (branch-scoped) so we only ever persist
        // valid table_ids on POS orders. Mirrors legacy Pos::setTable() which
        // drives Pos::saveOrder's `'table_id' => $this->tableId` write.
        $resolvedTableId = null;
        if ($orderTypeValue !== 'room_service' && array_key_exists('table_id', $validated) && $validated['table_id']) {
            $table = Table::query()
                ->where('id', (int) $validated['table_id'])
                ->where('branch_id', $branch->id)
                ->first();

            abort_if(! $table, 422, 'Selected table is not available in this branch.');
            $resolvedTableId = (int) $table->id;
        }

        $result = DB::transaction(function () use ($validated, $editingOrderId, $action, $status, $branch, $orderType, $orderTypeValue, $restaurant, $deliveryAppId, $appendKot, $resolvedTableId, $opensImmediatePayment, $billAfterKot, $hotelReservationId, $posUserId, $hasOrderItemsComboInstanceKey, $hasKotItemsComboInstanceKey) {
            // Note: Session updates are performed after the transaction succeeds (below)
            $isUpdate = false;

            if ($editingOrderId) {
                $order = Order::query()
                    ->where('id', $editingOrderId)
                    ->where('branch_id', $branch->id)
                    ->firstOrFail();

                $isUpdate = true;
                $statusBeforeSave = (string) $order->status;

                // Legacy parity (Pos.php saveOrder):
                //   - On `bill`, existing KOTs are preserved so order_detail.blade can render $kotList.
                //   - On `kot` with a FULL cart (non-append), items/KOTs are wiped and recreated.
                //   - On `kot` in APPEND mode (New KOT flow from /pos/kot/{id}), everything is
                //     preserved and only the delta lines are appended + new KOT created below.
                if (! $appendKot) {
                    foreach ($order->items()->with('modifierOptions')->get() as $existingOrderItem) {
                        $existingOrderItem->modifierOptions()->detach();
                    }

                    $order->items()->delete();
                    $order->taxes()->delete();

                    if ($action === 'kot') {
                        foreach ($order->kot()->with('items.modifierOptions')->get() as $existingKot) {
                            foreach ($existingKot->items as $existingKotItem) {
                                $existingKotItem->modifierOptions()->detach();
                            }
                            $existingKot->items()->delete();
                            $existingKot->delete();
                        }
                    }
                }

                // Build the update payload; preserve status when appending a New KOT to a
                // billed/paid/payment_due order so we don't demote it back to `kot`.
                $updatePayload = [
                    'date_time' => now(),
                    'waiter_id' => $validated['waiter_id'] ?? null,
                    'pos_user_id' => $posUserId,
                    'customer_id' => $orderTypeValue === 'room_service' ? null : ($validated['customer_id'] ?? null),
                    'delivery_app_id' => $deliveryAppId,
                    'delivery_executive_id' => ($orderTypeValue === 'delivery') ? ($validated['delivery_executive_id'] ?? null) : null,
                    'delivery_fee' => ($orderTypeValue === 'delivery') ? (float) ($validated['delivery_fee'] ?? 0) : 0,
                    // Legacy parity (Pos.php::saveOrder line 2913):
                    //   'table_id' => $this->tableId ?? $order->table_id
                    // — preserve the existing link if the UI didn't send a new one.
                    'table_id' => $orderTypeValue === 'room_service' ? null : ($resolvedTableId ?? $order->table_id),
                    'order_type' => $orderTypeValue,
                    'order_type_id' => $orderType?->id,
                    'custom_order_type_name' => $orderType?->order_type_name,
                    'order_status' => 'confirmed',
                    'placed_via' => 'pos',
                    'tax_mode' => $restaurant->tax_mode ?? 'item',
                    'hotel_reservation_id' => $hotelReservationId,
                ];

                if (! $appendKot) {
                    $updatePayload['sub_total'] = 0;
                    $updatePayload['total'] = 0;
                    $updatePayload['status'] = $status;
                } elseif ($action === 'bill') {
                    // Append + bill (e.g. "KOT, Bill & Payment" from New KOT screen):
                    // promote the existing order to `billed` while keeping previously
                    // persisted items/KOTs. Totals are recomputed below and re-saved.
                    $updatePayload['status'] = $status;
                }

                $order->update($updatePayload);
            } else {
                $statusBeforeSave = null;
                $numberData = Order::generateOrderNumber($branch);

                $order = Order::create([
                    'order_number' => $numberData['order_number'],
                    'formatted_order_number' => $numberData['formatted_order_number'],
                    'date_time' => now(),
                    'waiter_id' => $validated['waiter_id'] ?? null,
                    'pos_user_id' => $posUserId,
                    'customer_id' => $orderTypeValue === 'room_service' ? null : ($validated['customer_id'] ?? null),
                    'delivery_app_id' => $deliveryAppId,
                    'delivery_executive_id' => ($orderTypeValue === 'delivery') ? ($validated['delivery_executive_id'] ?? null) : null,
                    'delivery_fee' => ($orderTypeValue === 'delivery') ? (float) ($validated['delivery_fee'] ?? 0) : 0,
                    // Legacy parity (Pos.php::saveOrder line 2863):
                    //   'table_id' => $this->tableId
                    // OrderObserver::created auto-locks the table via lockForOrder
                    // once this is set, matching legacy table-lock-on-order flow.
                    'table_id' => $orderTypeValue === 'room_service' ? null : $resolvedTableId,
                    'sub_total' => 0,
                    'total' => 0,
                    'reward_point_discount' => null,
                    'reward_points_redeemed' => null,
                    'order_type' => $orderTypeValue,
                    'order_type_id' => $orderType?->id,
                    'custom_order_type_name' => $orderType?->order_type_name,
                    'status' => $status,
                    'order_status' => 'confirmed',
                    'placed_via' => 'pos',
                    'tax_mode' => $restaurant->tax_mode ?? 'item',
                    'hotel_reservation_id' => $hotelReservationId,
                ]);
            }

            $subtotal = 0.0;
            $orderItemsCreated = [];
            $kotLineSeed = [];

            $comboPackIdsInRequest = collect($validated['lines'] ?? [])
                ->pluck('combo_pack_id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique();

            $comboPacksById = collect();
            if ($comboPackIdsInRequest->isNotEmpty()) {
                $comboPacksById = ComboPack::query()
                    ->with([
                        'comboPackItems.menuItem.recipes.inventoryItem',
                        'comboPackItems.menuItemVariation',
                    ])
                    ->whereIn('id', $comboPackIdsInRequest->all())
                    ->get()
                    ->keyBy('id');

                foreach ($comboPackIdsInRequest as $comboPackId) {
                    $cp = $comboPacksById->get($comboPackId);
                    abort_if(! $cp || (int) $cp->branch_id !== (int) $branch->id, 422, 'Invalid combo pack.');
                    abort_if(! $cp->isAvailable(), 422, __('modules.combo.comboNotAvailable'));
                    $stockResult = $cp->validateStock();
                    abort_if(! $stockResult['valid'], 422, (string) ($stockResult['message'] ?? 'Combo stock validation failed.'));
                }
            }

            $menuItemIds = collect($validated['lines'] ?? [])
                ->pluck('menu_item_id')
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values()
                ->all();

            $menuItemsById = $menuItemIds === []
                ? collect()
                : MenuItem::query()->with('taxes')->whereIn('id', $menuItemIds)->get()->keyBy('id');

            $variationIds = collect($validated['lines'] ?? [])
                ->pluck('menu_item_variation_id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            $variationsById = $variationIds === []
                ? collect()
                : MenuItemVariation::query()->whereIn('id', $variationIds)->get()->keyBy('id');

            $allModifierOptionIds = collect($validated['lines'] ?? [])
                ->flatMap(fn ($line) => array_keys($line['modifier_option_quantities'] ?? []))
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values()
                ->all();

            $modifierOptionsById = $allModifierOptionIds === []
                ? collect()
                : ModifierOption::query()->whereIn('id', $allModifierOptionIds)->get()->keyBy('id');

            // Hoist KotPlace default lookup outside the per-item loop.
            // Previously this issued up to 2 queries per item that had no kitchen assigned.
            $defaultKotPlaceId = KotPlace::query()
                ->where('branch_id', $order->branch_id)
                ->where('is_default', true)
                ->value('id');
            if (! $defaultKotPlaceId) {
                $defaultKotPlaceId = KotPlace::query()
                    ->where('branch_id', $order->branch_id)
                    ->value('id');
            }

            foreach ($validated['lines'] as $line) {
                $menuItem = $menuItemsById->get((int) $line['menu_item_id']);
                abort_if(! $menuItem, 422, 'Invalid menu item.');
                $variation = null;
                $variationId = isset($line['menu_item_variation_id']) ? (int) $line['menu_item_variation_id'] : null;

                if ($variationId) {
                    $variation = $variationsById->get($variationId);
                    abort_if(
                        ! $variation || (int) $variation->menu_item_id !== (int) $menuItem->id,
                        422,
                        'Invalid menu item variation.'
                    );
                }

                $qty = (int) $line['qty'];
                $comboPackId = isset($line['combo_pack_id']) ? (int) $line['combo_pack_id'] : null;
                $isComboItem = $comboPackId && ! empty($line['combo_instance_key']);
                $comboInstanceKeyVal = ! empty($line['combo_instance_key'])
                    ? (string) $line['combo_instance_key']
                    : null;

                $modifierQtyMap = collect($line['modifier_option_quantities'] ?? [])
                    ->mapWithKeys(function ($qtyValue, $optionId) {
                        $id = (int) $optionId;
                        $qty = (int) $qtyValue;
                        if ($id <= 0 || $qty <= 0) {
                            return [];
                        }

                        return [$id => $qty];
                    })
                    ->all();

                $modifierOptions = ! empty($modifierQtyMap)
                    ? $modifierOptionsById->only(array_keys($modifierQtyMap))
                    : collect();

                $basePrice = $variation
                    ? (float) ($variation->price ?? 0)
                    : (float) ($menuItem->price ?? 0);

                $comboOriginalUnitPrice = null;
                $comboDiscountPerUnit = 0.0;

                if ($isComboItem) {
                    $combo = $comboPacksById->get($comboPackId) ?? ComboPack::query()
                        ->with(['comboPackItems.menuItem', 'comboPackItems.menuItemVariation'])
                        ->findOrFail($comboPackId);

                    $comboPricing = collect($combo->calculateComboItemPrices($orderType?->id, $deliveryAppId));
                    $comboMatch = $comboPricing->first(function ($entry) use ($menuItem, $variation) {
                        $comboItem = $entry['combo_item'];
                        $comboVariationId = $comboItem->menu_item_variation_id ? (int) $comboItem->menu_item_variation_id : null;
                        $lineVariationId = $variation?->id ? (int) $variation->id : null;

                        return (int) $comboItem->menu_item_id === (int) $menuItem->id
                            && $comboVariationId === $lineVariationId;
                    });

                    abort_if(! $comboMatch, 422, 'Invalid combo line payload');

                    $basePrice = (float) ($comboMatch['price'] ?? $basePrice);
                    $comboOriginalUnitPrice = (float) ($comboMatch['original_price'] ?? $basePrice);
                    $comboDiscountPerUnit = max(0, $comboOriginalUnitPrice - $basePrice);
                }

                $modifierUnitTotal = 0.0;
                foreach ($modifierQtyMap as $optionId => $optionQty) {
                    $optionPrice = (float) ($modifierOptions[$optionId]->price ?? 0);
                    $modifierUnitTotal += ($optionPrice * $optionQty);
                }

                $unitPrice = round($basePrice + $modifierUnitTotal, 2);

                if (! $isComboItem && isset($line['unit_price']) && is_numeric($line['unit_price'])) {
                    $unitPrice = round((float) $line['unit_price'], 2);
                }

                $lineDiscountType = $isComboItem ? null : ($line['discount_type'] ?? null);
                $lineDiscountValue = $isComboItem || ! isset($line['discount_value'])
                    ? null
                    : (float) $line['discount_value'];

                $pricing = $isComboItem
                    ? [
                        'price' => $unitPrice,
                        'amount' => round($qty * $unitPrice, 2),
                        'discount_type' => null,
                        'discount_value' => null,
                        'item_discount_amount' => null,
                    ]
                    : OrderItemLinePricing::compute($unitPrice, $qty, $lineDiscountType, $lineDiscountValue);

                $unitPrice = $pricing['price'];
                $amount = $pricing['amount'];
                $subtotal += $amount;

                $taxModeStore = (string) ($restaurant->tax_mode ?? 'item');
                $taxAmountVal = null;
                $taxPercentageVal = null;
                $taxBreakupVal = null;
                if ($taxModeStore === 'item' && $menuItem->taxes->isNotEmpty()) {
                    $taxFields = OrderItemLinePricing::itemTaxForLine($menuItem, $amount, $qty);
                    $taxAmountVal = $taxFields['tax_amount'];
                    $taxPercentageVal = $taxFields['tax_percentage'];
                    $taxBreakupVal = $taxFields['tax_breakup'];
                }

                $orderItemData = [
                    'branch_id' => $order->branch_id,
                    'order_type' => $orderTypeValue,
                    'order_type_id' => $orderType?->id,
                    'order_id' => $order->id,
                    'menu_item_id' => $menuItem->id,
                    'menu_item_variation_id' => $variation?->id,
                    'combo_pack_id' => $isComboItem ? $comboPackId : null,
                    'quantity' => $qty,
                    'price' => $unitPrice,
                    'original_price' => $isComboItem ? round($comboOriginalUnitPrice * $qty, 2) : null,
                    'combo_discount_amount' => $isComboItem ? round($comboDiscountPerUnit * $qty, 2) : null,
                    'discount_type' => $pricing['discount_type'],
                    'discount_value' => $pricing['discount_value'],
                    'item_discount_amount' => $pricing['item_discount_amount'],
                    'is_combo_item' => (bool) $isComboItem,
                    'amount' => $amount,
                    'note' => $line['note'] ?? null,
                    'tax_amount' => $taxAmountVal,
                    'tax_percentage' => $taxPercentageVal,
                    'tax_breakup' => $taxBreakupVal,
                ];
                if ($hasOrderItemsComboInstanceKey) {
                    $orderItemData['combo_instance_key'] = $isComboItem ? $comboInstanceKeyVal : null;
                }
                $orderItem = OrderItem::create($orderItemData);

                if (! empty($modifierQtyMap)) {
                    $orderItem->modifierOptions()->sync(
                        collect($modifierQtyMap)->mapWithKeys(fn ($optionQty, $optionId) => [(int) $optionId => ['quantity' => (int) $optionQty]])->all()
                    );
                }

                $orderItemsCreated[] = $orderItem->id;

                $kitchenIds = method_exists($menuItem, 'getKitchenPlaceIds')
                    ? ($menuItem->getKitchenPlaceIds() ?? [])
                    : [];

                if (empty($kitchenIds)) {
                    if ($defaultKotPlaceId) {
                        $kitchenIds = [$defaultKotPlaceId];
                    }
                }

                if (! empty($kitchenIds)) {
                    $seedLine = [
                        'kitchen_place_id' => (int) $kitchenIds[0],
                        'menu_item_id' => $menuItem->id,
                        'menu_item_variation_id' => $variation?->id,
                        'combo_pack_id' => $isComboItem ? $comboPackId : null,
                        'qty' => $qty,
                        'note' => $line['note'] ?? null,
                        'modifier_option_quantities' => $modifierQtyMap,
                        'is_multi_kitchen' => count($kitchenIds) > 1,
                    ];
                    if ($hasKotItemsComboInstanceKey) {
                        $seedLine['combo_instance_key'] = $isComboItem ? $comboInstanceKeyVal : null;
                    }
                    $kotLineSeed[] = $seedLine;
                }
            }

            // Legacy parity (Pos.php::syncOrderExtras): persist custom extras when the
            // setting is enabled. On bill/kot with a full cart we delete + recreate;
            // on append-only New KOT we keep existing rows untouched.
            $allowExtras = (bool) ($restaurant->allow_custom_order_extras ?? false);
            if ($allowExtras && ! $appendKot) {
                $order->extras()->delete();

                foreach (($validated['custom_extras'] ?? []) as $extraRow) {
                    if (! is_array($extraRow)) {
                        continue;
                    }

                    $extraNote = trim((string) ($extraRow['note'] ?? ''));
                    $extraAmount = max(0, round((float) ($extraRow['amount'] ?? 0), 2));

                    if ($extraNote === '' && $extraAmount <= 0) {
                        continue;
                    }

                    OrderExtra::create([
                        'order_id' => $order->id,
                        'note' => $extraNote !== '' ? $extraNote : null,
                        'amount' => $extraAmount,
                    ]);
                }
            }

            // Legacy parity (Pos.php::calculateTotal): extras are added to total but
            // excluded from the items subtotal and the discount base.
            $extrasTotal = $allowExtras
                ? (float) $order->extras()->sum('amount')
                : 0.0;

            // In append mode, the incoming $subtotal only reflects NEW lines — add the
            // persisted existing items so totals match the full order.
            if ($appendKot) {
                $subtotal += (float) $order->items()
                    ->whereNotIn('id', $orderItemsCreated)
                    ->sum('amount');
            }

            $taxMode = $restaurant->tax_mode ?? 'item';
            $totalTax = 0.0;

            if ($taxMode === 'order') {
                if (! $appendKot) {
                    $taxes = Tax::query()->select('id', 'tax_percent')->get();
                    foreach ($taxes as $tax) {
                        OrderTax::create([
                            'order_id' => $order->id,
                            'tax_id' => $tax->id,
                        ]);
                        $totalTax += (($subtotal + $extrasTotal) * ((float) $tax->tax_percent / 100));
                    }
                } else {
                    // Append mode: existing OrderTax rows remain. Recompute aggregate
                    // tax against the combined subtotal+extras base using those rows.
                    $taxPercents = $order->taxes()
                        ->join('taxes', 'order_taxes.tax_id', '=', 'taxes.id')
                        ->pluck('taxes.tax_percent');
                    foreach ($taxPercents as $taxPercent) {
                        $totalTax += (($subtotal + $extrasTotal) * ((float) $taxPercent / 100));
                    }
                }
            } else {
                // Item-level tax: sum persisted line tax rows (new + any untouched existing in append).
                $totalTax = (float) $order->items()->sum('tax_amount');
            }

            // Legacy parity (Pos.php::calculateTotal line 2265): delivery fee is
            // added to total for delivery orders. Use the persisted delivery_fee
            // written to the order above so we stay in sync with the stored field.
            $deliveryFee = ($orderTypeValue === 'delivery')
                ? (float) ($validated['delivery_fee'] ?? 0)
                : 0.0;

            $discountType = isset($validated['discount_type']) ? (string) $validated['discount_type'] : null;
            $discountValue = isset($validated['discount_value']) ? (float) $validated['discount_value'] : 0.0;
            if ($discountType === null || $discountValue <= 0) {
                $discountType = null;
                $discountValue = 0.0;
                $discountAmount = 0.0;
            } elseif ($discountType === 'percent') {
                $discountValue = min($discountValue, 100.0);
                $discountAmount = round(($subtotal * $discountValue) / 100, 2);
            } else {
                $discountAmount = min(round($discountValue, 2), round($subtotal, 2));
            }

            $rewardPointDiscount = 0.0;
            $rewardPointsRedeemed = 0;
            $customerIdForReward = isset($validated['customer_id']) ? (int) $validated['customer_id'] : null;

            // Legacy parity (Pos.php::saveOrder): persist reward discount on order totals for
            // both KOT and bill. Ledger redemption (RewardTransaction + balance) runs on bill only.
            $applyRewardPricing = $customerIdForReward && in_array($action, ['bill', 'kot'], true);

            if ($applyRewardPricing) {
                $existingRedeem = RewardTransaction::query()
                    ->where('order_id', $order->id)
                    ->where('type', 'redeem')
                    ->first();

                if ($existingRedeem) {
                    $rewardPointsRedeemed = abs((int) $existingRedeem->points);
                    $rewardPointDiscount = (float) ($existingRedeem->amount_value ?? 0);
                } elseif (
                    in_array('Reward Point', restaurant_modules())
                    && RewardSetting::getForRestaurant($restaurant->id)->enable_reward_point
                    && user_can('Redeem Reward Points')
                ) {
                    $requestedPoints = (int) ($validated['reward_points_redeemed'] ?? 0);
                    if ($requestedPoints > 0) {
                        $rewardCustomer = Customer::find($customerIdForReward);
                        if ($rewardCustomer) {
                            $rewardService = app(RewardPointsService::class);
                            $subtotalForRedeem = round($subtotal, 2);
                            $maxAllowed = $rewardService->calculateMaxRedeemablePoints(
                                $rewardCustomer,
                                (int) $restaurant->id,
                                $subtotalForRedeem
                            );
                            $rewardPointsRedeemed = min($requestedPoints, $maxAllowed);
                            if ($rewardPointsRedeemed > 0) {
                                $rewardPointDiscount = $rewardService->calculateDiscountFromPoints(
                                    $rewardPointsRedeemed,
                                    (int) $restaurant->id
                                );
                            }
                        }
                    }
                }
            }

            $total = round($subtotal + $extrasTotal + $totalTax + $deliveryFee - $discountAmount - $rewardPointDiscount, 2);
            $total = max(0, $total);

            $order->update([
                'sub_total' => round($subtotal, 2),
                'total' => $total,
                'total_tax_amount' => round($totalTax, 2),
                'discount_type' => $discountType,
                'discount_value' => $discountType ? round($discountValue, 2) : null,
                'discount_amount' => $discountAmount > 0 ? $discountAmount : null,
                'reward_point_discount' => $rewardPointDiscount > 0 ? $rewardPointDiscount : null,
                'reward_points_redeemed' => $rewardPointsRedeemed > 0 ? $rewardPointsRedeemed : null,
            ]);

            if (($action === 'bill' || $billAfterKot) && $rewardPointsRedeemed > 0 && $customerIdForReward) {
                $hasRedeem = RewardTransaction::query()
                    ->where('order_id', $order->id)
                    ->where('type', 'redeem')
                    ->exists();

                if (! $hasRedeem) {
                    $rewardCustomer = Customer::find($customerIdForReward);
                    if (! $rewardCustomer) {
                        throw new \RuntimeException(__('Customer not found for reward redemption.'));
                    }
                    $rewardService = app(RewardPointsService::class);
                    $rewardService->redeemPoints($order->fresh(), $rewardCustomer, $rewardPointsRedeemed);
                }
            }

            if ($billAfterKot) {
                $order->update(['status' => 'billed']);
            }

            if (in_array($statusBeforeSave, ['paid', 'payment_due'], true)) {
                OrderPaymentBalanceSync::syncPostPaymentBalance(
                    $order->fresh('payments'),
                    $total,
                    $opensImmediatePayment
                );
            }

            $kotIds = [];
            $kotPrintTargets = [];
            if ($action === 'kot' || ($appendKot && $action === 'bill')) {
                $groupedByKitchen = [];
                foreach ($kotLineSeed as $line) {
                    $groupedByKitchen[$line['kitchen_place_id']][] = $line;
                }

                foreach ($groupedByKitchen as $kitchenPlaceId => $groupedItems) {
                    $kot = Kot::create([
                        'branch_id' => $order->branch_id,
                        'kot_number' => Kot::generateKotNumber($order->branch),
                        'order_id' => $order->id,
                        'order_type_id' => $order->order_type_id,
                        'token_number' => Kot::generateTokenNumber($order->branch_id, $order->order_type_id),
                        'kitchen_place_id' => $kitchenPlaceId,
                        'note' => $validated['note'] ?? null,
                    ]);

                    $kotIds[] = $kot->id;
                    $kotPrintTargets[] = [
                        'id' => $kot->id,
                        'place_id' => (int) $kitchenPlaceId,
                    ];

                    foreach ($groupedItems as $item) {
                        $kotRow = [
                            'kot_id' => $kot->id,
                            'menu_item_id' => $item['menu_item_id'],
                            'menu_item_variation_id' => $item['menu_item_variation_id'] ?? null,
                            'combo_pack_id' => $item['combo_pack_id'] ?? null,
                            'quantity' => $item['qty'],
                            'note' => $item['note'],
                            'order_type_id' => $order->order_type_id,
                            'order_type' => $order->order_type,
                            'is_multi_kitchen' => (bool) ($item['is_multi_kitchen'] ?? false),
                        ];
                        if ($hasKotItemsComboInstanceKey) {
                            $kotRow['combo_instance_key'] = $item['combo_instance_key'] ?? null;
                        }
                        $kotItem = KotItem::create($kotRow);

                        $modifierQtyMap = $item['modifier_option_quantities'] ?? [];
                        if (! empty($modifierQtyMap)) {
                            $kotItem->modifierOptions()->sync(
                                collect($modifierQtyMap)->mapWithKeys(fn ($optionQty, $optionId) => [(int) $optionId => ['quantity' => (int) $optionQty]])->all()
                            );
                        }
                    }
                }
            }

            // Sync the fields written by the final update() back onto the in-memory
            // model so the return value is accurate without a separate SELECT.
            $order->sub_total = round($subtotal, 2);
            $order->total = $total;
            $order->total_tax_amount = round($totalTax, 2);
            $order->discount_type = $discountType;
            $order->discount_value = $discountType ? round($discountValue, 2) : null;
            $order->discount_amount = $discountAmount > 0 ? $discountAmount : null;
            // $status is already 'billed' for action=bill or billAfterKot, 'kot' otherwise.
            $order->status = $status;

            return [
                'order' => $order,
                'order_item_ids' => $orderItemsCreated,
                'kot_ids' => $kotIds,
                'kot_print_targets' => $kotPrintTargets,
                'is_update' => $isUpdate,
            ];
        });

        $kotTickets = self::buildKotPrintTickets(
            $result['kot_ids'] ?? [],
            $restaurant->timezone ?? config('app.timezone', 'UTC')
        );

        // Update session only after transaction succeeds
        if ($sessionDeliveryAppId !== null) {
            if ($sessionDeliveryAppId === false) {
                session()->forget('pos.delivery_app_id');
            } else {
                session()->put('pos.delivery_app_id', $sessionDeliveryAppId);
            }
        }

        // Legacy parity (Pos.php::saveOrder line 3375):
        //   Table::where('id', $this->tableId)->update(['available_status' => $tableStatus]);
        // Keep the `tables.available_status` column in sync with the effective business state.
        // IMPORTANT: Billed orders are still "active" per Table::activeOrder(), so only update
        // to 'available' when the order is truly freed (paid/cancelled). On KOT, set to 'running'.
        $persistedTableId = (int) ($result['order']->table_id ?? 0);
        if ($persistedTableId > 0) {
            // Only update table status on KOT; billing does NOT free the table (it remains active).
            // Table freedom is handled separately via deleteOrder (order cancelled/completed).
            $tableStatus = $action === 'bill' ? null : 'running';
            if ($tableStatus !== null) {
                Table::query()
                    ->where('id', $persistedTableId)
                    ->where('branch_id', $branch->id)
                    ->update(['available_status' => $tableStatus]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => $action === 'bill' ? __('messages.billedSuccess') : __('messages.kotGenerated'),
            'data' => [
                'order_id' => $result['order']->id,
                'order_number' => (string) ($result['order']->order_number ?? ''),
                'formatted_order_number' => (string) ($result['order']->show_formatted_order_number ?? ''),
                'is_update' => (bool) ($result['is_update'] ?? false),
                'order_uuid' => $result['order']->uuid,
                'status' => $result['order']->status,
                'sub_total' => (float) $result['order']->sub_total,
                'total' => (float) $result['order']->total,
                'should_open_payment_modal' => $billFollowUp['open_payment'] || ($action === 'bill' && $openPayment),
                'next' => [
                    'secondary_action' => $billFollowUp['secondary_action'],
                    'open_payment' => $billFollowUp['open_payment'] || ($action === 'bill' && $openPayment),
                    'print_receipt' => $billFollowUp['print_receipt'],
                    'print_kot' => $action === 'kot' && $secondaryAction === 'print',
                    'show_order_detail' => $billFollowUp['show_order_detail'],
                ],
                'kot_ids' => $result['kot_ids'],
                'kot_print_targets' => $result['kot_print_targets'] ?? [],
                // Ready-to-print ticket payloads so Vue POS can print without a second page load.
                'kot_tickets' => $kotTickets,
                'order_item_ids' => $result['order_item_ids'],
                'links' => [
                    'order' => route('pos.order', ['id' => $result['order']->id]),
                    'kot' => route('pos.kot', ['id' => $result['order']->id]),
                    'bill' => route('orders.print', ['id' => $result['order']->id]),
                    // Paths only — Vue resolves against window.location.origin so a
                    // mismatched APP_URL cannot strand the pre-opened print tab.
                    'kot_print_urls' => array_map(
                        fn (array $target) => '/kot/print/'.$target['id'].'/'.$target['place_id'],
                        $result['kot_print_targets'] ?? []
                    ),
                ],
            ],
        ]);
    }

    /**
     * Lean full-payment endpoint for Vue POS (no Livewire round-trip).
     * Split / room-charge remain on the Livewire payment modal.
     */
    public function pay(Request $request, int $id)
    {
        $canAccess = user_can('Create Order') || user_can('Update Order') || user_can('Show Order');
        abort_if(! in_array('Order', restaurant_modules()) || ! $canAccess, 403);

        $branch = branch();
        abort_if(! $branch, 422, 'Branch context is required');

        $validated = $request->validate([
            'payment_method' => ['nullable', 'required_without:split_type', Rule::in(['cash', 'card', 'upi', 'bank_transfer', 'due', 'room_charge'])],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'room_charge_reservation_id' => ['nullable', 'integer'],
            'split_type' => ['nullable', Rule::in(['equal', 'custom', 'items'])],
            'splits' => ['nullable', 'required_with:split_type', 'array'],
            'splits.*.payment_method' => ['required_with:splits', Rule::in(['cash', 'card', 'upi', 'bank_transfer', 'due', 'room_charge'])],
            'splits.*.amount' => ['required_if:split_type,equal,custom', 'numeric', 'min:0'],
            'splits.*.items' => ['required_if:split_type,items', 'array'],
            'splits.*.items.*.order_item_id' => ['required_with:splits.*.items', 'integer'],
            'splits.*.items.*.quantity' => ['required_with:splits.*.items', 'integer', 'min:1'],
            'splits.*.items.*.price' => ['required_with:splits.*.items', 'numeric', 'min:0'],
        ]);

        $epsilon = 0.0001;

        try {
            $result = DB::transaction(function () use ($id, $branch, $validated, $epsilon) {
                $order = Order::query()
                    ->whereKey($id)
                    ->where('branch_id', $branch->id)
                    ->lockForUpdate()
                    ->first();

                if (! $order) {
                    abort(404, __('messages.orderNotFound'));
                }

                if (class_exists(OrderFolioSettlement::class)
                    && OrderFolioSettlement::isChargedToFolio($order)) {
                    return [
                        'error' => true,
                        'status' => 422,
                        'message' => __('modules.order.orderAlreadyPaid'),
                    ];
                }

                $order->loadMissing('payments');
                $outstanding = (float) $order->outstandingAmount();

                if ($outstanding <= $epsilon) {
                    return [
                        'error' => true,
                        'status' => 422,
                        'message' => __('modules.order.orderAlreadyPaid'),
                    ];
                }

                $splitType = $validated['split_type'] ?? null;

                if ($splitType) {
                    if (in_array($splitType, ['equal', 'custom'], true)
                        && $order->splitOrders()->where('status', 'paid')->exists()) {
                        return [
                            'error' => true,
                            'status' => 422,
                            'message' => __('modules.order.orderAlreadyPaid'),
                        ];
                    }

                    if ($splitType === 'equal') {
                        $order->split_type = 'even';
                        $order->saveQuietly();

                        foreach ($validated['splits'] as $split) {
                            $splitAmt = (float) $split['amount'];
                            if ($splitAmt > 0) {
                                \App\Models\SplitOrder::create([
                                    'order_id' => $order->id,
                                    'amount' => $splitAmt,
                                    'payment_method' => $split['payment_method'],
                                    'status' => 'paid'
                                ]);

                                if ($split['payment_method'] !== 'due') {
                                    Payment::create([
                                        'order_id' => $order->id,
                                        'payment_method' => $split['payment_method'],
                                        'amount' => $splitAmt,
                                        'payment_account_id' => BranchPaymentAccountSetting::getDefaultAccount(
                                            (int) $order->branch_id,
                                            $split['payment_method']
                                        )?->id
                                    ]);
                                }
                            }
                        }
                    } elseif ($splitType === 'custom') {
                        $order->split_type = 'custom';
                        $order->saveQuietly();

                        foreach ($validated['splits'] as $split) {
                            $splitAmt = (float) $split['amount'];
                            if ($splitAmt > 0) {
                                \App\Models\SplitOrder::create([
                                    'order_id' => $order->id,
                                    'amount' => $splitAmt,
                                    'payment_method' => $split['payment_method'],
                                    'status' => 'paid'
                                ]);

                                if ($split['payment_method'] !== 'due') {
                                    Payment::create([
                                        'order_id' => $order->id,
                                        'payment_method' => $split['payment_method'],
                                        'amount' => $splitAmt,
                                        'payment_account_id' => BranchPaymentAccountSetting::getDefaultAccount(
                                            (int) $order->branch_id,
                                            $split['payment_method']
                                        )?->id
                                    ]);
                                }
                            }
                        }
                    } elseif ($splitType === 'items') {
                        $order->split_type = 'items';
                        $order->saveQuietly();

                        foreach ($validated['splits'] as $split) {
                            $splitItems = $split['items'] ?? [];
                            if (empty($splitItems)) {
                                continue;
                            }

                            $splitTotal = round(collect($splitItems)->sum(function ($item) {
                                return floatval($item['price']) * intval($item['quantity']);
                            }), 2);

                            if ($splitTotal > 0) {
                                $splitOrder = \App\Models\SplitOrder::create([
                                    'order_id' => $order->id,
                                    'amount' => $splitTotal,
                                    'payment_method' => $split['payment_method'],
                                    'status' => 'paid'
                                ]);

                                if ($split['payment_method'] !== 'due') {
                                    Payment::create([
                                        'order_id' => $order->id,
                                        'payment_method' => $split['payment_method'],
                                        'amount' => $splitTotal,
                                        'payment_account_id' => BranchPaymentAccountSetting::getDefaultAccount(
                                            (int) $order->branch_id,
                                            $split['payment_method']
                                        )?->id
                                    ]);
                                }

                                foreach ($splitItems as $item) {
                                    $splitOrder->items()->create([
                                        'order_item_id' => $item['order_item_id'],
                                        'quantity' => $item['quantity']
                                    ]);
                                }
                            }
                        }
                    }
                } else {
                    $paymentMethod = (string) $validated['payment_method'];
                    $isDueMethod = $paymentMethod === 'due';
                    $isRoomCharge = $paymentMethod === 'room_charge';
                    $rawAmount = max(0, (float) ($validated['amount'] ?? 0));
                    $netPay = ($isDueMethod || $isRoomCharge) ? 0.0 : min($rawAmount, $outstanding);
                    $returnAmount = ($isDueMethod || $isRoomCharge) ? 0.0 : max(0, round($rawAmount - $outstanding, 2));

                    if ($isRoomCharge) {
                        if (!class_exists(PosHotelSupport::class) || !PosHotelSupport::showRoomChargePayment()) {
                            return [
                                'error' => true,
                                'status' => 422,
                                'message' => 'Room charge payment is not enabled.',
                            ];
                        }
                        $resId = $validated['room_charge_reservation_id'] ?? null;
                        if (!$resId) {
                            return [
                                'error' => true,
                                'status' => 422,
                                'message' => __('modules.order.selectRoom'),
                            ];
                        }
                        OrderFolioSettlement::chargeToFolio($order, (int) $resId);
                        $chargedToFolio = true;
                    } else {
                        if (! $isDueMethod && $netPay <= $epsilon) {
                            return [
                                'error' => true,
                                'status' => 422,
                                'message' => __('modules.customer.payment_amount_required'),
                            ];
                        }

                        $leavesOutstanding = $isDueMethod || ($outstanding - $netPay) > $epsilon;
                        if ($leavesOutstanding && ! $order->canRecordDueBalance()) {
                            return [
                                'error' => true,
                                'status' => 422,
                                'message' => __('modules.order.customerRequiredForDuePayment'),
                                'needs_customer' => true,
                            ];
                        }

                        if (! $isDueMethod && $netPay > $epsilon) {
                            Payment::create([
                                'order_id' => $order->id,
                                'payment_method' => $paymentMethod,
                                'amount' => $netPay,
                                'balance' => $returnAmount,
                                'payment_account_id' => BranchPaymentAccountSetting::getDefaultAccount(
                                    (int) $order->branch_id,
                                    $paymentMethod
                                )?->id,
                            ]);
                        }
                    }
                }

                if (!empty($chargedToFolio)) {
                    // Skipped post-payment status computation as OrderFolioSettlement handles it
                } else {
                    $order->refresh();
                    $order->load('payments');

                    $orderPaidAmount = (float) $order->nonDuePaymentsSum();
                    $remaining = round((float) $order->total - $orderPaidAmount, 2);
                    $nextFinancialStatus = $remaining <= $epsilon ? 'paid' : 'payment_due';
                    $currentProgressStatus = $order->order_status?->value ?? (string) ($order->order_status ?? '');

                    $order->amount_paid = $orderPaidAmount;
                    $order->status = $nextFinancialStatus;
                    if (auth()->id()) {
                        $order->pos_user_id = auth()->id();
                    }
                    if (
                        $nextFinancialStatus === 'paid'
                        && ! in_array($currentProgressStatus, ['served', 'delivered', 'cancelled'], true)
                    ) {
                        $order->order_status = OrderStatus::SERVED;
                    }
                    $order->save();

                    Payment::where('order_id', $order->id)->where('payment_method', 'due')->delete();

                    if ($remaining > $epsilon) {
                        if (!$order->canRecordDueBalance()) {
                            return [
                                'error' => true,
                                'status' => 422,
                                'message' => __('modules.order.customerRequiredForDuePayment'),
                                'needs_customer' => true,
                            ];
                        }

                        Payment::create([
                            'order_id' => $order->id,
                            'payment_method' => 'due',
                            'amount' => $remaining,
                            'payment_account_id' => BranchPaymentAccountSetting::getDefaultAccount(
                                (int) $order->branch_id,
                                'due'
                            )?->id,
                        ]);
                    }
                }

                return [
                    'error' => false,
                    'order' => $order->fresh(['payments']),
                ];
            });
        } catch (\Throwable $e) {
            Log::error('POS pay failed: '.$e->getMessage(), ['order_id' => $id]);
            throw $e;
        }

        if (! empty($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'needs_customer' => (bool) ($result['needs_customer'] ?? false),
            ], (int) ($result['status'] ?? 422));
        }

        /** @var Order $order */
        $order = $result['order'];

        if ($order->table_id) {
            $table = Table::query()->find($order->table_id);
            if ($table) {
                $table->update(['available_status' => 'available']);
                if ($table->tableSession) {
                    if ($table->tableSession->isOrderLock() && (int) $table->tableSession->order_id === (int) $order->id) {
                        $table->unlockFromOrder($order->id);
                    } else {
                        $table->tableSession->releaseLock();
                    }
                }
            }
        }

        if ($order->customer_id) {
            $orderIdForBill = (int) $order->id;
            dispatch(function () use ($orderIdForBill) {
                $billOrder = Order::query()->find($orderIdForBill);
                if (! $billOrder || ! $billOrder->customer_id) {
                    return;
                }

                try {
                    SendOrderBillEvent::dispatch($billOrder);
                } catch (\Exception $e) {
                    Log::error('Error sending notification: '.$e->getMessage());
                }
            })->afterResponse();
        }

        $receipt = restaurant()?->receiptSetting;
        $directPrint = $receipt
            && (bool) ($receipt->direct_print_after_payment ?? false)
            && $order->status === 'paid';

        return response()->json([
            'success' => true,
            'message' => __('messages.paymentSuccess') ?: 'Payment recorded',
            'data' => [
                'order_id' => (int) $order->id,
                'status' => (string) $order->status,
                'amount_paid' => (float) $order->amount_paid,
                'total' => (float) $order->total,
                'direct_print' => $directPrint,
                'print_url' => '/orders/print/'.$order->id,
            ],
        ]);
    }

    public function updateTip(Request $request, int $id)
    {
        $canAccess = user_can('Create Order') || user_can('Update Order') || user_can('Show Order');
        abort_if(! in_array('Order', restaurant_modules()) || ! $canAccess, 403);

        $branch = branch();
        abort_if(! $branch, 422, 'Branch context is required');

        $validated = $request->validate([
            'tip_amount' => ['required', 'numeric', 'min:0'],
            'tip_note' => ['nullable', 'string', 'max:255'],
        ]);

        $order = Order::query()
            ->whereKey($id)
            ->where('branch_id', $branch->id)
            ->firstOrFail();

        if ($order->status === 'paid') {
            abort(422, 'Cannot add tip to a paid order');
        }

        $previousTip = (float) ($order->tip_amount ?? 0);
        $newTip = (float) $validated['tip_amount'];

        DB::transaction(function () use ($order, $previousTip, $newTip, $validated) {
            $order->total = round((float) $order->total - $previousTip + $newTip, 2);
            $order->tip_amount = $newTip;
            $order->tip_note = $newTip > 0 ? $validated['tip_note'] : null;
            $order->save();
        });

        return response()->json([
            'success' => true,
            'message' => $newTip > 0 ? __('messages.tipAddedSuccessfully') : __('messages.tipRemovedSuccessfully'),
            'data' => [
                'order_id' => (int) $order->id,
                'total' => (float) $order->total,
                'tip_amount' => (float) $order->tip_amount,
                'tip_note' => (string) $order->tip_note,
            ],
        ]);
    }

    /**
     * Compact KOT ticket payloads for Vue POS instant print (no second page load).
     *
     * @param  array<int>  $kotIds
     * @return array<int, array<string, mixed>>
     */
    private static function buildKotPrintTickets(array $kotIds, string $timezone): array
    {
        $kotIds = array_values(array_filter(array_map('intval', $kotIds)));
        if ($kotIds === []) {
            return [];
        }

        $kots = Kot::query()
            ->with([
                'items.menuItem:id,item_name',
                'items.menuItemVariation:id,variation',
                'items.modifierOptions:id,name',
                'order.table:id,table_code',
                'order.waiter:id,name',
                'kotPlace:id,name',
            ])
            ->whereIn('id', $kotIds)
            ->orderBy('id')
            ->get();

        return $kots->map(function (Kot $kot) use ($timezone) {
            $createdAt = $kot->created_at?->timezone($timezone);

            return [
                'id' => (int) $kot->id,
                'place_id' => (int) ($kot->kitchen_place_id ?? 0),
                'place_name' => (string) ($kot->kotPlace?->name ?? ''),
                'kot_number' => (string) ($kot->kot_number ?? ''),
                'token_number' => $kot->token_number,
                'order_number' => (string) ($kot->order?->show_formatted_order_number
                    ?? $kot->order?->order_number
                    ?? ''),
                'table' => (string) ($kot->order?->table?->table_code ?? '-'),
                'date' => $createdAt ? $createdAt->format('d-m-Y') : '',
                'time' => $createdAt ? $createdAt->format('h:i A') : '',
                'waiter' => $kot->order?->waiter?->name,
                'order_type' => $kot->order?->order_type
                    ? ucwords(str_replace('_', ' ', (string) $kot->order->order_type))
                    : null,
                'note' => $kot->note,
                'items' => $kot->items->map(function ($item) {
                    return [
                        'name' => (string) ($item->menuItem?->item_name ?? 'Item'),
                        'variation' => $item->menuItemVariation?->variation,
                        'qty' => (int) $item->quantity,
                        'note' => $item->note,
                        'modifiers' => $item->modifierOptions->map(function ($modifier) {
                            return [
                                'name' => (string) $modifier->name,
                                'qty' => (int) ($modifier->pivot->quantity ?? 1),
                            ];
                        })->values()->all(),
                    ];
                })->values()->all(),
            ];
        })->values()->all();
    }

    /**
     * @param  array<int>  $packIds
     * @return array<int, int> pack id => number of combo component rows per instance
     */
    private static function comboPackSlotCounts(array $packIds): array
    {
        if ($packIds === []) {
            return [];
        }

        return ComboPack::query()
            ->whereIn('id', $packIds)
            ->withCount('comboPackItems')
            ->get()
            ->mapWithKeys(fn (ComboPack $p) => [(int) $p->id => max(1, (int) $p->combo_pack_items_count)])
            ->all();
    }

    /**
     * Assign combo_instance_key to each row id by chunking consecutive same-pack lines
     * into groups of the pack's slot count (supports multiple instances of the same pack).
     *
     * @param  \Illuminate\Support\Collection<int, \Illuminate\Database\Eloquent\Model>  $rows
     * @param  array<int, int>  $slotCountByPackId
     * @return array<int, string> row id => instance key
     */
    private static function comboInstanceKeyMapForRows(\Illuminate\Support\Collection $rows, array $slotCountByPackId): array
    {
        $map = [];
        $counters = [];
        $n = $rows->count();
        $i = 0;

        while ($i < $n) {
            $row = $rows[$i];
            if (empty($row->combo_pack_id)) {
                $i++;

                continue;
            }

            $packId = (int) $row->combo_pack_id;
            $slots = (int) ($slotCountByPackId[$packId] ?? 1);
            $slots = max(1, $slots);
            $taken = 0;

            while ($taken < $slots && ($i + $taken) < $n) {
                $r = $rows[$i + $taken];
                if ((int) ($r->combo_pack_id ?? 0) !== $packId) {
                    break;
                }
                $taken++;
            }

            if ($taken === 0) {
                $i++;

                continue;
            }

            $counters[$packId] = ($counters[$packId] ?? 0) + 1;
            $key = 'combo_'.$packId.'_'.$counters[$packId];

            for ($k = 0; $k < $taken; $k++) {
                $r = $rows[$i + $k];
                $map[(int) $r->id] = $key;
            }

            $i += $taken;
        }

        return $map;
    }

    /**
     * @param  array<int>  $packIds
     * @return array<int, string>
     */
    private static function comboPackNamesById(array $packIds): array
    {
        if ($packIds === []) {
            return [];
        }

        return ComboPack::query()
            ->whereIn('id', $packIds)
            ->get()
            ->mapWithKeys(fn (ComboPack $p) => [
                (int) $p->id => (string) $p->getTranslation('name', app()->getLocale()),
            ])
            ->all();
    }
}
