<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kot;
use App\Models\KotItem;
use App\Models\KotPlace;
use App\Models\ComboPack;
use App\Models\MenuItem;
use App\Models\MenuItemVariation;
use App\Models\ModifierOption;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderType;
use App\Models\OrderTax;
use App\Models\Tax;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PosVueOrderController extends Controller
{
    public function store(Request $request)
    {
        abort_if(!in_array('Order', restaurant_modules()) || !user_can('Create Order'), 403);

        $validated = $request->validate([
            'action' => ['nullable', 'string', Rule::in(['kot', 'bill'])],
            'open_payment' => ['nullable', 'boolean'],
            'order_type_id' => ['nullable', 'integer', 'exists:order_types,id'],
            'waiter_id' => ['nullable', 'integer', 'exists:users,id'],
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
        ]);

        $action = $validated['action'] ?? 'kot';
    $openPayment = (bool) ($validated['open_payment'] ?? false);
        $status = $action === 'bill' ? 'billed' : 'kot';

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

        $result = DB::transaction(function () use ($validated, $action, $status, $branch, $orderType, $orderTypeValue, $restaurant) {
            $numberData = Order::generateOrderNumber($branch);

            $order = Order::create([
                'order_number' => $numberData['order_number'],
                'formatted_order_number' => $numberData['formatted_order_number'],
                'date_time' => now(),
                'waiter_id' => $validated['waiter_id'] ?? null,
                'sub_total' => 0,
                'total' => 0,
                'order_type' => $orderTypeValue,
                'order_type_id' => $orderType?->id,
                'custom_order_type_name' => $orderType?->order_type_name,
                'status' => $status,
                'order_status' => 'confirmed',
                'placed_via' => 'pos_vue',
                'tax_mode' => $restaurant->tax_mode ?? 'item',
            ]);

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

            $taxMode = $restaurant->tax_mode ?? 'item';
            $totalTax = 0.0;

            if ($taxMode === 'order') {
                $taxes = Tax::query()->select('id', 'tax_percent')->get();
                foreach ($taxes as $tax) {
                    OrderTax::create([
                        'order_id' => $order->id,
                        'tax_id' => $tax->id,
                    ]);
                    $totalTax += ($subtotal * ((float) $tax->tax_percent / 100));
                }
            }

            $total = round($subtotal + $totalTax, 2);

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
            ];
        });

        return response()->json([
            'success' => true,
            'message' => $action === 'bill' ? __('messages.billedSuccess') : __('messages.kotGenerated'),
            'data' => [
                'order_id' => $result['order']->id,
                'order_uuid' => $result['order']->uuid,
                'status' => $result['order']->status,
                'sub_total' => (float) $result['order']->sub_total,
                'total' => (float) $result['order']->total,
                'should_open_payment_modal' => $action === 'bill' && $openPayment,
                'kot_ids' => $result['kot_ids'],
                'order_item_ids' => $result['order_item_ids'],
                'links' => [
                    'order' => route('pos.order', ['id' => $result['order']->id]),
                    'kot' => route('pos.kot', ['id' => $result['order']->id]),
                ],
            ],
        ]);
    }
}
