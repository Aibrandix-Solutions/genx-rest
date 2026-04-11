<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\ItemModifier;
use App\Models\ModifierGroup;
use App\Models\ComboPack;
use App\Models\Order;
use App\Services\PosBootstrapService;

class PosController extends Controller
{

    public function index()
    {
        abort_if((!in_array('Order', restaurant_modules()) || !user_can('Create Order')), 403);
        return view('pos.index');
    }

    public function vue(PosBootstrapService $bootstrapService)
    {
        abort_if((!in_array('Order', restaurant_modules()) || !user_can('Create Order')), 403);

        $bootstrap = $bootstrapService->resolve();
        $data = $bootstrap['data'] ?? [];

        $menuList = Menu::query()
            ->select('id', 'menu_name')
            ->orderBy('id')
            ->get()
            ->map(function ($menu) {
                return [
                    'id' => (int) $menu->id,
                    'menu_name' => (string) $menu->getTranslation('menu_name', session('locale', app()->getLocale())),
                ];
            })
            ->values();

        $menuItems = MenuItem::query()
            ->with([
                'variations:id,menu_item_id,variation,price',
            ])
            ->withCount(['variations', 'modifierGroups'])
            ->select('id', 'menu_id', 'item_category_id', 'type', 'price', 'in_stock', 'image')
            ->orderBy('id')
            ->get();

        $itemIds = $menuItems->pluck('id')->all();

        $itemModifierRows = ItemModifier::query()
            ->whereIn('menu_item_id', $itemIds)
            ->select('menu_item_id', 'menu_item_variation_id', 'modifier_group_id')
            ->get();

        $modifierGroupIds = $itemModifierRows->pluck('modifier_group_id')->unique()->values()->all();

        $modifierGroupsById = ModifierGroup::query()
            ->with(['options:id,modifier_group_id,name,price'])
            ->whereIn('id', $modifierGroupIds)
            ->get()
            ->mapWithKeys(function ($group) {
                return [
                    $group->id => [
                        'id' => (int) $group->id,
                        'name' => (string) $group->name,
                        'options' => $group->options->map(function ($opt) {
                            return [
                                'id' => (int) $opt->id,
                                'name' => (string) $opt->name,
                                'price' => (float) ($opt->price ?? 0),
                            ];
                        })->values()->all(),
                    ],
                ];
            });

        $baseGroupsByItem = [];
        $variationGroupsByItem = [];

        foreach ($itemModifierRows as $row) {
            $itemId = (int) $row->menu_item_id;
            $groupId = (int) $row->modifier_group_id;
            if (!isset($modifierGroupsById[$groupId])) {
                continue;
            }

            if ($row->menu_item_variation_id) {
                $variationId = (int) $row->menu_item_variation_id;
                $variationGroupsByItem[$itemId] ??= [];
                $variationGroupsByItem[$itemId][$variationId] ??= [];
                $variationGroupsByItem[$itemId][$variationId][] = $modifierGroupsById[$groupId];
                continue;
            }

            $baseGroupsByItem[$itemId] ??= [];
            $baseGroupsByItem[$itemId][] = $modifierGroupsById[$groupId];
        }

        $menuItems = $menuItems->map(function ($item) use ($baseGroupsByItem, $variationGroupsByItem) {
            $itemId = (int) $item->id;
                return [
                    'id' => (int) $item->id,
                    'menu_id' => (int) ($item->menu_id ?? 0),
                    'item_category_id' => (int) ($item->item_category_id ?? 0),
                    'item_name' => (string) $item->item_name,
                    'type' => (string) ($item->type ?? 'veg'),
                    'price' => (float) ($item->price ?? 0),
                    'in_stock' => (bool) ($item->in_stock ?? true),
                    'item_photo_url' => (string) $item->item_photo_url,
                    'variations_count' => (int) ($item->variations_count ?? 0),
                    'modifier_groups_count' => (int) ($item->modifier_groups_count ?? 0),
                    'variations' => $item->variations->map(function ($variation) {
                        return [
                            'id' => (int) $variation->id,
                            'variation' => (string) ($variation->variation ?? ''),
                            'price' => (float) ($variation->price ?? 0),
                        ];
                    })->values()->all(),
                    'modifier_groups' => array_values($baseGroupsByItem[$itemId] ?? []),
                    'variation_modifier_groups' => collect($variationGroupsByItem[$itemId] ?? [])
                        ->mapWithKeys(function ($groups, $variationId) {
                            return [(string) $variationId => array_values($groups)];
                        })->all(),
                ];
            })
            ->values();

        $payload = [
            'cached' => (bool) ($bootstrap['cached'] ?? false),
            'menus' => $menuList,
            'categories' => collect($data['categories'] ?? [])->map(function ($category) {
                return [
                    'id' => (int) ($category->id ?? 0),
                    'category_name' => (string) ($category->category_name ?? ''),
                ];
            })->values(),
            'items' => $menuItems,
            'order_types' => collect($data['order_types'] ?? [])->map(function ($orderType) {
                return [
                    'id' => (int) ($orderType->id ?? 0),
                    'order_type_name' => (string) ($orderType->order_type_name ?? ''),
                    'slug' => (string) ($orderType->slug ?? ''),
                ];
            })->values(),
            'waiters' => collect($data['waiters'] ?? [])->map(function ($waiter) {
                return [
                    'id' => (int) ($waiter->id ?? 0),
                    'name' => (string) ($waiter->name ?? ''),
                ];
            })->values(),
            'taxes' => collect($data['taxes'] ?? [])->map(function ($tax) {
                return [
                    'id' => (int) ($tax->id ?? 0),
                    'tax_name' => (string) ($tax->tax_name ?? ''),
                    'tax_percent' => (float) ($tax->tax_percent ?? 0),
                ];
            })->values(),
            'tax_mode' => (string) ($data['tax_mode'] ?? 'item'),
            'currency_symbol' => (string) (restaurant()->currency?->currency_symbol ?? '$'),
        ];

        $branch = branch();
        $comboPacks = collect([]);

        if ($branch) {
            $comboPacks = ComboPack::query()
                ->where('branch_id', $branch->id)
                ->where('is_active', true)
                ->with(['comboPackItems.menuItem', 'comboPackItems.menuItemVariation'])
                ->orderByDesc('id')
                ->get()
                ->filter(fn($combo) => $combo->isAvailable())
                ->map(function ($combo) {
                    $calculated = $combo->calculateComboItemPrices(null, null);

                    return [
                        'id' => (int) $combo->id,
                        'name' => (string) $combo->getTranslation('name', app()->getLocale()),
                        'regular_price' => (float) $combo->regular_price,
                        'discounted_price' => (float) $combo->discounted_price,
                        'discount_percent' => (float) $combo->discount_percent,
                        'combo_image_url' => (string) ($combo->combo_image_url ?? ''),
                        'items' => collect($calculated)->map(function ($line) {
                            $comboItem = $line['combo_item'];
                            return [
                                'menu_item_id' => (int) $comboItem->menu_item_id,
                                'menu_item_variation_id' => $comboItem->menu_item_variation_id ? (int) $comboItem->menu_item_variation_id : null,
                                'quantity' => (int) $comboItem->quantity,
                                'discounted_unit_price' => (float) ($line['price'] ?? 0),
                                'original_unit_price' => (float) ($line['original_price'] ?? 0),
                                'line_discount_amount' => (float) ($line['combo_discount_amount'] ?? 0),
                                'item_name' => (string) ($comboItem->menuItem?->item_name ?? ''),
                                'variation_name' => (string) ($comboItem->menuItemVariation?->variation ?? ''),
                            ];
                        })->values(),
                    ];
                })->values();
        }

        $payload['combo_packs'] = $comboPacks;

        return view('pos.posvue', [
            'posVueBootstrap' => $payload,
        ]);
    }

    public function show($id)
    {
        abort_if((!in_array('Order', restaurant_modules())), 403);
        $tableOrderID = $id;
        return view('pos.show', compact('tableOrderID'));
    }

    public function order($id)
    {
        abort_if((!in_array('Order', restaurant_modules())), 403);
        $tableOrderID = $id;
        return view('pos.order', compact('tableOrderID'));
    }

    public function kot($id)
    {
        abort_if((!in_array('Order', restaurant_modules())), 403);
        $orderID = $id;
        $order = Order::find($orderID);

        $showOrderDetail = request()->get('show-order-detail') == 'true' ? true : false;
        return view('pos.kot', compact('orderID', 'showOrderDetail'));
    }

    public function customerDisplay()
    {
        abort_if((!in_array('Customer Display', restaurant_modules())), 403);
        return view('pos.customer-display');
    }

    public function customerOrderBoard()
    {
        abort_if((!in_array('Customer Display', restaurant_modules())), 403);
        return view('pos.customer-order-board');
    }

}
