<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kot;
use App\Models\KotItem;
use App\Models\KotPlace;
use App\Models\ComboPack;
use App\Models\DeliveryPlatform;
use App\Models\MenuItem;
use App\Models\MenuItemVariation;
use App\Models\ModifierOption;
use App\Models\Order;
use App\Models\OrderExtra;
use App\Models\OrderItem;
use App\Models\OrderType;
use App\Models\OrderTax;
use App\Models\Table;
use App\Models\TableSession;
use App\Models\Tax;
use App\Services\Pos\BillSecondaryActionResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PosVueOrderController extends Controller
{
    public function show(int $id)
    {
        abort_if(!in_array('Order', restaurant_modules()) || !user_can('View Order'), 403);

        $branch = branch();
        abort_if(!$branch, 422, 'Branch context is required');

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

        $comboInstancesByPack = [];
        $currentComboPackId = null;
        $currentComboInstanceKey = null;
        $itemsInCurrentCombo = 0;
        $resolveUnitPrice = static function ($item): float {
            $unitPrice = (float) ($item->price ?? 0);

            if ($unitPrice <= 0) {
                $qty = (int) ($item->quantity ?? 0);
                $amount = (float) ($item->amount ?? 0);
                if ($qty > 0 && $amount > 0) {
                    $unitPrice = round($amount / $qty, 2);
                }
            }

            if ($unitPrice <= 0) {
                $unitPrice = (float) ($item->menuItemVariation?->price ?? 0);
            }

            return $unitPrice > 0 ? $unitPrice : 0.0;
        };

        $lines = $order->items->map(function ($item) use (&$comboInstancesByPack, &$currentComboPackId, &$currentComboInstanceKey, &$itemsInCurrentCombo, $resolveUnitPrice) {
            $comboInstanceKey = null;
            if (!empty($item->combo_pack_id)) {
                $packId = (int) $item->combo_pack_id;
                
                // Start a new combo instance if this is a different pack or we've completed the current one
                if ($packId !== $currentComboPackId) {
                    $currentComboPackId = $packId;
                    $itemsInCurrentCombo = 1;
                    $comboInstancesByPack[$packId] = ($comboInstancesByPack[$packId] ?? 0) + 1;
                    $currentComboInstanceKey = 'combo_' . $packId . '_' . $comboInstancesByPack[$packId];
                } else {
                    // Same pack, increment item count in current combo
                    $itemsInCurrentCombo++;
                }
                
                $comboInstanceKey = $currentComboInstanceKey;
            } else {
                // Reset combo tracking when we hit a non-combo item
                $currentComboPackId = null;
                $currentComboInstanceKey = null;
                $itemsInCurrentCombo = 0;
            }

            $modifierQtyMap = $item->modifierOptions
                ->mapWithKeys(fn($opt) => [(int) $opt->id => (int) ($opt->pivot->quantity ?? 1)])
                ->all();

            $qty = (int) ($item->quantity ?? 1);
            $unitPrice = $resolveUnitPrice($item);
            $amount = (float) ($item->amount ?? 0);
            if ($amount <= 0 && $unitPrice > 0 && $qty > 0) {
                $amount = round($unitPrice * $qty, 2);
            }

            return [
                'order_item_id' => (int) $item->id,
                'menu_item_id' => (int) $item->menu_item_id,
                'item_name' => (string) ($item->menuItem?->item_name ?? ''),
                'menu_item_variation_id' => $item->menu_item_variation_id ? (int) $item->menu_item_variation_id : null,
                'variation_name' => (string) ($item->menuItemVariation?->variation ?? ''),
                'qty' => $qty,
                'unit_price' => $unitPrice,
                'amount' => $amount,
                'note' => $item->note,
                'combo_pack_id' => $item->combo_pack_id ? (int) $item->combo_pack_id : null,
                'combo_instance_key' => $comboInstanceKey,
                'modifier_option_quantities' => $modifierQtyMap,
            ];
        })->values();

        $kots = $order->kot->map(function ($kot) use ($resolveUnitPrice) {
            $comboInstancesByPack = [];
            $currentComboPackId = null;
            $currentComboInstanceKey = null;

            $kotLines = $kot->items->map(function ($item) use (&$comboInstancesByPack, &$currentComboPackId, &$currentComboInstanceKey, $resolveUnitPrice) {
                $comboInstanceKey = null;
                if (!empty($item->combo_pack_id)) {
                    $packId = (int) $item->combo_pack_id;

                    if ($packId !== $currentComboPackId) {
                        $currentComboPackId = $packId;
                        $comboInstancesByPack[$packId] = ($comboInstancesByPack[$packId] ?? 0) + 1;
                        $currentComboInstanceKey = 'combo_' . $packId . '_' . $comboInstancesByPack[$packId];
                    }

                    $comboInstanceKey = $currentComboInstanceKey;
                } else {
                    $currentComboPackId = null;
                    $currentComboInstanceKey = null;
                }

                $modifierQtyMap = $item->modifierOptions
                    ->mapWithKeys(fn($opt) => [(int) $opt->id => (int) ($opt->pivot->quantity ?? 1)])
                    ->all();

                $qty = (int) ($item->quantity ?? 1);
                $unitPrice = $resolveUnitPrice($item);
                $amount = (float) ($item->amount ?? 0);
                if ($amount <= 0 && $unitPrice > 0 && $qty > 0) {
                    $amount = round($unitPrice * $qty, 2);
                }

                return [
                    'kot_item_id' => (int) $item->id,
                    'menu_item_id' => (int) $item->menu_item_id,
                    'item_name' => (string) ($item->menuItem?->item_name ?? ''),
                    'menu_item_variation_id' => $item->menu_item_variation_id ? (int) $item->menu_item_variation_id : null,
                    'variation_name' => (string) ($item->menuItemVariation?->variation ?? ''),
                    'qty' => $qty,
                    'unit_price' => $unitPrice,
                    'amount' => $amount,
                    'note' => (string) ($item->note ?? ''),
                    'status' => (string) ($item->status ?? ''),
                    'combo_pack_id' => $item->combo_pack_id ? (int) $item->combo_pack_id : null,
                    'combo_instance_key' => $comboInstanceKey,
                    'modifier_option_quantities' => $modifierQtyMap,
                ];
            })->values();

            return [
                'id' => (int) $kot->id,
                'kot_number' => (string) ($kot->kot_number ?? ''),
                'created_at' => $kot->created_at ? $kot->created_at->toIso8601String() : null,
                'status' => (string) ($kot->status ?? ''),
                'lines' => $kotLines,
            ];
        })->values();

        $customerPhone = null;
        if ($order->customer?->phone) {
            $phoneCode = trim((string) ($order->customer->phone_code ?? ''));
            $phone = trim((string) $order->customer->phone);
            $customerPhone = $phoneCode !== '' ? $phoneCode . $phone : $phone;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'order' => [
                    'id' => (int) $order->id,
                    'order_number' => (string) ($order->order_number ?? ''),
                    'formatted_order_number' => (string) ($order->show_formatted_order_number ?? ''),
                    'status' => (string) $order->status,
                    'order_status' => $order->order_status?->value ?? (string) ($order->order_status ?? ''),
                    'order_type' => (string) ($order->order_type ?? 'dine_in'),
                    'order_type_id' => $order->order_type_id ? (int) $order->order_type_id : null,
                    'delivery_app_id' => $order->delivery_app_id ? (int) $order->delivery_app_id : null,
                    'delivery_executive_id' => $order->delivery_executive_id ? (int) $order->delivery_executive_id : null,
                    'delivery_fee' => (float) ($order->delivery_fee ?? 0),
                    'waiter_id' => $order->waiter_id ? (int) $order->waiter_id : null,
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
                    'customer_lat' => $order->customer_lat !== null ? (float) $order->customer_lat : null,
                    'customer_lng' => $order->customer_lng !== null ? (float) $order->customer_lng : null,
                    'note' => (string) ($order->note ?? ''),
                    'sub_total' => (float) ($order->sub_total ?? 0),
                    'total' => (float) ($order->total ?? 0),
                    // Legacy parity (Pos.php mount): custom_extras loaded from order_extras.
                    // Only surfaced when the setting is enabled so the UI never appears
                    // for restaurants that have it turned off.
                    'allow_custom_order_extras' => (bool) (restaurant()->allow_custom_order_extras ?? false),
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
                    ],
                    'lines' => $lines,
                    'kots' => $kots,
                ],
            ],
        ]);
    }

    public function store(Request $request)
    {
        abort_if(!in_array('Order', restaurant_modules()) || !user_can('Create Order'), 403);

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
            // Legacy parity (Pos.php::normalizeOrderExtras / syncOrderExtras):
            // optional per-order custom extras, each with {amount, note}.
            'custom_extras' => ['nullable', 'array'],
            'custom_extras.*.amount' => ['nullable', 'numeric', 'min:0'],
            'custom_extras.*.note' => ['nullable', 'string'],
            // New-KOT mode flag: when the cart is an "append-only" delta for an
            // existing order, the store path must preserve existing items/KOTs
            // (mirrors Pos.php::$appendOnlyKotSave).
            'append_kot' => ['nullable', 'boolean'],
        ]);

        $editingOrderId = isset($validated['order_id']) ? (int) $validated['order_id'] : null;
        $action = $validated['action'] ?? 'kot';
        $secondaryAction = $validated['secondary_action'] ?? null;
        $openPayment = (bool) ($validated['open_payment'] ?? false);
        $status = $action === 'bill' ? 'billed' : 'kot';
        $billFollowUp = app(BillSecondaryActionResolver::class)->resolve($action, $secondaryAction);
        // Append-only KOT save is valid only for `kot` action against an existing order.
        // Legacy parity (Pos.php::$appendOnlyKotSave): the New KOT screen posts
        // a delta of new lines only. Regardless of action (kot or bill), the
        // server must preserve existing items/KOTs/extras. Status transitions
        // on bill are still applied via the update payload below.
        $appendKot = $editingOrderId && !empty($validated['append_kot']);

        $branch = branch();
        $restaurant = restaurant();
        abort_if(!$branch || !$restaurant, 422, 'Branch/restaurant context is required');

        $orderType = null;
        if (!empty($validated['order_type_id'])) {
            $orderType = OrderType::query()->find($validated['order_type_id']);
        }

        $orderTypeValue = $orderType?->slug
            ?: ($orderType?->type ? strtolower((string) $orderType->type) : 'dine_in');

        if (!in_array($orderTypeValue, ['dine_in', 'delivery', 'pickup'], true)) {
            $orderTypeValue = 'dine_in';
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
        if (array_key_exists('table_id', $validated) && $validated['table_id']) {
            $table = Table::query()
                ->where('id', (int) $validated['table_id'])
                ->where('branch_id', $branch->id)
                ->first();

            abort_if(!$table, 422, 'Selected table is not available in this branch.');
            $resolvedTableId = (int) $table->id;
        }

        $result = DB::transaction(function () use ($validated, $editingOrderId, $action, $status, $branch, $orderType, $orderTypeValue, $restaurant, $deliveryAppId, $appendKot, $resolvedTableId) {
            // Note: Session updates are performed after the transaction succeeds (below)
            $isUpdate = false;

            if ($editingOrderId) {
                $order = Order::query()
                    ->where('id', $editingOrderId)
                    ->where('branch_id', $branch->id)
                    ->firstOrFail();

                $isUpdate = true;

                // Legacy parity (Pos.php saveOrder):
                //   - On `bill`, existing KOTs are preserved so order_detail.blade can render $kotList.
                //   - On `kot` with a FULL cart (non-append), items/KOTs are wiped and recreated.
                //   - On `kot` in APPEND mode (New KOT flow from /pos/kot/{id}), everything is
                //     preserved and only the delta lines are appended + new KOT created below.
                if (!$appendKot) {
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
                    'customer_id' => $validated['customer_id'] ?? null,
                    'delivery_app_id' => $deliveryAppId,
                    'delivery_executive_id' => ($orderTypeValue === 'delivery') ? ($validated['delivery_executive_id'] ?? null) : null,
                    'delivery_fee' => ($orderTypeValue === 'delivery') ? (float) ($validated['delivery_fee'] ?? 0) : 0,
                    // Legacy parity (Pos.php::saveOrder line 2913):
                    //   'table_id' => $this->tableId ?? $order->table_id
                    // — preserve the existing link if the UI didn't send a new one.
                    'table_id' => $resolvedTableId ?? $order->table_id,
                    'order_type' => $orderTypeValue,
                    'order_type_id' => $orderType?->id,
                    'custom_order_type_name' => $orderType?->order_type_name,
                    'order_status' => 'confirmed',
                    'placed_via' => 'pos',
                    'tax_mode' => $restaurant->tax_mode ?? 'item',
                ];

                if (!$appendKot) {
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
                $numberData = Order::generateOrderNumber($branch);

                $order = Order::create([
                    'order_number' => $numberData['order_number'],
                    'formatted_order_number' => $numberData['formatted_order_number'],
                    'date_time' => now(),
                    'waiter_id' => $validated['waiter_id'] ?? null,
                    'customer_id' => $validated['customer_id'] ?? null,
                    'delivery_app_id' => $deliveryAppId,
                    'delivery_executive_id' => ($orderTypeValue === 'delivery') ? ($validated['delivery_executive_id'] ?? null) : null,
                    'delivery_fee' => ($orderTypeValue === 'delivery') ? (float) ($validated['delivery_fee'] ?? 0) : 0,
                    // Legacy parity (Pos.php::saveOrder line 2863):
                    //   'table_id' => $this->tableId
                    // OrderObserver::created auto-locks the table via lockForOrder
                    // once this is set, matching legacy table-lock-on-order flow.
                    'table_id' => $resolvedTableId,
                    'sub_total' => 0,
                    'total' => 0,
                    'order_type' => $orderTypeValue,
                    'order_type_id' => $orderType?->id,
                    'custom_order_type_name' => $orderType?->order_type_name,
                    'status' => $status,
                    'order_status' => 'confirmed',
                    'placed_via' => 'pos',
                    'tax_mode' => $restaurant->tax_mode ?? 'item',
                ]);
            }

            $subtotal = 0.0;
            $orderItemsCreated = [];
            $kotLineSeed = [];

            foreach ($validated['lines'] as $line) {
                $menuItem = MenuItem::query()->findOrFail((int) $line['menu_item_id']);
                $variation = null;
                $variationId = isset($line['menu_item_variation_id']) ? (int) $line['menu_item_variation_id'] : null;

                if ($variationId) {
                    $variation = MenuItemVariation::query()
                        ->where('id', $variationId)
                        ->where('menu_item_id', $menuItem->id)
                        ->first();
                }

                $qty = (int) $line['qty'];
                $comboPackId = isset($line['combo_pack_id']) ? (int) $line['combo_pack_id'] : null;
                $isComboItem = $comboPackId && !empty($line['combo_instance_key']);

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

                $modifierOptions = !empty($modifierQtyMap)
                    ? ModifierOption::query()->whereIn('id', array_keys($modifierQtyMap))->get()->keyBy('id')
                    : collect();

                $basePrice = $variation
                    ? (float) ($variation->price ?? 0)
                    : (float) ($menuItem->price ?? 0);

                $comboOriginalUnitPrice = null;
                $comboDiscountPerUnit = 0.0;

                if ($isComboItem) {
                    $combo = ComboPack::query()
                        ->with(['comboPackItems.menuItem', 'comboPackItems.menuItemVariation'])
                        ->findOrFail($comboPackId);

                    $comboPricing = collect($combo->calculateComboItemPrices($orderType?->id, null));
                    $comboMatch = $comboPricing->first(function ($entry) use ($menuItem, $variation) {
                        $comboItem = $entry['combo_item'];
                        $comboVariationId = $comboItem->menu_item_variation_id ? (int) $comboItem->menu_item_variation_id : null;
                        $lineVariationId = $variation?->id ? (int) $variation->id : null;

                        return (int) $comboItem->menu_item_id === (int) $menuItem->id
                            && $comboVariationId === $lineVariationId;
                    });

                    abort_if(!$comboMatch, 422, 'Invalid combo line payload');

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
                $amount = round($qty * $unitPrice, 2);
                $subtotal += $amount;

                $orderItem = OrderItem::create([
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
                    'is_combo_item' => (bool) $isComboItem,
                    'amount' => $amount,
                    'note' => $line['note'] ?? null,
                ]);

                if (!empty($modifierQtyMap)) {
                    $orderItem->modifierOptions()->sync(
                        collect($modifierQtyMap)->mapWithKeys(fn($optionQty, $optionId) => [(int) $optionId => ['quantity' => (int) $optionQty]])->all()
                    );
                }

                $orderItemsCreated[] = $orderItem->id;

                $kitchenIds = method_exists($menuItem, 'getKitchenPlaceIds')
                    ? ($menuItem->getKitchenPlaceIds() ?? [])
                    : [];

                if (empty($kitchenIds)) {
                    $defaultKotPlace = KotPlace::query()
                        ->where('branch_id', $order->branch_id)
                        ->where('is_default', true)
                        ->value('id');

                    if (!$defaultKotPlace) {
                        $defaultKotPlace = KotPlace::query()
                            ->where('branch_id', $order->branch_id)
                            ->value('id');
                    }

                    if ($defaultKotPlace) {
                        $kitchenIds = [$defaultKotPlace];
                    }
                }

                if (!empty($kitchenIds)) {
                    $kotLineSeed[] = [
                        'kitchen_place_id' => (int) $kitchenIds[0],
                        'menu_item_id' => $menuItem->id,
                        'menu_item_variation_id' => $variation?->id,
                        'combo_pack_id' => $isComboItem ? $comboPackId : null,
                        'qty' => $qty,
                        'note' => $line['note'] ?? null,
                        'modifier_option_quantities' => $modifierQtyMap,
                    ];
                }
            }

            // Legacy parity (Pos.php::syncOrderExtras): persist custom extras when the
            // setting is enabled. On bill/kot with a full cart we delete + recreate;
            // on append-only New KOT we keep existing rows untouched.
            $allowExtras = (bool) ($restaurant->allow_custom_order_extras ?? false);
            if ($allowExtras && !$appendKot) {
                $order->extras()->delete();

                foreach (($validated['custom_extras'] ?? []) as $extraRow) {
                    if (!is_array($extraRow)) {
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
                if (!$appendKot) {
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
            }

            // Legacy parity (Pos.php::calculateTotal line 2265): delivery fee is
            // added to total for delivery orders. Use the persisted delivery_fee
            // written to the order above so we stay in sync with the stored field.
            $deliveryFee = ($orderTypeValue === 'delivery')
                ? (float) ($validated['delivery_fee'] ?? 0)
                : 0.0;

            $total = round($subtotal + $extrasTotal + $totalTax + $deliveryFee, 2);

            $order->update([
                'sub_total' => round($subtotal, 2),
                'total' => $total,
                'total_tax_amount' => round($totalTax, 2),
            ]);

            $kotIds = [];
            if ($action === 'kot') {
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

                    foreach ($groupedItems as $item) {
                        $kotItem = KotItem::create([
                            'kot_id' => $kot->id,
                            'menu_item_id' => $item['menu_item_id'],
                            'menu_item_variation_id' => $item['menu_item_variation_id'] ?? null,
                            'combo_pack_id' => $item['combo_pack_id'] ?? null,
                            'quantity' => $item['qty'],
                            'note' => $item['note'],
                            'order_type_id' => $order->order_type_id,
                            'order_type' => $order->order_type,
                        ]);

                        $modifierQtyMap = $item['modifier_option_quantities'] ?? [];
                        if (!empty($modifierQtyMap)) {
                            $kotItem->modifierOptions()->sync(
                                collect($modifierQtyMap)->mapWithKeys(fn($optionQty, $optionId) => [(int) $optionId => ['quantity' => (int) $optionQty]])->all()
                            );
                        }
                    }
                }
            }

            return [
                'order' => $order->fresh(),
                'order_item_ids' => $orderItemsCreated,
                'kot_ids' => $kotIds,
                'is_update' => $isUpdate,
            ];
        });

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
                    'show_order_detail' => $billFollowUp['show_order_detail'],
                ],
                'kot_ids' => $result['kot_ids'],
                'order_item_ids' => $result['order_item_ids'],
                'links' => [
                    'order' => route('pos.order', ['id' => $result['order']->id]),
                    'kot' => route('pos.kot', ['id' => $result['order']->id]),
                    'bill' => route('orders.print', ['id' => $result['order']->id]),
                    'kot_print_urls' => array_map(
                        fn ($kotId) => route('kot.print', ['id' => $kotId]),
                        $result['kot_ids'] ?? []
                    ),
                ],
            ],
        ]);
    }
}
