<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\ItemModifier;
use App\Models\ModifierGroup;
use App\Models\ComboPack;
use App\Models\Order;
use App\Models\Table;
use App\Services\PosBatchSyncService;
use App\Services\PosBootstrapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PosController extends Controller
{

    public function index(PosBootstrapService $bootstrapService, Request $request)
    {
        abort_if((!in_array('Order', restaurant_modules()) || !user_can('Create Order')), 403);

        return $this->renderVuePos(
            $bootstrapService,
            $this->buildInitialTableBootstrapPayload((int) $request->query('table_id', 0))
        );
    }

    public function vue(PosBootstrapService $bootstrapService, Request $request)
    {
        abort_if((!in_array('Order', restaurant_modules()) || !user_can('Create Order')), 403);

        return $this->renderVuePos(
            $bootstrapService,
            $this->buildInitialTableBootstrapPayload((int) $request->query('table_id', 0))
        );
    }

    public function bootstrap(PosBootstrapService $bootstrapService): JsonResponse
    {
        abort_if((!in_array('Order', restaurant_modules()) || !user_can('Create Order')), 403);

        return response()->json($this->buildPosVueBootstrapPayload($bootstrapService));
    }

    public function clientOps(Request $request, PosBatchSyncService $batchSyncService): JsonResponse
    {
        abort_if((!in_array('Order', restaurant_modules()) || !user_can('Create Order')), 403);

        $request->validate([
            'state' => ['nullable', 'array'],
            'operations' => ['required', 'array'],
            'operations.*' => ['required', 'array'],
        ]);

        $state = $request->input('state', []);
        $operations = $request->input('operations', []);

        return response()->json($batchSyncService->apply(
            is_array($state) ? $state : [],
            is_array($operations) ? $operations : []
        ));
    }

    private function buildPosVueBootstrapPayload(PosBootstrapService $bootstrapService): array
    {
        $bootstrap = $bootstrapService->resolve();
        $data = $bootstrap['data'] ?? [];
        $branch = branch();

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
                'prices:id,menu_item_id,order_type_id,delivery_app_id,menu_item_variation_id,final_price,status',
                'variations' => function ($query) {
                    $query->select('id', 'menu_item_id', 'variation', 'price')
                        ->with([
                            'prices:id,menu_item_id,menu_item_variation_id,order_type_id,delivery_app_id,final_price,status',
                        ]);
                },
            ])
            ->withCount(['variations', 'modifierGroups'])
            ->orderBy('id')
            ->get();

        $itemIds = $menuItems->pluck('id')->all();

        // Pull per-item rules (is_required / allow_multiple_selection) from item_modifiers
        // so the Vue ItemModifiersModal can mirror legacy ItemModifiers.php validation.
        $itemModifierRows = ItemModifier::query()
            ->whereIn('menu_item_id', $itemIds)
            ->select('menu_item_id', 'menu_item_variation_id', 'modifier_group_id', 'is_required', 'allow_multiple_selection')
            ->get();

        $modifierGroupIds = $itemModifierRows->pluck('modifier_group_id')->unique()->values()->all();

        $modifierGroupsById = ModifierGroup::query()
            ->with(['options:id,modifier_group_id,name,price,is_available,is_preselected,sort_order'])
            ->whereIn('id', $modifierGroupIds)
            ->get()
            ->mapWithKeys(function ($group) {
                return [
                    $group->id => [
                        'id' => (int) $group->id,
                        'name' => (string) $group->name,
                        'options' => $group->options
                            ->sortBy([['sort_order', 'asc'], ['id', 'asc']])
                            ->values()
                            ->map(function ($opt) {
                                return [
                                    'id' => (int) $opt->id,
                                    'name' => (string) $opt->name,
                                    'price' => (float) ($opt->price ?? 0),
                                    'is_available' => (bool) ($opt->is_available ?? true),
                                    'is_preselected' => (bool) ($opt->is_preselected ?? false),
                                    'sort_order' => (int) ($opt->sort_order ?? 0),
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

            // Per-assignment rules live on item_modifiers, not modifier_groups
            // (legacy: ItemModifiers->itemModifiers->first()->is_required etc.).
            $groupForItem = $modifierGroupsById[$groupId];
            $groupForItem['is_required'] = (bool) ($row->is_required ?? false);
            $groupForItem['allow_multiple_selection'] = (bool) ($row->allow_multiple_selection ?? false);

            if ($row->menu_item_variation_id) {
                $variationId = (int) $row->menu_item_variation_id;
                $variationGroupsByItem[$itemId] ??= [];
                $variationGroupsByItem[$itemId][$variationId] ??= [];
                $variationGroupsByItem[$itemId][$variationId][] = $groupForItem;
                continue;
            }

            $baseGroupsByItem[$itemId] ??= [];
            $baseGroupsByItem[$itemId][] = $groupForItem;
        }

        $menuItems = $menuItems->map(function ($item) use ($baseGroupsByItem, $variationGroupsByItem) {
            $itemId = (int) $item->id;
                return [
                    'id' => (int) $item->id,
                    'menu_id' => (int) ($item->menu_id ?? 0),
                    'item_category_id' => (int) ($item->item_category_id ?? 0),
                    'item_name' => (string) $item->item_name,
                    'item_code' => $item->item_code !== null && $item->item_code !== ''
                        ? (string) $item->item_code
                        : null,
                    'type' => (string) ($item->type ?? 'veg'),
                    'price' => (float) ($item->price ?? 0),
                    'item_photo_url' => (string) ($item->item_photo_url ?? ''),
                    'in_stock' => (bool) ($item->in_stock ?? true),
                    'variations_count' => (int) ($item->variations_count ?? 0),
                    'modifier_groups_count' => (int) ($item->modifier_groups_count ?? 0),
                    'variations' => $item->variations->map(function ($variation) {
                        return [
                            'id' => (int) $variation->id,
                            'variation' => (string) ($variation->variation ?? ''),
                            'price' => (float) ($variation->price ?? 0),
                            'pricing_rows' => $variation->prices
                                ->map(function ($priceRow) {
                                    return [
                                        'order_type_id' => $priceRow->order_type_id ? (int) $priceRow->order_type_id : null,
                                        'delivery_app_id' => $priceRow->delivery_app_id ? (int) $priceRow->delivery_app_id : null,
                                        'menu_item_variation_id' => $priceRow->menu_item_variation_id ? (int) $priceRow->menu_item_variation_id : null,
                                        'final_price' => (float) ($priceRow->final_price ?? 0),
                                    ];
                                })
                                ->values()
                                ->all(),
                        ];
                    })->values()->all(),
                    'pricing_rows' => $item->prices
                        ->map(function ($priceRow) {
                            return [
                                'order_type_id' => $priceRow->order_type_id ? (int) $priceRow->order_type_id : null,
                                'delivery_app_id' => $priceRow->delivery_app_id ? (int) $priceRow->delivery_app_id : null,
                                'menu_item_variation_id' => $priceRow->menu_item_variation_id ? (int) $priceRow->menu_item_variation_id : null,
                                'final_price' => (float) ($priceRow->final_price ?? 0),
                            ];
                        })
                        ->values()
                        ->all(),
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
            'current_user' => [
                'id' => (int) (auth()->id() ?? 0),
                'name' => (string) (auth()->user()?->name ?? ''),
                'is_waiter' => (bool) auth()->user()?->hasRole('waiter_' . (restaurant()?->id ?? 0)),
                'can_update_order' => (bool) user_can('Update Order'),
            ],
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
            'branch' => $branch ? [
                'id' => (int) $branch->id,
                'name' => (string) ($branch->name ?? ''),
                'lat' => $branch->lat !== null ? (float) $branch->lat : null,
                'lng' => $branch->lng !== null ? (float) $branch->lng : null,
            ] : null,
            'delivery_platforms' => collect($data['delivery_platforms'] ?? [])->map(function ($platform) {
                return [
                    'id' => (int) ($platform->id ?? 0),
                    'name' => (string) ($platform->name ?? ''),
                    'commission_type' => (string) ($platform->commission_type ?? 'fixed'),
                    'commission_value' => (float) ($platform->commission_value ?? 0),
                ];
            })->values(),
            'delivery_executives' => collect($data['delivery_executives'] ?? [])->map(function ($executive) {
                return [
                    'id' => (int) ($executive->id ?? 0),
                    'name' => (string) ($executive->name ?? ''),
                    'phone' => (string) ($executive->phone ?? ''),
                    'status' => (string) ($executive->status ?? ''),
                ];
            })->values(),
        ];

        $payload['modules'] = array_values(restaurant_modules() ?? []);
        $payload['hide_menu_item_image_on_pos'] = (bool) (restaurant()->hide_menu_item_image_on_pos ?? false);
        $payload['allow_custom_order_extras'] = (bool) (restaurant()->allow_custom_order_extras ?? false);
        $payload['reward_settings'] = $data['reward_settings'] ?? null;
        $payload['pos_preferences'] = [
            'default_order_type_id' => (int) (auth()->user()?->default_order_type_id ?? restaurant()->default_order_type_id ?? 0),
            'selected_delivery_app' => null,
        ];

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
                        'discount_type' => (string) ($combo->discount_type ?? 'fixed'),
                        'discount_amount' => (float) ($combo->discount_amount ?? 0),
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

        return $payload;
    }

    public function show($id)
    {
        abort_if((!in_array('Order', restaurant_modules())), 403);
        $tableOrderID = $id;
        return view('pos.show', compact('tableOrderID'));
    }

    public function order($id, PosBootstrapService $bootstrapService)
    {
        abort_if((!in_array('Order', restaurant_modules())), 403);

        return $this->renderVuePos($bootstrapService);
    }

    public function kot($id, PosBootstrapService $bootstrapService, Request $request)
    {
        abort_if((!in_array('Order', restaurant_modules())), 403);

        return $this->renderVuePos($bootstrapService, $this->buildInitialOrderBootstrapPayload((int) $id, $request->boolean('show-order-detail')));
    }

    private function buildInitialTableBootstrapPayload(int $tableId): array
    {
        if ($tableId <= 0) {
            return [];
        }

        $branch = branch();
        if (!$branch) {
            return [];
        }

        $table = Table::query()
            ->select('id', 'table_code')
            ->where('id', $tableId)
            ->where('branch_id', $branch->id)
            ->first();

        if (!$table) {
            return [];
        }

        return [
            'initial_table' => [
                'id' => (int) $table->id,
                'table_code' => (string) $table->table_code,
            ],
        ];
    }

    private function renderVuePos(PosBootstrapService $bootstrapService, array $extraBootstrapData = [])
    {
        return view('pos.posvue', [
            'posVueBootstrap' => array_merge(
                $this->buildPosVueBootstrapPayload($bootstrapService),
                $extraBootstrapData,
            ),
        ]);
    }

    private function buildInitialOrderBootstrapPayload(int $orderId, bool $showOrderDetail): array
    {
        if (!$showOrderDetail || $orderId <= 0) {
            return [];
        }

        $branch = branch();
        if (!$branch) {
            return [];
        }

        $order = Order::query()
            ->with([
                'customer:id,name,email,phone,phone_code,delivery_address',
                'items.modifierOptions',
                'items.menuItem',
                'items.menuItemVariation',
                'table:id,table_code',
            ])
            ->where('id', $orderId)
            ->where('branch_id', $branch->id)
            ->first();

        if (!$order) {
            return [];
        }

        $comboInstancesByPack = [];
        $currentComboPackId = null;
        $currentComboInstanceKey = null;

        $lines = $order->items->map(function ($item) use (&$comboInstancesByPack, &$currentComboPackId, &$currentComboInstanceKey) {
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
                ->mapWithKeys(fn ($opt) => [(int) $opt->id => (int) ($opt->pivot->quantity ?? 1)])
                ->all();

            return [
                'order_item_id' => (int) $item->id,
                'menu_item_id' => (int) $item->menu_item_id,
                'item_name' => (string) ($item->menuItem?->item_name ?? ''),
                'menu_item_variation_id' => $item->menu_item_variation_id ? (int) $item->menu_item_variation_id : null,
                'variation_name' => (string) ($item->menuItemVariation?->variation ?? ''),
                'qty' => (int) ($item->quantity ?? 1),
                'unit_price' => (float) ($item->price ?? 0),
                'amount' => (float) ($item->amount ?? 0),
                'note' => (string) ($item->note ?? ''),
                'combo_pack_id' => $item->combo_pack_id ? (int) $item->combo_pack_id : null,
                'combo_instance_key' => $comboInstanceKey,
                'modifier_option_quantities' => $modifierQtyMap,
            ];
        })->values();

        return [
            'initial_order' => [
                'id' => (int) $order->id,
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
                'note' => (string) ($order->note ?? ''),
                'sub_total' => (float) ($order->sub_total ?? 0),
                'total' => (float) ($order->total ?? 0),
                'reward_point_discount' => (float) ($order->reward_point_discount ?? 0),
                'reward_points_redeemed' => (int) ($order->reward_points_redeemed ?? 0),
                'order_number' => (string) ($order->order_number ?? ''),
                'formatted_order_number' => (string) ($order->show_formatted_order_number ?? ''),
                'table_id' => $order->table_id ? (int) $order->table_id : null,
                'table_code' => $order->table?->table_code ? (string) $order->table->table_code : null,
                'lines' => $lines,
            ],
            'initial_order_id' => (int) $order->id,
            'initial_show_order_detail' => true,
        ];
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
