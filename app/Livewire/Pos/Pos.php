<?php

namespace App\Livewire\Pos;

use App\Events\NewOrderCreated;
use App\Livewire\Customer\AddCustomer;
use App\Models\Customer;
use App\Models\DeliveryExecutive;
use App\Models\DeliveryPlatform;
use App\Models\ItemCategory;
use App\Models\Kot;
use App\Models\KotCancelReason;
use App\Models\KotItem;
use App\Models\KotPlace;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\MenuItemVariation;
use App\Models\ModifierOption;
use App\Models\Order;
use App\Models\OrderCharge;
use App\Models\OrderItem;
use App\Models\OrderTax;
use App\Models\OrderType;
use App\Models\Printer;
use App\Models\RestaurantCharge;
use App\Models\Table;
use App\Models\Tax;
use App\Models\User;
use App\Scopes\BranchScope;
use App\Services\Pos\BillSecondaryActionResolver;
use App\Services\PosBatchSyncService;
use App\Services\PosBootstrapService;
use App\Services\RewardPointsService;
use App\Support\KotAdjustmentLogger;
use App\Traits\PrinterSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Attributes\On;
use Livewire\Component;

class Pos extends Component
{
    use LivewireAlert, PrinterSetting;

    protected $listeners = ['refreshPos' => '$refresh', 'customerSelected' => 'setCustomer', 'setOrderTypeChoice'];

    public $categoryList;

    public $search;

    public $filterCategories;

    public $menuItem;

    public $subTotal;

    public $total;

    public $orderNumber;

    public $kotNumber;

    public $tableNo;

    public $tableId;

    public $users;

    public $noOfPax = 1;

    public $selectWaiter;

    public $taxes;

    public $orderNote;

    public $tableOrder;

    public $tableOrderID;

    public $orderType;

    public $orderTypeSlug;

    public $kotList = [];

    public $showVariationModal = false;

    public $showKotNote = false;

    public $showTableModal = false;

    public $showErrorModal = true;

    public $showNewKotButton = false;

    public $orderDetail = null;

    public $showReservationModal = false;

    public $reservationId = null;

    public $reservationCustomer = null;

    public $reservation = null;

    public $isSameCustomer = false;

    public $intendedOrderAction = null;

    public $orderItemList = [];

    public $orderItemVariation = [];

    public $orderItemQty = [];

    public $orderItemAmount = [];

    public $deliveryExecutives;

    public $selectDeliveryExecutive;

    public $orderID;

    public $discountType;

    public $discountValue;

    public $discountAmount;

    public $restaurantSetting;

    public $showDiscountModal = false;

    public $selectedModifierItem;

    public $modifiers;

    public $showModifiersModal = false;

    public $itemModifiersSelected = [];

    public $orderItemModifiersPrice = [];

    public $orderItemComboPack = [];

    public $orderItemOriginalPrice = [];

    public $orderItemComboDiscount = [];

    public $orderItemComboName = [];

    public $orderItemUnitPrice = [];

    public $orderItemDisplayPrice = [];

    public $extraCharges;

    public $orderExtras = [];

    public $discountedTotal;

    public $tipAmount = 0;

    public $orderStatus;

    public $deliveryFee = 0;

    public $itemNotes = [];

    public $orderPlaces;

    public $cancelReasons;

    public $confirmDeleteModal = false;

    public $deleteOrderModal = false;

    public $cancelReason;

    public $cancelReasonText;

    public $orderTypeId;

    public $selectedDeliveryApp = null;

    public $deliveryDateTime;

    public $customerDisplayStatus = 'idle';

    public $totalTaxAmount = 0;

    public $orderItemTaxDetails = [];

    /**
     * When a KOT-backed line is rehydrated from a persisted order_item row, keep item-level tax
     * fields aligned with what was saved (calculateTotal() re-runs updateOrderItemTaxDetails()).
     *
     * @var array<string, array{tax_amount: float, tax_percentage: float|null, tax_breakup: mixed}>
     */
    public $orderItemPersistedTaxOverride = [];

    public $taxMode;

    public $pickupRange;

    public $showRemovalReasonModal = false;

    public $removalReason = '';

    public $pendingRemovalItem = null;

    public $pendingRemovalAction = null;

    public $pendingRemovalNewQuantity = null;

    public $pendingRemovalComboItems = [];

    public $now;

    public $minDate;

    public $maxDate;

    public $defaultDate;

    public $formattedOrderNumber;

    public $customerId;

    public $customer;

    public $menuList;

    public $menuId;

    // Lightweight order type selector (dropdown) + persistence
    public $showOrderTypeDropdown = false;

    public $availableOrderTypes = [];

    public $availableDeliveryPlatforms = [];

    public $setAsDefaultOrderType = false;

    public $userDefaultOrderTypeId = null;

    public $orderTypeName = null;

    public $selectedDeliveryPlatformName = null;

    // Optimistic qty update properties
    public $pendingQtySyncs = [];  // Track items awaiting debounced sync

    public $qtyDebounceTimer = null;  // Handle for debounce timer

    public $isQtyOptimisticMode = true;  // Enable optimistic qty updates

    // Reward Points Redemption
    public $rewardPointDiscount = 0;
    public $rewardPointsRedeemed = 0;
    public $rewardPointsAvailable = 0;
    public $rewardSettings = null;
    public $rewardDisplayName = 'Reward';

    public function setCustomer($customerId = null)
    {
        $this->customerId = $customerId;
        $this->customer = Customer::find($customerId);
        $this->loadCustomerRewardBalance();
    }

    /**
     * Load the customer's reward balance and settings for display in POS
     */
    protected function loadCustomerRewardBalance(): void
    {
        $this->rewardPointsAvailable = 0;
        $this->rewardSettings = null;
        $this->rewardDisplayName = 'Reward';

        if (!$this->customerId) {
            return;
        }

        try {
            $settings = \App\Models\RewardSetting::getForRestaurant(restaurant()->id);
            if (!$settings->enable_reward_point) {
                return;
            }

            $this->rewardSettings = $settings;
            $this->rewardDisplayName = $settings->reward_point_display_name ?: 'Reward';

            $balance = \App\Models\RewardBalance::getForCustomer($this->customerId, restaurant()->id);
            $this->rewardPointsAvailable = $balance->available_points;
        } catch (\Exception $e) {
            \Log::warning('Failed to load reward balance for POS', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Apply reward points redemption to the current order
     */
    public function applyRewardRedemption(int $points): void
    {
        if (!user_can('Redeem Reward Points')) {
            $this->alert('error', __('messages.noPermission'), ['toast' => true, 'position' => 'top-end']);
            return;
        }

        if (!$this->customerId || !$this->rewardSettings || !$this->rewardSettings->enable_reward_point) {
            $this->alert('error', 'Reward points are not available.', ['toast' => true, 'position' => 'top-end']);
            return;
        }

        if ($points <= 0) {
            $this->alert('error', 'Please enter valid points to redeem.', ['toast' => true, 'position' => 'top-end']);
            return;
        }

        // Validate minimum redeem points
        if ($this->rewardSettings->minimum_redeem_point && $points < $this->rewardSettings->minimum_redeem_point) {
            $this->alert('error', "Minimum {$this->rewardSettings->minimum_redeem_point} points required to redeem.", ['toast' => true, 'position' => 'top-end']);
            return;
        }

        // Validate max per order
        if ($this->rewardSettings->maximum_redeem_point_per_order && $points > $this->rewardSettings->maximum_redeem_point_per_order) {
            $points = $this->rewardSettings->maximum_redeem_point_per_order;
        }

        // Validate available balance
        if ($points > $this->rewardPointsAvailable) {
            $this->alert('error', "Insufficient points. Available: {$this->rewardPointsAvailable}", ['toast' => true, 'position' => 'top-end']);
            return;
        }

        // Validate minimum order total for redemption
        if ($this->total < $this->rewardSettings->minimum_order_total_to_redeem) {
            $this->alert('error', "Minimum order total of {$this->rewardSettings->minimum_order_total_to_redeem} required to redeem points.", ['toast' => true, 'position' => 'top-end']);
            return;
        }

        // Calculate discount amount
        $discountAmount = $points * $this->rewardSettings->redeem_amount_per_unit_point;

        // Can't redeem more than order value (after other discounts)
        $maxDiscount = $this->total;
        if ($discountAmount > $maxDiscount) {
            $discountAmount = $maxDiscount;
            $points = (int) floor($discountAmount / $this->rewardSettings->redeem_amount_per_unit_point);
        }

        $this->rewardPointDiscount = round($discountAmount, 2);
        $this->rewardPointsRedeemed = $points;
        $this->calculateTotal();

        $this->alert('success', "Applied {$points} {$this->rewardDisplayName} points (" . number_format($this->rewardPointDiscount, 2) . ' discount)', [
            'toast' => true,
            'position' => 'top-end',
            'timer' => 3000,
        ]);
    }

    /**
     * Remove reward points redemption from the current order
     */
    public function removeRewardRedemption(): void
    {
        $this->rewardPointDiscount = 0;
        $this->rewardPointsRedeemed = 0;
        $this->calculateTotal();

        $this->alert('info', 'Reward points discount removed.', [
            'toast' => true,
            'position' => 'top-end',
            'timer' => 2000,
        ]);
    }

    /**
     * Get the maximum redeemable points for the current order context
     */
    public function getMaxRedeemablePointsProperty(): int
    {
        if (!$this->rewardSettings || !$this->rewardSettings->enable_reward_point || !$this->customerId) {
            return 0;
        }

        if ($this->total < $this->rewardSettings->minimum_order_total_to_redeem) {
            return 0;
        }

        $available = $this->rewardPointsAvailable;

        // Apply max per order limit
        if ($this->rewardSettings->maximum_redeem_point_per_order) {
            $available = min($available, $this->rewardSettings->maximum_redeem_point_per_order);
        }

        // Can't redeem more than order total
        $maxPointsByOrderTotal = (int) floor($this->total / $this->rewardSettings->redeem_amount_per_unit_point);
        return min($available, $maxPointsByOrderTotal);
    }

    protected function applyBootstrapContext(array $bootstrap): void
    {
        $this->categoryList = $bootstrap['categories'] ?? ItemCategory::all();
        $this->availableOrderTypes = isset($bootstrap['order_types']) ? $bootstrap['order_types']->toArray() : [];
        $this->availableDeliveryPlatforms = isset($bootstrap['delivery_platforms']) ? $bootstrap['delivery_platforms']->toArray() : [];
        $this->users = $bootstrap['waiters'] ?? collect();
        $this->taxes = $bootstrap['taxes'] ?? Tax::all();
        $this->deliveryExecutives = $bootstrap['delivery_executives'] ?? collect();
        $this->pickupRange = (int) ($bootstrap['pickup_days_range'] ?? $this->pickupRange ?? 1);
        $this->taxMode = $bootstrap['tax_mode'] ?? $this->taxMode;
        $this->selectWaiter = user()->id;
        $this->maxDate = now()->addDays($this->pickupRange - 1)->endOfDay()->format('Y-m-d\TH:i');
    }

    protected function loadBootstrapContext(): void
    {
        $startedAt = microtime(true);

        try {
            $resolved = app(PosBootstrapService::class)->resolve();
            $this->applyBootstrapContext($resolved['data']);

            Log::info('POS bootstrap loaded', [
                'source' => $resolved['cached'] ? 'cache' : 'compute',
                'restaurant_id' => restaurant()->id ?? null,
                'branch_id' => branch()->id ?? null,
                'ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ]);

            return;
        } catch (\Throwable $e) {
            Log::warning('POS bootstrap failed, falling back to legacy queries', [
                'restaurant_id' => restaurant()->id ?? null,
                'branch_id' => branch()->id ?? null,
                'ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'error' => $e->getMessage(),
            ]);
        }

        $this->loadLegacyBootstrapContext();
    }

    protected function loadLegacyBootstrapContext(): void
    {
        $this->categoryList = ItemCategory::all();

        $this->availableOrderTypes = OrderType::where('is_active', true)
            ->orderBy('order_type_name')
            ->get(['id', 'order_type_name', 'slug', 'type'])
            ->toArray();

        $this->availableDeliveryPlatforms = DeliveryPlatform::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->toArray();

        $this->users = User::withoutGlobalScope(BranchScope::class)
            ->where(function ($q) {
                return $q->where('branch_id', branch()->id)
                    ->orWhereNull('branch_id');
            })
            ->role('waiter_'.restaurant()->id)
            ->where('restaurant_id', restaurant()->id)
            ->get();

        $this->taxMode = restaurant()->tax_mode;
        $this->taxes = Tax::all();
        $this->selectWaiter = user()->id;
        $this->deliveryExecutives = DeliveryExecutive::where('status', 'available')->get();
    }

    public function mount()
    {

        $this->total = 0;
        $this->subTotal = 0;
        $this->pickupRange = restaurant()->pickup_days_range ?? 1;
        $this->loadBootstrapContext();
        // Set minimum date to next minute to avoid past times
        $this->minDate = now()->addMinute()->format('Y-m-d\TH:i');
        $this->maxDate = now()->addDays($this->pickupRange - 1)->endOfDay()->format('Y-m-d\TH:i');
        $this->defaultDate = old('deliveryDateTime', $this->deliveryDateTime ?? $this->minDate);

        // Restore last selection from session (so navigating away/back doesn't reset)
        if (! $this->orderID && ! $this->tableOrderID) {
            $sessionOrderTypeId = session()->get('pos.order_type_id');
            $sessionDeliveryAppId = session()->get('pos.delivery_app_id');

            if ($sessionOrderTypeId) {
                $sessionOrderType = OrderType::find($sessionOrderTypeId);
                if ($sessionOrderType && $sessionOrderType->is_active) {
                    $this->orderTypeId = $sessionOrderType->id;
                    $this->orderType = $sessionOrderType->type;
                    $this->orderTypeSlug = $sessionOrderType->slug;
                    $this->orderTypeName = $sessionOrderType->order_type_name;

                    if ($this->orderTypeSlug === 'delivery') {
                        $this->selectedDeliveryApp = $sessionDeliveryAppId;
                    }
                }
            }

            // If session has no selection, fall back to user's default order type
            if (! $this->orderTypeId) {
                $user = auth()->user();
                if ($user && $user->default_order_type_id) {
                    $defaultOrderType = OrderType::find($user->default_order_type_id);
                    if ($defaultOrderType && $defaultOrderType->is_active) {
                        $this->orderTypeId = $defaultOrderType->id;
                        $this->orderType = $defaultOrderType->type;
                        $this->orderTypeSlug = $defaultOrderType->slug;
                        $this->orderTypeName = $defaultOrderType->order_type_name;
                    }
                }
            }

            // Final fallback: ensure an order type is always selected (default to Dine In)
            if (! $this->orderTypeId) {
                $dineIn = OrderType::where('type', 'dine_in')->where('is_active', true)->first();
                if ($dineIn) {
                    $this->orderTypeId = $dineIn->id;
                    $this->orderType = $dineIn->type;
                    $this->orderTypeSlug = $dineIn->slug;
                    $this->orderTypeName = $dineIn->order_type_name;
                } else {
                    // Extremely defensive fallback
                    $this->orderType = 'dine_in';
                    $this->orderTypeSlug = 'dine_in';
                }
            }
        }

        $this->userDefaultOrderTypeId = auth()->user()?->default_order_type_id;
        $this->setAsDefaultOrderType = (bool) ($this->orderTypeId && $this->userDefaultOrderTypeId && ((int) $this->orderTypeId === (int) $this->userDefaultOrderTypeId));
        $this->showOrderTypeDropdown = ! $this->orderTypeId;

        if ($this->tableOrderID) {
            $this->tableId = $this->tableOrderID;
            $this->tableOrder = Table::find($this->tableOrderID);
            $this->tableNo = $this->tableOrder->table_code;
            $this->orderID = $this->tableOrder->activeOrder ? $this->tableOrder->activeOrder->id : null;

            if ($this->tableOrder->activeOrder) {

                $this->orderNumber = $this->tableOrder->activeOrder->order_number;
                $this->formattedOrderNumber = $this->tableOrder->activeOrder->formatted_order_number;
                $this->tipAmount = $this->tableOrder->activeOrder->tip_amount;
                $this->deliveryFee = $this->tableOrder->activeOrder->delivery_fee;
                $this->showTableOrder();

                if ($this->orderDetail) {
                    // Pass runSetup=false: setupOrderItems() will be called after
                    // updatedOrderTypeId() so item amounts are set last and win.
                    $this->showOrderDetail(runSetup: false);
                }
            } elseif ($this->orderDetail) {
                return $this->redirect(route('pos.index'), navigate: true);
            } else {
                $this->setTable($this->tableOrder);
            }
        }

        if ($this->orderID) {
            $order = Order::find($this->orderID);

            if (! $order || $order->status === 'canceled') {
                return $this->redirect(route('pos.index'), navigate: true);
            }

            $this->orderNumber = $order->order_number;
            $this->formattedOrderNumber = $order->formatted_order_number;
            $this->noOfPax = $order->number_of_pax;
            $this->selectWaiter = $order->waiter_id ?? null;
            $this->tableNo = $order->table->table_code ?? null;
            $this->tableId = $order->table->id ?? null;
            $this->discountAmount = $order->discount_amount;
            $this->discountValue = $order->discount_type === 'percent' ? rtrim(rtrim($order->discount_value, '0'), '.') : $order->discount_value;
            $this->discountType = $order->discount_type;
            $this->tipAmount = $order->tip_amount;
            $this->deliveryFee = $order->delivery_fee;
            $this->orderStatus = $order->order_status;
            $this->orderTypeId = $order->order_type_id;
            $this->orderType = $order->order_type;

            // Restore reward points state from existing order
            $this->rewardPointDiscount = (float) ($order->reward_point_discount ?? 0);
            $this->rewardPointsRedeemed = (int) ($order->reward_points_redeemed ?? 0);
            if ($order->customer_id) {
                $this->customerId = $order->customer_id;
                $this->customer = Customer::find($order->customer_id);
                $this->loadCustomerRewardBalance();
            }

            // Ensure existing orders always resolve to a concrete order type ID
            if (! $this->orderTypeId && $this->orderType) {
                $resolved = OrderType::where('type', $this->orderType)
                    ->orWhere('slug', $this->orderType)
                    ->where('is_active', true)
                    ->first();

                if ($resolved) {
                    $this->orderTypeId = $resolved->id;
                    $this->orderTypeSlug = $resolved->slug;
                    $this->orderType = $resolved->type;
                    $this->orderTypeName = $resolved->order_type_name;
                }
            }

            if ($this->orderTypeId) {
                $this->orderTypeName = $this->orderTypeName ?? OrderType::where('id', $this->orderTypeId)->value('order_type_name');
            }
            $this->deliveryDateTime = $order->pickup_date;
            $this->taxMode = $order->tax_mode ?? $this->taxMode;
            $this->selectedDeliveryApp = $order->delivery_app_id;

            $this->userDefaultOrderTypeId = auth()->user()?->default_order_type_id;
            $this->setAsDefaultOrderType = (bool) ($this->orderTypeId && $this->userDefaultOrderTypeId && ((int) $this->orderTypeId === (int) $this->userDefaultOrderTypeId));

            $this->orderExtras = $order->extras()
                ->orderBy('id')
                ->get(['note', 'amount'])
                ->map(fn ($extra) => [
                    'note' => $extra->note,
                    'amount' => (float) $extra->amount,
                ])
                ->toArray();
            $this->selectDeliveryExecutive = $order->delivery_executive_id;

            // kot.blade passes orderDetail="{{ $showOrderDetail }}" (URL flag). Only replace with the real Order
            // when that flag is set — otherwise keep cart empty for "New KOT" (pos shows kot_items, not order_detail).
            if ($this->orderDetail) {
                $this->orderDetail = $order;
            }
        }

        // Call updatedOrderTypeId BEFORE setupOrderItems so that the charge + order-type
        // recalculation runs on an empty cart (no item amounts overwritten).
        // setupOrderItems() then populates the cart with persisted amounts and runs as
        // the authoritative last step.
        $this->updatedOrderTypeId($this->orderTypeId);

        // Now run setupOrderItems() as the LAST authoritative step so persisted amounts
        // are not overwritten by updatedOrderTypeId()'s recalculation.
        if (($this->orderID || $this->tableOrderID) && $this->orderDetail instanceof \App\Models\Order) {
            $this->setupOrderItems();
        }
        $this->refreshSelectedDeliveryPlatformName();

        if ($this->orderID) {
            $this->extraCharges = ($order->status === 'kot' && ! $this->orderDetail) ? [] : $order->extraCharges;
        }

        $this->cancelReasons = KotCancelReason::where('cancel_order', true)->get();
        $this->menuList = Menu::withoutGlobalScopes()->where('branch_id', branch()->id)->orderBy('sort_order')->get();
    }

    public function toggleOrderTypeDropdown(): void
    {
        $this->showOrderTypeDropdown = ! $this->showOrderTypeDropdown;
    }

    public function updatedSetAsDefaultOrderType($value): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }

        $value = (bool) $value;

        if ($value && $this->orderTypeId) {
            $user->update(['default_order_type_id' => (int) $this->orderTypeId]);
            $this->userDefaultOrderTypeId = (int) $this->orderTypeId;

            return;
        }

        if (! $value && $user->default_order_type_id && $this->orderTypeId && ((int) $user->default_order_type_id === (int) $this->orderTypeId)) {
            $user->update(['default_order_type_id' => null]);
            $this->userDefaultOrderTypeId = null;
        }
    }

    public function updatedSelectedDeliveryApp(): void
    {
        // Only relevant for delivery orders
        if (($this->orderTypeSlug ?? null) !== 'delivery') {
            $this->selectedDeliveryApp = null;
            $this->selectedDeliveryPlatformName = null;

            return;
        }

        // Persist last chosen platform for this session
        session()->put('pos.delivery_app_id', $this->selectedDeliveryApp);

        $this->refreshSelectedDeliveryPlatformName();
        $this->updateCartItemsPricing();
        $this->calculateTotal();
    }

    private function refreshSelectedDeliveryPlatformName(): void
    {
        if (($this->orderTypeSlug ?? null) !== 'delivery') {
            $this->selectedDeliveryPlatformName = null;

            return;
        }

        if ($this->selectedDeliveryApp === 'default' || $this->selectedDeliveryApp === null || $this->selectedDeliveryApp === '') {
            $this->selectedDeliveryPlatformName = __('modules.order.default');

            return;
        }

        $platformId = is_numeric($this->selectedDeliveryApp) ? (int) $this->selectedDeliveryApp : null;
        $this->selectedDeliveryPlatformName = $platformId
            ? (DeliveryPlatform::where('id', $platformId)->value('name') ?? null)
            : null;
    }

    public function setOrderTypeChoice($value)
    {
        try {
            // Handle if $value is an array containing orderType and orderTypeId
            if (is_array($value) && isset($value['orderTypeId'])) {
                $this->orderTypeId = $value['orderTypeId'];

                // Store delivery platform if provided
                $this->selectedDeliveryApp = $value['deliveryPlatform'] ?? null;

                // Get the order type object
                $orderType = OrderType::find($this->orderTypeId);

                if ($orderType) {
                    $this->orderType = $orderType->type;
                    $this->orderTypeSlug = $orderType->slug;

                    // If this is a delivery order, handle delivery-specific settings
                    if ($this->orderTypeSlug === 'delivery') {
                        // You can set default delivery fee here if needed
                        // $this->deliveryFee = $this->getDefaultDeliveryFee();
                    } else {
                        $this->deliveryFee = 0;
                    }

                    // Get relevant extra charges for this order type
                    $this->extraCharges = RestaurantCharge::whereJsonContains('order_types', $this->orderTypeSlug)
                        ->where('is_enabled', true)
                        ->get();

                    // Update prices for existing cart items when delivery platform changes
                    $this->updateCartItemsPricing();

                    // Calculate total with new order type settings
                    $this->calculateTotal();

                    // Display success notification for better UX
                    $platformName = $this->selectedDeliveryApp && $this->selectedDeliveryApp !== 'default'
                        ? DeliveryPlatform::find($this->selectedDeliveryApp)?->name ?? ''
                        : '';

                    $message = $platformName
                        ? __('modules.order.orderTypeSetTo', ['type' => $orderType->order_type_name]).' - '.$platformName
                        : __('modules.order.orderTypeSetTo', ['type' => $orderType->order_type_name]);

                    $this->alert('success', $message, [
                        'toast' => true,
                        'position' => 'top-end',
                        'timer' => 2000,
                        'showCancelButton' => false,
                    ]);
                }
            } else {
                // Legacy handling for direct ID passing
                $this->orderTypeId = $value;

                $this->selectedDeliveryApp = null;

                $orderType = OrderType::find($this->orderTypeId);

                if ($orderType) {
                    $this->orderType = $orderType->type;
                    $this->orderTypeSlug = $orderType->slug;

                    // Update prices for existing cart items
                    $this->updateCartItemsPricing();
                }
            }
        } catch (\Exception $e) {
            Log::error('Error setting order type: '.$e->getMessage());
            $this->alert('error', 'Error setting order type: '.$e->getMessage(), [
                'toast' => true,
                'position' => 'top-end',
            ]);
        }
    }

    /**
     * Normalize delivery app ID to ensure it's either an integer or null
     * Converts 'default' string to null
     */
    private function normalizeDeliveryAppId()
    {
        if ($this->selectedDeliveryApp === 'default' || $this->selectedDeliveryApp === null) {
            return null;
        }

        return is_numeric($this->selectedDeliveryApp) ? (int) $this->selectedDeliveryApp : null;
    }

    /**
     * Normalize modifier selections into: [modifier_option_id => quantity].
     *
     * Supports:
     * - [1, 5, 9] (legacy) => [1=>1, 5=>1, 9=>1]
     * - [1 => 2, 5 => 1] (new) => [1=>2, 5=>1]
     */
    private function normalizeModifierQuantities(array $modifierOptionIdsOrQuantities): array
    {
        if (empty($modifierOptionIdsOrQuantities)) {
            return [];
        }

        $isList = array_is_list($modifierOptionIdsOrQuantities);
        $normalized = [];

        if ($isList) {
            foreach ($modifierOptionIdsOrQuantities as $modifierOptionId) {
                $modifierOptionId = (int) $modifierOptionId;
                if ($modifierOptionId > 0) {
                    $normalized[$modifierOptionId] = 1;
                }
            }
        } else {
            foreach ($modifierOptionIdsOrQuantities as $modifierOptionId => $qty) {
                $modifierOptionId = (int) $modifierOptionId;
                $qty = (int) $qty;
                if ($modifierOptionId > 0 && $qty > 0) {
                    $normalized[$modifierOptionId] = $qty;
                }
            }
        }

        ksort($normalized);

        return $normalized;
    }

    private function buildModifierSyncData(array $modifierOptionIdsOrQuantities): array
    {
        $qtyMap = $this->normalizeModifierQuantities($modifierOptionIdsOrQuantities);
        $sync = [];
        foreach ($qtyMap as $modifierOptionId => $qty) {
            $sync[$modifierOptionId] = ['quantity' => $qty];
        }

        return $sync;
    }

    private function getSelectedModifierOptionIds(): array
    {
        $ids = [];
        foreach (($this->itemModifiersSelected ?? []) as $selected) {
            if (! is_array($selected)) {
                continue;
            }
            $ids = array_merge($ids, array_keys($this->normalizeModifierQuantities($selected)));
        }
        $ids = array_values(array_unique($ids));
        sort($ids);

        return $ids;
    }

    private function calculateModifierTotal(array $modifierOptionQtyMap, $modifierOptionsById): float
    {
        $modifierOptionQtyMap = $this->normalizeModifierQuantities($modifierOptionQtyMap);
        $total = 0.0;

        foreach ($modifierOptionQtyMap as $modifierOptionId => $qty) {
            $price = $modifierOptionsById[$modifierOptionId]->price ?? 0;
            $total += ((float) $price * (int) $qty);
        }

        return $total;
    }

    /**
     * Get the normalized delivery app ID for use in views
     */
    public function getNormalizedDeliveryAppIdProperty()
    {
        return $this->normalizeDeliveryAppId();
    }

    /**
     * Update pricing for all items in cart when order type or delivery platform changes
     */
    public function updateCartItemsPricing()
    {
        $this->orderItemPersistedTaxOverride = [];
        // Update prices for all items in cart when order type or delivery platform changes
        foreach ($this->orderItemList as $key => $item) {
            if ($this->orderTypeId) {
                // Set price context on menu item and variation
                $item->setPriceContext($this->orderTypeId, $this->normalizeDeliveryAppId());
                if (isset($this->orderItemVariation[$key])) {
                    $this->orderItemVariation[$key]->setPriceContext($this->orderTypeId, $this->normalizeDeliveryAppId());
                }

                // Update modifier prices
                if (! empty($this->itemModifiersSelected[$key])) {
                    $modifierOptions = $this->getModifierOptionsProperty();
                    $selected = $this->normalizeModifierQuantities($this->itemModifiersSelected[$key]);
                    foreach (array_keys($selected) as $modifierId) {
                        if (isset($modifierOptions[$modifierId])) {
                            $modifierOptions[$modifierId]->setPriceContext($this->orderTypeId, $this->normalizeDeliveryAppId());
                        }
                    }
                    $this->orderItemModifiersPrice[$key] = $this->calculateModifierTotal($selected, $modifierOptions);
                }

                // Recalculate item amount with updated prices
                $basePrice = isset($this->orderItemVariation[$key]) ? $this->orderItemVariation[$key]->price : $item->price;
                $this->orderItemAmount[$key] = $this->orderItemQty[$key] * ($basePrice + ($this->orderItemModifiersPrice[$key] ?? 0));
            }
        }
    }

    public function updatedOrderTypeId($value)
    {
        // Defensive: ensure orderTypeId never stays empty (prevents UI/state mismatch)
        if (! $value) {
            $fallback = OrderType::where('type', 'dine_in')->where('is_active', true)->first();
            if ($fallback) {
                $this->orderTypeId = $fallback->id;
                $this->orderTypeSlug = $fallback->slug;
                $this->orderType = $fallback->type;
                $this->orderTypeName = $fallback->order_type_name;
                $this->selectedDeliveryApp = null;
                $this->selectedDeliveryPlatformName = null;
                session()->put('pos.order_type_id', (int) $this->orderTypeId);
                $value = $this->orderTypeId;
            } else {
                // If dine-in is missing, keep previous behavior of a safe early return
                return;
            }
        }
        // Only clear tax overrides when the cart already has items — i.e., the user
        // explicitly changed the order type mid-session, not during mount initialisation
        // (at mount time the cart is still empty so the override array is empty anyway).
        if (! empty($this->orderItemList)) {
            $this->orderItemPersistedTaxOverride = [];
        }
        // Get the order type information efficiently
        $orderType = OrderType::select('slug', 'type', 'order_type_name')->find($value);

        // Update the local variables to keep them in sync
        $this->orderTypeSlug = $orderType ? $orderType->slug : $this->orderTypeSlug;
        $this->orderType = $orderType ? $orderType->type : $this->orderType;
        $this->orderTypeName = $orderType ? $orderType->order_type_name : null;

        // Keep default checkbox in sync with user preference
        $this->userDefaultOrderTypeId = auth()->user()?->default_order_type_id;
        $this->setAsDefaultOrderType = (bool) ($this->orderTypeId && $this->userDefaultOrderTypeId && ((int) $this->orderTypeId === (int) $this->userDefaultOrderTypeId));

        // Clear delivery platform when leaving delivery order type
        if ($this->orderTypeSlug !== 'delivery') {
            $this->selectedDeliveryApp = null;
            $this->selectedDeliveryPlatformName = null;
        } else {
            $this->refreshSelectedDeliveryPlatformName();
        }

        // Close dropdown after a selection is made
        $this->showOrderTypeDropdown = false;

        // Persist last choice for this session (navigation/refresh)
        session()->put('pos.order_type_id', (int) $this->orderTypeId);

        // If user keeps "set as default" checked and changes type, update their default
        if ($this->setAsDefaultOrderType && auth()->user() && $this->orderTypeId) {
            auth()->user()->update(['default_order_type_id' => (int) $this->orderTypeId]);
            $this->userDefaultOrderTypeId = (int) $this->orderTypeId;
        }

        $mainExtraCharges = RestaurantCharge::whereJsonContains('order_types', $this->orderTypeSlug)
            ->where('is_enabled', true)
            ->get();

        // Handle new orders or table orders without active orders
        if ((! $this->orderID && ! $this->tableOrderID) || ($this->tableOrderID && ! $this->tableOrder->activeOrder)) {
            $this->extraCharges = $mainExtraCharges;
            $this->orderStatus = 'confirmed';

            // Set default delivery fee for delivery orders
            if ($this->orderTypeSlug === 'delivery') {
                $this->deliveryFee = $this->getDefaultDeliveryFee();
            } else {
                $this->deliveryFee = 0;
            }

            // Recalculate prices for all items in cart when order type changes
            foreach ($this->orderItemList as $key => $item) {
                if ($this->orderTypeId) {
                    $item->setPriceContext($this->orderTypeId, $this->normalizeDeliveryAppId());
                    if (isset($this->orderItemVariation[$key])) {
                        $this->orderItemVariation[$key]->setPriceContext($this->orderTypeId, $this->normalizeDeliveryAppId());
                    }
                }

                // Recalculate modifier prices
                if (! empty($this->itemModifiersSelected[$key])) {
                    $modifierOptions = $this->getModifierOptionsProperty();
                    $selected = $this->normalizeModifierQuantities($this->itemModifiersSelected[$key]);
                    $this->orderItemModifiersPrice[$key] = $this->calculateModifierTotal($selected, $modifierOptions);
                }

                // Recalculate item amount
                $basePrice = isset($this->orderItemVariation[$key]) ? $this->orderItemVariation[$key]->price : $item->price;
                $this->orderItemAmount[$key] = $this->orderItemQty[$key] * ($basePrice + ($this->orderItemModifiersPrice[$key] ?? 0));
            }

            $this->calculateTotal();

            return;
        }

        $order = $this->tableOrderID ? $this->tableOrder->activeOrder : Order::find($this->orderID);

        // Early return if no valid order or order is paid
        if (! $order || $order->status === 'paid') {
            return;
        }

        // Efficiently get the slug from the order's order type ID
        $orderTypeSlugFromOrder = $order->order_type_id
            ? OrderType::where('id', $order->order_type_id)->value('slug') ?? $order->order_type
            : $order->order_type;

        // Keep existing charges if order type is unchanged, otherwise set new ones
        $this->extraCharges = $orderTypeSlugFromOrder === $this->orderTypeSlug ? $order->extraCharges : $mainExtraCharges;

        $this->orderStatus = $order->order_status;

        // Recalculate prices for all items in cart when order type changes
        foreach ($this->orderItemList as $key => $item) {
            // Skip combo items - their prices are fixed and shouldn't be recalculated
            if (isset($this->orderItemComboPack[$key]) && ! empty($this->orderItemComboPack[$key])) {
                continue;
            }

            if ($this->orderTypeId) {
                $item->setPriceContext($this->orderTypeId, $this->normalizeDeliveryAppId());
                if (isset($this->orderItemVariation[$key])) {
                    $this->orderItemVariation[$key]->setPriceContext($this->orderTypeId, $this->normalizeDeliveryAppId());
                }
            }

            // Recalculate modifier prices
            if (! empty($this->itemModifiersSelected[$key])) {
                $modifierOptions = $this->getModifierOptionsProperty();
                $selected = $this->normalizeModifierQuantities($this->itemModifiersSelected[$key]);
                $this->orderItemModifiersPrice[$key] = $this->calculateModifierTotal($selected, $modifierOptions);
            }

            // Recalculate item amount
            $basePrice = isset($this->orderItemVariation[$key]) ? $this->orderItemVariation[$key]->price : $item->price;
            $this->orderItemAmount[$key] = $this->orderItemQty[$key] * ($basePrice + ($this->orderItemModifiersPrice[$key] ?? 0));
        }

        $this->calculateTotal();
    }

    /**
     * Get the default delivery fee from branch settings
     */
    private function getDefaultDeliveryFee(): float
    {
        $branch = branch();
        if (! $branch) {
            return 0;
        }

        $deliverySettings = $branch->deliverySetting;
        if (! $deliverySettings || ! $deliverySettings->is_enabled) {
            return 0;
        }

        // Return fixed fee if fee type is fixed
        if ($deliverySettings->fee_type->value === 'fixed') {
            return $deliverySettings->fixed_fee ?? 0;
        }

        // For other fee types, return 0 as they need distance calculation
        return 0;
    }

    /**
     * Update delivery fee and recalculate total
     */
    public function updatedDeliveryFee()
    {
        $this->calculateTotal();
    }

    public function updatedDeliveryDateTime($value)
    {
        if ($value) {
            $selectedDateTime = \Carbon\Carbon::parse($value, restaurant()->timezone ?? config('app.timezone'));
            $minDateTime = now(restaurant()->timezone ?? config('app.timezone'))->addMinute();

            // If selected time is in the past, reset to minimum allowed time
            if ($selectedDateTime->lt($minDateTime)) {
                $this->deliveryDateTime = $minDateTime->format('Y-m-d\TH:i');
                $this->addError('pickupDateTime', 'Please select a future time');
            }
        }
    }

    public function updatedOrderStatus($value)
    {
        if ((! $this->orderID && ! $this->tableOrderID) || ! $this->orderDetail instanceof Order || is_null($value)) {

            return;
        }

        $this->orderDetail->update(['order_status' => $value]);

        if ($value->value === 'confirmed') {
            $this->orderDetail->kot->each(function ($kot) {
                $kot->update(['status' => 'in_kitchen']);
            });
        }
    }

    public function changeOrderType()
    {
        // Check if we have ongoing order items that would be affected
        if (! empty($this->orderItemList)) {
            // Show confirmation dialog before changing
            $this->alert('question', __('modules.order.changeOrderType'), [
                'text' => __('modules.order.changeOrderTypeConfirmation'),
                'showCancelButton' => true,
                'showConfirmButton' => true,
                'withConfirmButton' => __('app.yes').', '.__('app.change'),
                'cancelButtonText' => __('app.cancel'),
                'timer' => 3000,
                'onConfirmed' => 'confirmChangeOrderType',
            ]);
        } else {
            // No items in cart, safe to change directly
            $this->resetOrderTypeSelection();
        }
    }

    #[On('confirmChangeOrderType')]
    public function resetOrderTypeSelection()
    {
        // Keep order type selected; just open the selector so user can change it.
        $this->showOrderTypeDropdown = true;
    }

    public function showTableOrder()
    {
        $this->selectWaiter = $this->tableOrder->activeOrder->waiter_id;
        $this->noOfPax = $this->tableOrder->activeOrder->number_of_pax;
    }

    public function showOrderDetail(bool $runSetup = true)
    {
        $this->orderDetail = $this->tableOrder->activeOrder;
        $this->orderType = $this->orderDetail->order_type;
        $this->orderTypeId = $this->orderDetail->order_type_id;

        // Update orderTypeSlug based on order_type_id if available
        if ($this->orderDetail->order_type_id) {
            $orderType = OrderType::select('slug')->find($this->orderDetail->order_type_id);
            $this->orderTypeSlug = $orderType ? $orderType->slug : $this->orderDetail->order_type;
        } else {
            $this->orderTypeSlug = $this->orderDetail->order_type;
        }

        if ($runSetup) {
            $this->setupOrderItems();
        }
    }

    public function showPayment($id)
    {
        $order = Order::find($id);

        $this->dispatch('showPaymentModal', id: $order->id);
    }

    public function setupOrderItems()
    {
        if ($this->orderDetail) {
            // Rehydrate must start from a clean cart state; otherwise newly-added
            // in-memory lines can survive and be counted again alongside kot_* lines.
            $this->kotList = [];
            $this->orderItemList = [];
            $this->orderItemVariation = [];
            $this->orderItemQty = [];
            $this->orderItemAmount = [];
            $this->orderItemComboPack = [];
            $this->orderItemOriginalPrice = [];
            $this->orderItemComboDiscount = [];
            $this->orderItemComboName = [];
            $this->orderItemUnitPrice = [];
            $this->orderItemDisplayPrice = [];
            $this->orderItemModifiersPrice = [];
            $this->itemModifiersSelected = [];
            $this->itemNotes = [];
            $this->orderItemTaxDetails = [];
            $this->orderItemPersistedTaxOverride = [];
            // Track a unique instance number per combo pack across all KOTs.
            // Each KOT that contains a given combo gets its own instance number,
            // so savings badges and grouping are isolated per KOT.
            $comboInstanceCounters = [];  // comboPackId => next instance number

            // FIFO queues: persisted non-combo order lines (saved when KOT was placed) keyed by
            // menu + variation + qty so reopening the order restores amounts/tax instead of
            // recalculating from current menu prices.
            $persistedIndividualQueues = $this->buildPersistedIndividualOrderItemQueues($this->orderDetail->id);

            // FIFO queues for combo order lines: keyed by combo_pack_id|menu_item_id|variation_id|qty.
            // Preserves correct per-instance pricing when the same combo appears in multiple KOTs.
            $persistedComboQueues = $this->buildPersistedComboOrderItemQueues($this->orderDetail->id);

            foreach ($this->orderDetail->kot as $kot) {
                $this->kotList['kot_'.$kot->id] = $kot;

                // Track which combos have already been assigned an instance number *in this KOT*.
                // All items of the same combo within one KOT share the same instance key.
                $comboInThisKot = [];  // comboPackId => instanceKey assigned for this KOT

                foreach ($kot->items as $item) {
                    $key = 'kot_'.$kot->id.'_'.$item->id;

                    $this->orderItemList[$key] = $item->menuItem;
                    $this->orderItemQty[$key] = $item->quantity;
                    $this->itemModifiersSelected[$key] = $item->modifierOptions->pluck('pivot.quantity', 'id')->toArray();

                    // Check if this KOT item is from a combo pack.
                    // Priority: combo_pack_id stored directly on KotItem (set since fix rev2),
                    // then order_items table lookup, then legacy [COMBO:id] note fallback.
                    $isComboItem = false;
                    $comboPackId = null;
                    $orderItem = null;

                    if ($item->combo_pack_id) {
                        // New: combo_pack_id stored directly on KotItem — unambiguous.
                        $isComboItem = true;
                        $comboPackId = $item->combo_pack_id;
                        // Use FIFO queue so each combo instance gets the correct saved order_item.
                        $orderItem = $this->shiftMatchingPersistedComboOrderItem(
                            $persistedComboQueues, $comboPackId, $item
                        );
                    } else {
                        // For lines without combo_pack_id on KotItem, avoid inferring combo state
                        // by menu/variation lookup (ambiguous when same SKU also sold standalone).
                        // If there is no matching persisted individual line, we can safely
                        // recover combo classification from persisted combo queues.
                        if (! $this->hasPersistedIndividualQueueMatch($persistedIndividualQueues, $item)) {
                            $orderItem = $this->shiftMatchingPersistedComboOrderItemWithoutPack($persistedComboQueues, $item);
                            if ($orderItem && ! empty($orderItem->combo_pack_id)) {
                                $comboPackId = (int) $orderItem->combo_pack_id;
                                $isComboItem = true;
                            }
                        }

                        // Legacy explicit note markers remain supported.
                        if (! $isComboItem && $item->note && str_contains($item->note, '[COMBO:')) {
                            preg_match('/\[COMBO:(\d+)\]/', $item->note, $matches);
                            if (! empty($matches[1])) {
                                $comboPackId = (int) $matches[1];
                                $isComboItem = true;
                                $orderItem = $this->shiftMatchingPersistedComboOrderItem(
                                    $persistedComboQueues,
                                    $comboPackId,
                                    $item
                                );
                            }
                        }
                    }

                    if ($isComboItem && ($orderItem || $comboPackId)) {
                        // Prefer persisted combo instance token so one combo split into multiple
                        // kitchen KOTs still resolves to one "remove whole combo" group.
                        $instanceKey = $this->extractComboInstanceKey($item->note)
                            ?? $this->extractComboInstanceKey($orderItem?->note);

                        // Backward-compatible fallback for old rows without instance tokens.
                        if (! $instanceKey) {
                            // Assign a unique instance key per combo per KOT.
                            // The first time we see comboPackId in this KOT, allocate a new instance number.
                            if (! isset($comboInThisKot[$comboPackId])) {
                                $instanceNum = $comboInstanceCounters[$comboPackId] ?? 0;
                                $comboInstanceCounters[$comboPackId] = $instanceNum + 1;
                                $comboInThisKot[$comboPackId] = $comboPackId.'_'.$instanceNum;
                            }
                            $instanceKey = $comboInThisKot[$comboPackId];
                        }

                        // Restore combo pricing from order item if available
                        if ($orderItem) {
                            $this->orderItemComboPack[$key] = $instanceKey;

                            // Calculate per-unit prices from order item
                            // orderItem->original_price is stored as TOTAL (original_price × quantity)
                            // orderItem->amount is the TOTAL discounted amount (what customer paid)
                            // orderItem->combo_discount_amount is stored as TOTAL discount
                            // Use amount/quantity as source of truth for per-unit discounted price
                            $unitOriginalPrice = $orderItem->original_price && $orderItem->quantity > 0
                                ? $orderItem->original_price / $orderItem->quantity
                                : 0;
                            $unitPrice = $orderItem->amount && $orderItem->quantity > 0
                                ? $orderItem->amount / $orderItem->quantity  // Use amount/quantity as source of truth
                                : ($orderItem->price ?? 0); // Fallback to price field
                            $unitDiscount = $orderItem->combo_discount_amount && $orderItem->quantity > 0
                                ? $orderItem->combo_discount_amount / $orderItem->quantity
                                : 0;

                            // Scale to current KOT item quantity
                            $this->orderItemOriginalPrice[$key] = $unitOriginalPrice * $item->quantity;
                            $this->orderItemComboDiscount[$key] = $unitDiscount * $item->quantity;

                            // Use the discounted price per unit, scaled to KOT item quantity
                            $this->orderItemAmount[$key] = $unitPrice * $item->quantity;
                        } else {
                            // Fallback: recalculate combo price if order item not found
                            $combo = \App\Models\ComboPack::with(['comboPackItems.menuItem', 'comboPackItems.menuItemVariation'])
                                ->find($comboPackId);

                            if ($combo) {
                                $comboItemPrices = $combo->calculateComboItemPrices($this->orderTypeId, $this->normalizeDeliveryAppId());

                                // Find the matching combo item
                                foreach ($comboItemPrices as $itemData) {
                                    $comboItem = $itemData['combo_item'];
                                    if ($comboItem->menu_item_id == $item->menu_item_id &&
                                        $comboItem->menu_item_variation_id == $item->menu_item_variation_id) {
                                        $this->orderItemComboPack[$key] = $instanceKey;
                                        $this->orderItemOriginalPrice[$key] = $itemData['original_price'] * $item->quantity;
                                        $this->orderItemComboDiscount[$key] = $itemData['combo_discount_amount'];
                                        $this->orderItemAmount[$key] = $itemData['price'] * $item->quantity;
                                        break;
                                    }
                                }
                            }
                        }

                        $this->orderItemModifiersPrice[$key] = 0; // Combo items don't have modifiers

                        // Cache combo name for visual grouping in UI (keyed by instance key)
                        if ($comboPackId && ! isset($this->orderItemComboName[$instanceKey])) {
                            $comboNameModel = \App\Models\ComboPack::find($comboPackId);
                            if ($comboNameModel) {
                                $this->orderItemComboName[$instanceKey] = $comboNameModel->getTranslation('name', app()->getLocale());
                            }
                        }
                    } else {
                        // Regular item pricing — prefer a matching persisted order_item row (from
                        // last KOT save) so line totals/tax stay stable if menu prices change.
                        $savedIndividual = $this->shiftMatchingPersistedIndividualOrderItem($persistedIndividualQueues, $item);

                        if ($savedIndividual) {
                            $this->orderItemAmount[$key] = (float) $savedIndividual->amount;
                            if ($this->orderTypeId) {
                                if ($item->menuItem) {
                                    $item->menuItem->setPriceContext($this->orderTypeId, $this->normalizeDeliveryAppId());
                                }
                                if ($item->menuItemVariation) {
                                    $item->menuItemVariation->setPriceContext($this->orderTypeId, $this->normalizeDeliveryAppId());
                                }
                                foreach ($item->modifierOptions as $modifier) {
                                    $modifier->setPriceContext($this->orderTypeId, $this->normalizeDeliveryAppId());
                                }
                            }
                            $this->orderItemModifiersPrice[$key] = $item->modifierOptions->sum(function ($modifier) {
                                $qty = (int) ($modifier->pivot->quantity ?? 1);

                                return $modifier->price * max(1, $qty);
                            });
                            if ($this->taxMode === 'item' && ! is_null($savedIndividual->tax_amount)) {
                                $this->orderItemPersistedTaxOverride[$key] = [
                                    'tax_amount' => (float) $savedIndividual->tax_amount,
                                    'tax_percentage' => $savedIndividual->tax_percentage !== null
                                        ? (float) $savedIndividual->tax_percentage
                                        : null,
                                    'tax_breakup' => $savedIndividual->tax_breakup,
                                ];
                            }
                        } else {
                            // Set price context before calculating amounts
                            if ($this->orderTypeId) {
                                $item->menuItem->setPriceContext($this->orderTypeId, $this->normalizeDeliveryAppId());
                                if ($item->menuItemVariation) {
                                    $item->menuItemVariation->setPriceContext($this->orderTypeId, $this->normalizeDeliveryAppId());
                                }
                                // Set context on modifiers too
                                foreach ($item->modifierOptions as $modifier) {
                                    $modifier->setPriceContext($this->orderTypeId, $this->normalizeDeliveryAppId());
                                }
                            }

                            $this->orderItemModifiersPrice[$key] = $item->modifierOptions->sum(function ($modifier) {
                                $qty = (int) ($modifier->pivot->quantity ?? 1);

                                return $modifier->price * max(1, $qty);
                            });
                            $basePrice = $item->menuItemVariation ? $item->menuItemVariation->price : $item->menuItem->price;
                            $this->orderItemAmount[$key] = $this->orderItemQty[$key] * ($basePrice + ($this->orderItemModifiersPrice[$key] ?? 0));
                        }
                    }

                    if ($item->menuItemVariation) {
                        $this->orderItemVariation[$key] = $item->menuItemVariation;
                    }

                    if ($item->note) {
                        // Strip combo metadata markers so UI notes stay human-readable.
                        $cleanNote = trim(preg_replace('/\s*\[(?:COMBO:\d+|COMBO_INSTANCE:[^\]]+)\]\s*/', '', $item->note));
                        if ($cleanNote !== '') {
                            $this->itemNotes[$key] = $cleanNote;
                        }
                    }
                }
            }

            // Lines billed/saved as order_items but never matched to a KotItem stay in the FIFO
            // queues. Prepend them so the POS cart and totals match the full bill (KOT + non-KOT).
            $prependMaps = $this->buildCartMapsFromUnmatchedPersistedOrderItems(
                $persistedIndividualQueues,
                $persistedComboQueues,
                $comboInstanceCounters
            );

            if (! empty($prependMaps['orderItemList'])) {
                $this->orderItemList = $prependMaps['orderItemList'] + $this->orderItemList;
                $this->orderItemQty = $prependMaps['orderItemQty'] + $this->orderItemQty;
                $this->orderItemAmount = $prependMaps['orderItemAmount'] + $this->orderItemAmount;
                $this->orderItemVariation = $prependMaps['orderItemVariation'] + $this->orderItemVariation;
                $this->itemModifiersSelected = $prependMaps['itemModifiersSelected'] + $this->itemModifiersSelected;
                $this->itemNotes = $prependMaps['itemNotes'] + $this->itemNotes;
                $this->orderItemModifiersPrice = $prependMaps['orderItemModifiersPrice'] + $this->orderItemModifiersPrice;
                $this->orderItemPersistedTaxOverride = $prependMaps['orderItemPersistedTaxOverride'] + $this->orderItemPersistedTaxOverride;
                $this->orderItemComboPack = $prependMaps['orderItemComboPack'] + $this->orderItemComboPack;
                $this->orderItemOriginalPrice = $prependMaps['orderItemOriginalPrice'] + $this->orderItemOriginalPrice;
                $this->orderItemComboDiscount = $prependMaps['orderItemComboDiscount'] + $this->orderItemComboDiscount;
                $this->orderItemComboName = $prependMaps['orderItemComboName'] + $this->orderItemComboName;
                $this->orderItemUnitPrice = $prependMaps['orderItemUnitPrice'] + $this->orderItemUnitPrice;
                $this->orderItemDisplayPrice = $prependMaps['orderItemDisplayPrice'] + $this->orderItemDisplayPrice;
            }

            // Calculate tax details for existing items after setting up all items
            if ($this->taxMode === 'item') {
                $this->updateOrderItemTaxDetails();
            }

            $this->calculateTotal();
        }
    }

    /**
     * True for cart keys that rehydrate an existing order_items row (no duplicate KOT / inserts).
     */
    protected function isAlreadyPersistedOrderItemCartKey(string $key): bool
    {
        $k = str_replace('"', '', (string) $key);

        return str_starts_with($k, 'order_item_');
    }

    /**
     * Lines already on a KOT or already stored as order_items must not be sent as a "new" KOT payload.
     */
    protected function shouldExcludeFromNewKotTicketPayload(string $key): bool
    {
        $k = str_replace('"', '', (string) $key);

        return str_starts_with($k, 'kot_') || str_starts_with($k, 'order_item_');
    }

    /**
     * Build cart slice maps for OrderItem rows still left in persisted queues after KOT matching.
     *
     * @param  array<string, list<OrderItem>>  $persistedIndividualQueues
     * @param  array<string, list<OrderItem>>  $persistedComboQueues
     * @param  array<int, int>  $comboInstanceCounters
     * @return array<string, array<string, mixed>>
     */
    protected function buildCartMapsFromUnmatchedPersistedOrderItems(
        array $persistedIndividualQueues,
        array $persistedComboQueues,
        array &$comboInstanceCounters
    ): array {
        $emptyMaps = [
            'orderItemList' => [],
            'orderItemQty' => [],
            'orderItemAmount' => [],
            'orderItemVariation' => [],
            'itemModifiersSelected' => [],
            'itemNotes' => [],
            'orderItemModifiersPrice' => [],
            'orderItemPersistedTaxOverride' => [],
            'orderItemComboPack' => [],
            'orderItemOriginalPrice' => [],
            'orderItemComboDiscount' => [],
            'orderItemComboName' => [],
            'orderItemUnitPrice' => [],
            'orderItemDisplayPrice' => [],
        ];

        $maps = $emptyMaps;

        $individuals = [];
        foreach ($persistedIndividualQueues as $list) {
            foreach ($list as $oi) {
                $individuals[] = $oi;
            }
        }
        usort($individuals, fn ($a, $b) => $a->id <=> $b->id);

        foreach ($individuals as $oi) {
            $this->appendPersistedOrderItemToCartMaps($oi, null, $maps, $comboInstanceCounters);
        }

        $comboOrphans = [];
        foreach ($persistedComboQueues as $list) {
            foreach ($list as $oi) {
                $comboOrphans[] = $oi;
            }
        }
        usort($comboOrphans, fn ($a, $b) => $a->id <=> $b->id);

        $byInstance = [];
        $remainingCombo = [];
        foreach ($comboOrphans as $oi) {
            $ik = $this->extractComboInstanceKey($oi->note);
            if ($ik !== null && $ik !== '') {
                $byInstance[$ik][] = $oi;
            } else {
                $remainingCombo[] = $oi;
            }
        }

        foreach ($byInstance as $ik => $rows) {
            usort($rows, fn ($a, $b) => $a->id <=> $b->id);
            foreach ($rows as $oi) {
                $this->appendPersistedOrderItemToCartMaps($oi, $ik, $maps, $comboInstanceCounters);
            }
        }

        $n = count($remainingCombo);
        $i = 0;
        while ($i < $n) {
            $pack = (int) $remainingCombo[$i]->combo_pack_id;
            $j = $i + 1;
            while ($j < $n
                && (int) $remainingCombo[$j]->combo_pack_id === $pack
                && ($this->extractComboInstanceKey($remainingCombo[$j]->note) === null
                    || $this->extractComboInstanceKey($remainingCombo[$j]->note) === '')) {
                $j++;
            }

            $instanceNum = $comboInstanceCounters[$pack] ?? 0;
            $comboInstanceCounters[$pack] = $instanceNum + 1;
            $allocatedInstanceKey = $pack.'_'.$instanceNum;

            for ($k = $i; $k < $j; $k++) {
                $this->appendPersistedOrderItemToCartMaps($remainingCombo[$k], $allocatedInstanceKey, $maps, $comboInstanceCounters);
            }
            $i = $j;
        }

        return $maps;
    }

    /**
     * @param  array<string, array<string, mixed>>  $maps
     * @param  array<int, int>  $comboInstanceCounters
     */
    protected function appendPersistedOrderItemToCartMaps(
        OrderItem $oi,
        ?string $forcedComboInstanceKey,
        array &$maps,
        array &$comboInstanceCounters
    ): void {
        $oi->loadMissing(['menuItem', 'menuItemVariation', 'modifierOptions']);

        if (! $oi->menuItem) {
            return;
        }

        $key = 'order_item_'.$oi->id;

        $maps['orderItemList'][$key] = $oi->menuItem;
        $maps['orderItemQty'][$key] = (int) $oi->quantity;
        $maps['orderItemAmount'][$key] = (float) $oi->amount;
        $savedQty = max(1, (int) $oi->quantity);
        $savedPerUnitFromAmount = $savedQty > 0 ? ((float) $oi->amount / $savedQty) : 0.0;
        $savedUnitPrice = $oi->price !== null ? (float) $oi->price : $savedPerUnitFromAmount;
        $maps['orderItemUnitPrice'][$key] = $savedUnitPrice;
        $maps['orderItemDisplayPrice'][$key] = $savedPerUnitFromAmount > 0 ? $savedPerUnitFromAmount : $savedUnitPrice;
        $maps['itemModifiersSelected'][$key] = $oi->modifierOptions->pluck('pivot.quantity', 'id')->toArray();
        $maps['orderItemModifiersPrice'][$key] = $oi->modifierOptions->sum(function ($modifier) {
            $qty = (float) ($modifier->pivot->quantity ?? 1);
            $unitPrice = $modifier->pivot->price !== null
                ? (float) $modifier->pivot->price
                : (float) $modifier->price;

            return $unitPrice * max(1, $qty);
        });

        if ($oi->menuItemVariation) {
            $maps['orderItemVariation'][$key] = $oi->menuItemVariation;
        }

        if ($oi->note) {
            $cleanNote = trim(preg_replace('/\s*\[(?:COMBO:\d+|COMBO_INSTANCE:[^\]]+)\]\s*/', '', (string) $oi->note));
            if ($cleanNote !== '') {
                $maps['itemNotes'][$key] = $cleanNote;
            }
        }

        if ($this->taxMode === 'item' && $oi->tax_amount !== null) {
            $maps['orderItemPersistedTaxOverride'][$key] = [
                'tax_amount' => (float) $oi->tax_amount,
                'tax_percentage' => $oi->tax_percentage !== null ? (float) $oi->tax_percentage : null,
                'tax_breakup' => $oi->tax_breakup,
            ];
        }

        $isComboRow = (bool) $oi->getAttribute('is_combo_item') && $oi->combo_pack_id;

        if ($isComboRow) {
            $packId = (int) $oi->combo_pack_id;

            if ($forcedComboInstanceKey !== null && $forcedComboInstanceKey !== '') {
                $instanceKey = $forcedComboInstanceKey;
            } else {
                $instanceKey = $this->extractComboInstanceKey($oi->note);
                if ($instanceKey === null || $instanceKey === '') {
                    $num = $comboInstanceCounters[$packId] ?? 0;
                    $comboInstanceCounters[$packId] = $num + 1;
                    $instanceKey = $packId.'_'.$num;
                }
            }

            $maps['orderItemComboPack'][$key] = $instanceKey;
            $maps['orderItemOriginalPrice'][$key] = (float) ($oi->original_price ?? 0);
            $maps['orderItemComboDiscount'][$key] = (float) ($oi->combo_discount_amount ?? 0);
            $maps['orderItemModifiersPrice'][$key] = 0;

            if ($packId > 0 && empty($maps['orderItemComboName'][$instanceKey])) {
                $comboNameModel = \App\Models\ComboPack::find($packId);
                if ($comboNameModel) {
                    $maps['orderItemComboName'][$instanceKey] = $comboNameModel->getTranslation('name', app()->getLocale());
                }
            }
        }
    }

    /**
     * @return array<string, list<OrderItem>>
     */
    protected function buildPersistedIndividualOrderItemQueues(int $orderId): array
    {
        $items = OrderItem::query()
            ->where('order_id', $orderId)
            ->where(function ($q) {
                $q->where('is_combo_item', false)->orWhereNull('is_combo_item');
            })
            ->whereNull('combo_pack_id')
            ->orderBy('id')
            ->get();

        $queues = [];
        foreach ($items as $oi) {
            $queues[$this->persistedIndividualLineKey(
                (int) $oi->menu_item_id,
                $oi->menu_item_variation_id !== null ? (int) $oi->menu_item_variation_id : null,
                (int) $oi->quantity
            )][] = $oi;
        }

        return $queues;
    }

    protected function persistedIndividualLineKey(int $menuItemId, ?int $variationId, int $quantity): string
    {
        return $menuItemId.'|'.($variationId ?? 'null').'|'.$quantity;
    }

    protected function shiftMatchingPersistedIndividualOrderItem(array &$queues, KotItem $kotItem): ?OrderItem
    {
        $key = $this->persistedIndividualLineKey(
            (int) $kotItem->menu_item_id,
            $kotItem->menu_item_variation_id !== null ? (int) $kotItem->menu_item_variation_id : null,
            (int) $kotItem->quantity
        );

        if (! isset($queues[$key]) || $queues[$key] === []) {
            return null;
        }

        return array_shift($queues[$key]);
    }

    protected function hasPersistedIndividualQueueMatch(array $queues, KotItem $kotItem): bool
    {
        $key = $this->persistedIndividualLineKey(
            (int) $kotItem->menu_item_id,
            $kotItem->menu_item_variation_id !== null ? (int) $kotItem->menu_item_variation_id : null,
            (int) $kotItem->quantity
        );

        return isset($queues[$key]) && $queues[$key] !== [];
    }

    /**
     * Build a FIFO queue of persisted combo OrderItems keyed by
     * "comboPackId|menuItemId|variationId|quantity" so multiple instances of
     * the same combo each get the correct saved order_item row instead of
     * all sharing the same ->first() result.
     *
     * @return array<string, list<OrderItem>>
     */
    protected function buildPersistedComboOrderItemQueues(int $orderId): array
    {
        $items = OrderItem::query()
            ->where('order_id', $orderId)
            ->where('is_combo_item', true)
            ->whereNotNull('combo_pack_id')
            ->orderBy('id')
            ->get();

        $queues = [];
        foreach ($items as $oi) {
            $queues[$this->persistedComboLineKey(
                (int) $oi->combo_pack_id,
                (int) $oi->menu_item_id,
                $oi->menu_item_variation_id !== null ? (int) $oi->menu_item_variation_id : null,
                (int) $oi->quantity
            )][] = $oi;
        }

        return $queues;
    }

    protected function persistedComboLineKey(int $comboPackId, int $menuItemId, ?int $variationId, int $quantity): string
    {
        return $comboPackId.'|'.$menuItemId.'|'.($variationId ?? 'null').'|'.$quantity;
    }

    protected function shiftMatchingPersistedComboOrderItem(array &$queues, int $comboPackId, KotItem $kotItem): ?OrderItem
    {
        $key = $this->persistedComboLineKey(
            $comboPackId,
            (int) $kotItem->menu_item_id,
            $kotItem->menu_item_variation_id !== null ? (int) $kotItem->menu_item_variation_id : null,
            (int) $kotItem->quantity
        );

        if (! isset($queues[$key]) || $queues[$key] === []) {
            return null;
        }

        return array_shift($queues[$key]);
    }

    protected function shiftMatchingPersistedComboOrderItemWithoutPack(array &$queues, KotItem $kotItem): ?OrderItem
    {
        $menuItemId = (int) $kotItem->menu_item_id;
        $variationId = $kotItem->menu_item_variation_id !== null ? (int) $kotItem->menu_item_variation_id : null;
        $quantity = (int) $kotItem->quantity;
        $suffix = '|'.$menuItemId.'|'.($variationId ?? 'null').'|'.$quantity;

        $matchedKey = null;
        foreach ($queues as $key => $rows) {
            if ($rows === [] || ! str_ends_with((string) $key, $suffix)) {
                continue;
            }

            if ($matchedKey !== null) {
                // Ambiguous combo match across multiple packs; bail out safely.
                return null;
            }
            $matchedKey = (string) $key;
        }

        if ($matchedKey === null) {
            return null;
        }

        return array_shift($queues[$matchedKey]);
    }

    public function addCartItems($id, $variationCount, $modifierCount)
    {
        if (($this->orderID && ! user_can('Update Order')) || (! $this->orderID && ! user_can('Create Order'))) {
            return;
        }

        $this->dispatch('play_beep');
        $this->menuItem = MenuItem::find($id);

        // Set price context immediately after loading the item to prevent price flickering
        if ($this->orderTypeId) {
            $this->menuItem->setPriceContext($this->orderTypeId, $this->normalizeDeliveryAppId());
        }

        // Initialize item note if it doesn't exist
        if (! isset($this->itemNotes[$id])) {
            $this->itemNotes[$id] = '';
        }

        if ($variationCount > 0) {
            $this->showVariationModal = true;
        } elseif ($modifierCount > 0) {
            $this->selectedModifierItem = $id;
            $this->showModifiersModal = true;
        } else {
            $this->syncCart($id);
        }
    }

    public function addComboToCart($comboId)
    {
        if (($this->orderID && ! user_can('Update Order')) || (! $this->orderID && ! user_can('Create Order'))) {
            return;
        }

        $this->dispatch('play_beep');

        $combo = \App\Models\ComboPack::with(['comboPackItems.menuItem', 'comboPackItems.menuItemVariation'])->findOrFail($comboId);

        // Check if combo is available
        if (! $combo->isAvailable()) {
            $this->alert('error', __('modules.combo.comboNotAvailable'), [
                'toast' => true,
                'position' => 'top-end',
            ]);

            return;
        }

        // Phase 3.3: Warn if any combo item already exists individually in the cart
        $conflictNames = [];
        foreach ($combo->comboPackItems as $comboItem) {
            $regularKey = $comboItem->menuItem->id.($comboItem->menu_item_variation_id ? '_'.$comboItem->menu_item_variation_id : '');
            if (isset($this->orderItemList[$regularKey]) && ! isset($this->orderItemComboPack[$regularKey])) {
                $conflictNames[] = $comboItem->menuItem->item_name;
            }
        }
        if (! empty($conflictNames)) {
            $this->alert('info', implode(', ', array_unique($conflictNames)).' '.__('modules.combo.addingAsCombo'), [
                'toast' => true,
                'position' => 'top-end',
                'timer' => 4000,
            ]);
        }

        // Phase 1.5: Set price context on all combo items BEFORE calculating prices
        if ($this->orderTypeId) {
            foreach ($combo->comboPackItems as $comboItem) {
                $comboItem->menuItem->setPriceContext($this->orderTypeId, $this->normalizeDeliveryAppId());
                if ($comboItem->menuItemVariation) {
                    $comboItem->menuItemVariation->setPriceContext($this->orderTypeId, $this->normalizeDeliveryAppId());
                }
            }
        }

        // Calculate combo item prices with discounts (context already set above)
        $comboItemPrices = $combo->calculateComboItemPrices($this->orderTypeId, $this->normalizeDeliveryAppId());

        // Compute which instance number this addition is (supports adding the same combo multiple times)
        $existingInstanceKeys = array_unique(array_filter(
            array_values($this->orderItemComboPack ?? []),
            fn ($v) => str_starts_with((string) $v, $combo->id.'_')
        ));
        $instanceNum = count($existingInstanceKeys);
        $instanceKey = $combo->id.'_'.$instanceNum;

        // Cache combo name for visual grouping in UI (keyed by instance key)
        $this->orderItemComboName[$instanceKey] = $combo->getTranslation('name', app()->getLocale());

        // Add each item from combo to cart
        foreach ($comboItemPrices as $itemData) {
            $comboItem = $itemData['combo_item'];
            $menuItem = $comboItem->menuItem;
            $variationId = $comboItem->menu_item_variation_id;

            // Create unique ID for this combo item (includes instance number to support multiple combos)
            $itemId = $menuItem->id.($variationId ? '_'.$variationId : '');
            $comboItemKey = 'combo_'.$combo->id.'_'.$instanceNum.'_'.$itemId;

            // Store variation reference (price context was already set before calculateComboItemPrices)
            if ($variationId) {
                $variation = $comboItem->menuItemVariation;
                if ($variation) {
                    $this->orderItemVariation[$comboItemKey] = $variation;
                }
            }

            // Store combo pack info
            $this->orderItemList[$comboItemKey] = $menuItem;
            $this->orderItemQty[$comboItemKey] = $comboItem->quantity;

            // Use the calculated combo price (already includes discount)
            $comboPrice = $itemData['price'];
            $originalPrice = $itemData['original_price'];
            $comboDiscount = $itemData['combo_discount_amount'];

            // Store combo metadata
            $this->orderItemAmount[$comboItemKey] = $comboPrice * $comboItem->quantity;
            $this->orderItemComboPack[$comboItemKey] = $instanceKey;
            $this->orderItemOriginalPrice[$comboItemKey] = $originalPrice * $comboItem->quantity;
            $this->orderItemComboDiscount[$comboItemKey] = $comboDiscount;

            // Initialize item note
            if (! isset($this->itemNotes[$comboItemKey])) {
                $this->itemNotes[$comboItemKey] = __('modules.combo.itemsAddedFromCombo');
            }
        }

        $this->calculateTotal();

        $this->alert('success', __('modules.combo.comboAdded'), [
            'toast' => true,
            'position' => 'top-end',
            'timer' => 2000,
        ]);
    }

    #[On('setTable')]
    public function setTable(Table $table)
    {
        // Check table lock status first
        $tableModel = Table::find($table->id);
        if (! $tableModel->canBeAccessedByUser(user()->id)) {
            $session = $tableModel->tableSession;
            $lockedByUser = $session?->lockedByUser;

            $lockedUserName = $lockedByUser?->name ?? 'Admin';
            $this->alert('error', __('messages.tableLockedByUser', ['user' => $lockedUserName]), [
                'toast' => true,
                'position' => 'top-end',
                'timer' => 5000,
                'showCancelButton' => false,
            ]);

            $this->showTableModal = false;

            return;
        }

        // Lock the table for current user
        $lockResult = $tableModel->lockForUser(user()->id);

        if (! $lockResult['success']) {
            $this->alert('error', __('messages.tableLockFailed'), [
                'toast' => true,
                'position' => 'top-end',
                'timer' => 5000,
                'showCancelButton' => false,
            ]);

            $this->showTableModal = false;

            return;
        }

        // Release previous table lock if exists
        if ($this->tableId && $this->tableId !== $table->id) {
            Table::where('id', $this->tableId)->update([
                'available_status' => 'available',
            ]);
        }

        $this->tableNo = $table->table_code;
        $this->tableId = $table->id;

        if ($this->orderID) {
            Order::where('id', $this->orderID)->update(['table_id' => $table->id]);

            // Refresh orderDetail to ensure it's the latest object
            $this->orderDetail = Order::find($this->orderID);

            if (
                $this->orderDetail && is_object($this->orderDetail) && $this->orderDetail->date_time &&
                $this->orderDetail->date_time instanceof \Carbon\Carbon &&
                $this->orderDetail->date_time->format('d-m-Y') == now()->format('d-m-Y')
            ) {
                Table::where('id', $this->tableId)->update([
                    'available_status' => 'running',
                ]);
            }

            $this->orderDetail->fresh();
        }

        $this->showTableModal = false;

        // Show success message
        $this->alert('success', __('messages.tableLocked', ['table' => $table->table_code]), [
            'toast' => true,
            'position' => 'top-end',
            'timer' => 3000,
            'showCancelButton' => false,
        ]);
    }

    #[On('setPosVariation')]
    public function setPosVariation($variationId)
    {
        $this->showVariationModal = false;
        $menuItemVariation = MenuItemVariation::find($variationId);

        // Set price context on variation BEFORE using it to prevent price flickering
        if ($this->orderTypeId) {
            $menuItemVariation->setPriceContext($this->orderTypeId, $this->normalizeDeliveryAppId());
        }

        $modifiersAvailable = $menuItemVariation->menuItem->modifiers->count();

        if ($modifiersAvailable) {
            $this->selectedModifierItem = $menuItemVariation->menu_item_id.'_'.$variationId;
            $this->showModifiersModal = true;
        } else {
            $this->orderItemVariation['"'.$menuItemVariation->menu_item_id.'_'.$variationId.'"'] = $menuItemVariation;
            $this->syncCart('"'.$menuItemVariation->menu_item_id.'_'.$variationId.'"');
        }
    }

    public function syncCart($id)
    {
        // Update table activity when adding items
        if ($this->tableId) {
            $table = Table::find($this->tableId);
            $table?->updateActivity(user()->id);
        }

        if (! isset($this->orderItemList[$id])) {
            // Set price context BEFORE adding to cart to prevent price flickering
            if ($this->orderTypeId) {
                $this->menuItem->setPriceContext($this->orderTypeId, $this->normalizeDeliveryAppId());
                if (isset($this->orderItemVariation[$id])) {
                    $this->orderItemVariation[$id]->setPriceContext($this->orderTypeId, $this->normalizeDeliveryAppId());
                }
            }

            $this->orderItemList[$id] = $this->menuItem;
            $this->orderItemQty[$id] = $this->orderItemQty[$id] ?? 1;

            $basePrice = $this->orderItemVariation[$id]->price ?? $this->orderItemList[$id]->price;
            $this->orderItemAmount[$id] = $this->orderItemQty[$id] * ($basePrice + ($this->orderItemModifiersPrice[$id] ?? 0));
            $this->calculateTotal();
        } else {
            $this->addQty($id);
        }
    }

    public function deleteCartItems($id)
    {
        if ($this->isComboCartLine($id)) {
            $this->alert('error', 'Combo items cannot be removed individually. Remove the whole combo.', ['toast' => true, 'position' => 'top-end']);

            return;
        }

        if ($this->requiresRemovalReason($id)) {
            if (! user_can('Delete KOT Item')) {
                $this->alert('error', __('messages.kotDeletePermissionDenied'), [
                    'toast' => true,
                    'position' => 'top-end',
                    'showCancelButton' => false,
                    'cancelButtonText' => __('app.close'),
                ]);

                return;
            }

            $this->promptRemovalReason($id, 'delete');

            return;
        }

        $this->executeDeleteCartItems($id);
    }

    public function removeComboGroup(string $instanceKey): void
    {
        if (! $this->canModifyCurrentOrderItems()) {
            $this->alert('error', __('messages.noPermission'), ['toast' => true, 'position' => 'top-end']);

            return;
        }

        $keysToDelete = array_keys(array_filter(
            $this->orderItemComboPack ?? [],
            fn ($v) => $v === $instanceKey
        ));

        // Keep combo-group removal guard identical to individual cart-line removal:
        // existing KOT lines require Delete KOT Item permission.
        if (! empty($keysToDelete) && $this->requiresRemovalReason($keysToDelete[0]) && ! user_can('Delete KOT Item')) {
            $this->alert('error', __('messages.kotDeletePermissionDenied'), [
                'toast' => true,
                'position' => 'top-end',
                'showCancelButton' => false,
                'cancelButtonText' => __('app.close'),
            ]);

            return;
        }

        $firstComboKey = $keysToDelete[0] ?? null;
        $isPersistedExistingCombo = $firstComboKey
            ? $this->isAlreadyPersistedOrderItemCartKey((string) $firstComboKey)
            : false;

        // Existing-items combos (order_item_*) must also collect a reason to stay aligned with
        // KOT reduction/removal workflow.
        if (! empty($keysToDelete) && ($this->requiresRemovalReason($firstComboKey) || $isPersistedExistingCombo)) {
            $this->pendingRemovalComboItems = $keysToDelete;
            $this->pendingRemovalItem = '__combo__';
            $this->pendingRemovalAction = 'delete_combo';
            $this->pendingRemovalNewQuantity = null;
            $this->removalReason = '';
            $this->showRemovalReasonModal = true;

            return;
        }

        foreach ($keysToDelete as $key) {
            $this->executeDeleteCartItems($key);
        }

        unset($this->orderItemComboName[$instanceKey]);
        $this->calculateTotal();
    }

    /**
     * Apply a batch of client-queued POS operations in a single request.
     * Keeps interaction client-first while reducing Livewire round-trips.
     */
    public function applyClientOps(array $operations): void
    {
        if (empty($operations)) {
            return;
        }

        if (($this->orderID && ! user_can('Update Order')) || (! $this->orderID && ! user_can('Create Order'))) {
            return;
        }

        $maxOps = 40;
        $ops = array_slice($operations, 0, $maxOps);

        foreach ($ops as $op) {
            if (! is_array($op)) {
                continue;
            }

            $type = (string) ($op['type'] ?? '');

            if ($type === 'add_item') {
                $itemId = (int) ($op['id'] ?? 0);
                $variationCount = (int) ($op['variationCount'] ?? 0);
                $modifierCount = (int) ($op['modifierCount'] ?? 0);

                if ($itemId > 0) {
                    $this->addCartItems($itemId, $variationCount, $modifierCount);
                }

                continue;
            }

            if ($type === 'add_combo') {
                $comboId = (int) ($op['comboId'] ?? 0);
                if ($comboId > 0) {
                    $this->addComboToCart($comboId);
                }

                continue;
            }

            if ($type === 'qty_delta') {
                $lineKey = (string) ($op['key'] ?? '');
                $delta = (int) ($op['delta'] ?? 0);

                if ($lineKey === '' || $delta === 0) {
                    continue;
                }

                if ($delta > 0) {
                    for ($i = 0; $i < $delta; $i++) {
                        $this->optimisticAddQty($lineKey);
                    }
                } else {
                    for ($i = 0; $i < abs($delta); $i++) {
                        $this->optimisticSubQty($lineKey);
                    }
                }

                continue;
            }

            if ($type === 'qty_set') {
                $lineKey = (string) ($op['key'] ?? '');
                $qty = (int) ($op['qty'] ?? 0);

                if ($lineKey === '') {
                    continue;
                }

                if ($qty <= 0) {
                    if ($this->shouldBatchRemovalPromptForPersistedLine($lineKey)) {
                        if ($this->requiresRemovalReason($lineKey) && ! user_can('Delete KOT Item')) {
                            $this->alert('error', __('messages.kotDeletePermissionDenied'), [
                                'toast' => true,
                                'position' => 'top-end',
                                'showCancelButton' => false,
                                'cancelButtonText' => __('app.close'),
                            ]);

                            continue;
                        }
                        if ($this->isAlreadyPersistedOrderItemCartKey($lineKey) && ! $this->canModifyCurrentOrderItems()) {
                            $this->alert('error', __('messages.noPermission'), [
                                'toast' => true,
                                'position' => 'top-end',
                            ]);

                            continue;
                        }
                        $this->promptRemovalReason($lineKey, 'delete');

                        continue;
                    }
                    // Remove line through full deletion path to clear all dependent cart state.
                    $this->executeDeleteCartItems($lineKey);
                } else {
                    // Set new quantity
                    $this->orderItemQty[$lineKey] = $qty;
                    $this->updateQty($lineKey);
                }

                continue;
            }

            if ($type === 'remove_item') {
                $lineKey = (string) ($op['key'] ?? '');

                if ($lineKey === '') {
                    continue;
                }

                $this->deleteCartItems($lineKey);
            }
        }
    }

    public function deleteOrderItems($id)
    {
        $orderStatus = $this->orderDetail?->status ?? null;
        $isBilledOrPaid = in_array($orderStatus, ['billed', 'paid', 'payment_due']);

        if ($isBilledOrPaid && ! user_can('Edit Billed Order')) {
            $this->alert('error', __('messages.noPermission'), ['toast' => true, 'position' => 'top-end']);

            return;
        }
        if (! $isBilledOrPaid && ! user_can('Delete Order')) {
            $this->alert('error', __('messages.noPermission'), ['toast' => true, 'position' => 'top-end']);

            return;
        }

        $orderItem = OrderItem::find($id);

        if ($orderItem) {
            $kotItems = KotItem::where('menu_item_id', $orderItem->menu_item_id)
                ->where('menu_item_variation_id', $orderItem->menu_item_variation_id)
                ->where('quantity', $orderItem->quantity)
                ->whereHas('kot', function ($query) use ($orderItem) {
                    $query->where('order_id', $orderItem->order_id);
                })
                ->get();

            foreach ($kotItems as $kotItem) {
                $kotItem->delete();
            }
        }

        OrderItem::destroy($id);

        if ($this->orderDetail && $this->orderDetail instanceof Order) {
            $this->orderDetail->refresh();

            if ($this->orderDetail->items->count() === 0) {
                $this->deleteOrder($this->orderDetail->id);
                $this->orderDetail = null;
                $this->orderID = null;

                $this->alert('success', __('messages.orderDeleted'), [
                    'toast' => true,
                    'position' => 'top-end',
                    'showCancelButton' => false,
                    'cancelButtonText' => __('app.close'),
                ]);

                return $this->redirect(route('pos.index'), navigate: true);
            }

            $this->total = 0;
            $this->subTotal = 0;

            $this->discountedTotal = $this->total;

            $this->recalculateTaxTotals();

            foreach ($this->extraCharges ?? [] as $value) {
                $this->total += $value->getAmount($this->subTotal);
            }

            Order::where('id', $this->orderDetail->id)->update([
                'sub_total' => $this->subTotal,
                'total' => $this->total,
            ]);
        }
    }

    public function deleteOrder($id)
    {
        $order = Order::find($id);

        if (! $order) {
            $this->alert('error', __('messages.orderNotFound'), [
                'toast' => true,
                'position' => 'top-end',
                'showCancelButton' => false,
                'cancelButtonText' => __('app.close'),
            ]);

            return;
        }

        if ($order->table_id) {
            Table::where('id', $order->table_id)->update(['available_status' => 'available']);
        }

        // Delete associated KOT records
        $order->kot()->delete();

        $order->delete();

        $this->alert('success', __('messages.orderDeleted'), [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close'),
        ]);

        return $this->redirect(route('pos.index'), navigate: true);
    }

    public function addQty($id)
    {
        if (($this->orderID && ! user_can('Update Order')) || (! $this->orderID && ! user_can('Create Order'))) {
            return;
        }

        if ($this->isComboCartLine($id)) {
            $this->alert('error', 'Combo item quantity cannot be edited individually. Edit/remove the whole combo.', ['toast' => true, 'position' => 'top-end']);

            return;
        }

        // Update table activity when changing quantities
        if ($this->tableId) {
            $table = Table::find($this->tableId);
            $table?->updateActivity(user()->id);
        }

        $this->orderItemQty[$id] = isset($this->orderItemQty[$id]) ? ($this->orderItemQty[$id] + 1) : 1;

        // Set price context before using price
        if ($this->orderTypeId) {
            if (isset($this->orderItemVariation[$id])) {
                $this->orderItemVariation[$id]->setPriceContext($this->orderTypeId, $this->normalizeDeliveryAppId());
            }
            if (isset($this->orderItemList[$id])) {
                $this->orderItemList[$id]->setPriceContext($this->orderTypeId, $this->normalizeDeliveryAppId());
            }
        }

        $basePrice = $this->orderItemVariation[$id]->price ?? $this->orderItemList[$id]->price;
        $this->orderItemAmount[$id] = $this->orderItemQty[$id] * ($basePrice + ($this->orderItemModifiersPrice[$id] ?? 0));
        $this->calculateTotal();
    }

    public function subQty($id)
    {
        if (($this->orderID && ! user_can('Update Order')) || (! $this->orderID && ! user_can('Create Order'))) {
            return;
        }

        if ($this->isComboCartLine($id)) {
            $this->alert('error', 'Combo items cannot be removed individually. Remove the whole combo.', ['toast' => true, 'position' => 'top-end']);

            return;
        }

        if ($this->requiresRemovalReason($id)) {
            if (! user_can('Delete KOT Item')) {
                $this->alert('error', __('messages.kotDeletePermissionDenied'), [
                    'toast' => true,
                    'position' => 'top-end',
                    'showCancelButton' => false,
                    'cancelButtonText' => __('app.close'),
                ]);

                return;
            }

            $context = $this->parseKotContext($id);
            if (! $context) {
                return;
            }

            $kotItem = KotItem::find($context['kot_item_id']);

            if (! $kotItem) {
                return;
            }

            if ($kotItem->quantity <= 1) {
                $this->promptRemovalReason($id, 'delete');
            } else {
                $this->promptRemovalReason($id, 'decrement', $kotItem->quantity - 1);
            }

            return;
        }

        // Update table activity when changing quantities
        if ($this->tableId) {
            $table = Table::find($this->tableId);
            $table?->updateActivity(user()->id);
        }

        $this->orderItemQty[$id] = (isset($this->orderItemQty[$id]) && $this->orderItemQty[$id] > 1) ? ($this->orderItemQty[$id] - 1) : 1;

        // Set price context before using price
        if ($this->orderTypeId) {
            if (isset($this->orderItemVariation[$id])) {
                $this->orderItemVariation[$id]->setPriceContext($this->orderTypeId, $this->normalizeDeliveryAppId());
            }
            if (isset($this->orderItemList[$id])) {
                $this->orderItemList[$id]->setPriceContext($this->orderTypeId, $this->normalizeDeliveryAppId());
            }
        }

        $basePrice = $this->orderItemVariation[$id]->price ?? $this->orderItemList[$id]->price;
        $this->orderItemAmount[$id] = $this->orderItemQty[$id] * ($basePrice + ($this->orderItemModifiersPrice[$id] ?? 0));
        $this->calculateTotal();
    }

    public function calculateTotal(bool $skipRealtimeSideEffects = false)
    {
        $this->total = 0;
        $this->subTotal = 0;
        $this->totalTaxAmount = 0;

        // If cart is empty and status was billed, reset to idle for new order
        if (empty($this->orderItemList) && $this->customerDisplayStatus === 'billed') {
            $this->customerDisplayStatus = 'idle';
        }

        if (is_array($this->orderItemAmount)) {
            // Calculate item taxes first for proper subtotal calculation
            if ($this->taxMode === 'item') {
                $this->updateOrderItemTaxDetails();
            }

            foreach ($this->orderItemAmount as $key => $value) {
                $this->total += $value;

                // For inclusive taxes, subtract tax from subtotal
                if ($this->taxMode === 'item' && isset($this->orderItemTaxDetails[$key])) {
                    $taxDetail = $this->orderItemTaxDetails[$key];
                    $isInclusive = restaurant()->tax_inclusive ?? false;

                    if ($isInclusive) {
                        // For inclusive tax: subtotal = item amount - tax amount
                        $this->subTotal += ($value - ($taxDetail['tax_amount'] ?? 0));
                    } else {
                        // For exclusive tax: subtotal = item amount (tax will be added later)
                        $this->subTotal += $value;
                    }
                } else {
                    // No item taxes or order-level taxes
                    $this->subTotal += $value;
                }
            }
        }

        $itemsSubTotalForDiscount = $this->subTotal;
        $extrasTotal = $this->getOrderExtrasTotal();

        // Extras are part of order total, but excluded from discount calculations
        if ($extrasTotal > 0) {
            $this->total += $extrasTotal;
        }

        // Apply discounts
        if ($this->discountValue > 0 && $this->discountType) {
            if ($this->discountType === 'percent') {
                $this->discountAmount = round(($itemsSubTotalForDiscount * $this->discountValue) / 100, 2);
            } elseif ($this->discountType === 'fixed') {
                $this->discountAmount = min($this->discountValue, $itemsSubTotalForDiscount);
            }

            $this->total -= $this->discountAmount;
        }

        // Charges/taxes base should match POS UI breakdown:
        // base = (items subtotal + custom extras) - discount
        // Note: custom extras are excluded from discount calculations (handled above).
        $chargeAndTaxBase = max(0, round(($this->subTotal + $extrasTotal) - ((float) ($this->discountAmount ?? 0)), 2));
        $this->discountedTotal = $chargeAndTaxBase;

        // Calculate taxes using centralized method
        $this->recalculateTaxTotals();

        // Apply extra charges
        if (! empty($this->orderItemAmount) && $this->extraCharges) {
            foreach ($this->extraCharges ?? [] as $charge) {
                $this->total += $charge->getAmount($this->discountedTotal);
            }
        }

        // Add tip and delivery fees
        if ($this->tipAmount > 0) {
            $this->total += $this->tipAmount;
        }

        if ($this->deliveryFee > 0) {
            $this->total += $this->deliveryFee;
        }

        // Apply reward points discount (after all other calculations)
        if ($this->rewardPointDiscount > 0) {
            $this->total = max(0, $this->total - $this->rewardPointDiscount);
        }

        if (! $skipRealtimeSideEffects) {
            // Calculate tax and charge amounts for display
            $taxesForDisplay = collect($this->taxes ?? [])->map(function ($tax) {
                $amount = (($tax->tax_percent / 100) * $this->discountedTotal);

                return [
                    'name' => $tax->tax_name,
                    'percent' => $tax->tax_percent,
                    'amount' => $amount,
                ];
            })->toArray();
            $chargesForDisplay = collect($this->extraCharges ?? [])->map(function ($charge) {
                return [
                    'name' => $charge->name,
                    'amount' => $charge->getAmount($this->discountedTotal),
                ];
            })->toArray();
            $displayItems = $this->getCustomerDisplayItems();
            $displayCustomExtras = $this->getCustomerDisplayCustomExtras();

            $paymentGateway = restaurant()->paymentGateways;
            $qrCodeImageUrl = $paymentGateway && $paymentGateway->is_qr_payment_enabled ? $paymentGateway->qr_code_image_url : null;

            $customerDisplayData = [
                'order_number' => $this->orderNumber,
                'formatted_order_number' => $this->formattedOrderNumber,
                'items' => $displayItems,
                'custom_extras' => $displayCustomExtras,
                'sub_total' => $this->subTotal,
                'discount' => $this->discountAmount ?? 0,
                'reward_point_discount' => $this->rewardPointDiscount ?? 0,
                'total' => $this->total,
                'taxes' => $taxesForDisplay,
                'extra_charges' => $chargesForDisplay,
                'tip' => $this->tipAmount,
                'delivery_fee' => $this->deliveryFee,
                'order_type' => $this->orderType,
                'status' => $this->customerDisplayStatus ?? 'idle',
                'cash_due' => ($this->customerDisplayStatus ?? null) === 'billed' ? $this->total : null,
                'qr_code_image_url' => $qrCodeImageUrl,
            ];

            $userId = auth()->id();
            $cacheKey = 'customer_display_cart_user_'.$userId;
            Cache::put($cacheKey, $customerDisplayData, now()->addMinutes(30));

            // Broadcast customer display update if Pusher is enabled
            $isPusherEnabled = (bool) optional(pusherSettings())->is_enabled_pusher_broadcast;
            if ($isPusherEnabled) {
                try {
                    broadcast(new \App\Events\CustomerDisplayUpdated($customerDisplayData, $userId));
                } catch (\Exception $e) {
                    // Log the error but don't break the request
                    // Common causes: network timeout, SSL issues, Pusher API down
                    \Log::warning('Pusher broadcast failed for CustomerDisplayUpdated', [
                        'error' => $e->getMessage(),
                        'user_id' => $userId,
                        'exception_class' => get_class($e),
                    ]);
                }
            }

            // Optionally, still dispatch browser event
            $this->dispatch('orderUpdated', [
                'order_number' => $this->orderNumber,
                'formatted_order_number' => $this->formattedOrderNumber,
                'items' => $displayItems,
                'custom_extras' => $displayCustomExtras,
                'sub_total' => $this->subTotal,
                'discount' => $this->discountAmount ?? 0,
                'reward_point_discount' => $this->rewardPointDiscount ?? 0,
                'total' => $this->total,
            ]);
        }
    }

    public function updated($name, $value)
    {
        if (is_string($name) && Str::startsWith($name, 'orderExtras.')) {
            $this->calculateTotal();
        }
    }

    public function updatedOrderExtras()
    {
        $this->calculateTotal();
    }

    public function addOrderExtraRow()
    {
        if (! (restaurant()->allow_custom_order_extras ?? false)) {
            return;
        }

        if (($this->orderID && ! user_can('Update Order')) || (! $this->orderID && ! user_can('Create Order'))) {
            return;
        }

        $this->orderExtras[] = [
            'amount' => 0,
            'note' => '',
        ];

        $this->calculateTotal();
    }

    public function removeOrderExtraRow($index)
    {
        if (($this->orderID && ! user_can('Update Order')) || (! $this->orderID && ! user_can('Create Order'))) {
            return;
        }

        if (! isset($this->orderExtras[$index])) {
            return;
        }

        unset($this->orderExtras[$index]);
        $this->orderExtras = array_values($this->orderExtras);
        $this->calculateTotal();
    }

    private function normalizeOrderExtras(): array
    {
        $normalized = [];

        foreach (($this->orderExtras ?? []) as $extra) {
            if (! is_array($extra)) {
                continue;
            }

            $note = trim((string) ($extra['note'] ?? ''));
            $amount = (float) ($extra['amount'] ?? 0);
            $amount = max(0, round($amount, 2));

            if ($note === '' && $amount <= 0) {
                continue;
            }

            $normalized[] = [
                'note' => ($note !== '' ? $note : null),
                'amount' => $amount,
            ];
        }

        return $normalized;
    }

    private function getOrderExtrasTotal(): float
    {
        return (float) collect($this->normalizeOrderExtras())->sum('amount');
    }

    private function getCustomerDisplayCustomExtras(): array
    {
        return $this->normalizeOrderExtras();
    }

    private function syncOrderExtras(Order $order): void
    {
        if (! (restaurant()->allow_custom_order_extras ?? false)) {
            return;
        }

        $extras = $this->normalizeOrderExtras();

        $order->extras()->delete();

        if (! empty($extras)) {
            $order->extras()->createMany($extras);
        }
    }

    private function recalculateTaxTotals()
    {
        $this->totalTaxAmount = 0;

        if ($this->taxMode === 'order') {
            foreach ($this->taxes as $tax) {
                $taxAmount = ($tax->tax_percent / 100) * $this->discountedTotal;
                $this->totalTaxAmount += $taxAmount;
                $this->total += $taxAmount;
            }
        } elseif ($this->taxMode === 'item' && ! empty($this->orderItemAmount)) {
            // Item-based taxation - taxes are already calculated in calculateTotal()
            $totalInclusiveTax = 0;
            $totalExclusiveTax = 0;
            $isInclusive = restaurant()->tax_inclusive ?? false;

            // Calculate total tax amounts
            foreach ($this->orderItemTaxDetails as $itemTaxDetail) {
                $taxAmount = $itemTaxDetail['tax_amount'] ?? 0;

                if ($isInclusive) {
                    $totalInclusiveTax += $taxAmount;
                } else {
                    $totalExclusiveTax += $taxAmount;
                }
            }

            $this->totalTaxAmount = $totalInclusiveTax + $totalExclusiveTax;

            // For exclusive taxes, add them to the total
            // (Inclusive taxes are already included in the item prices)
            if ($totalExclusiveTax > 0) {
                $this->total += $totalExclusiveTax;
            }
        }
    }

    public function addDiscounts()
    {
        $order = $this->tableOrderID ? $this->tableOrder->activeOrder : $this->orderDetail;
        $isBilledOrPaid = $order && in_array($order->status, ['billed', 'paid', 'payment_due']);

        if ($isBilledOrPaid && ! user_can('Edit Billed Order')) {
            $this->alert('error', __('messages.noPermission'), ['toast' => true, 'position' => 'top-end']);

            return;
        }

        if (! $isBilledOrPaid && ! user_can('Update Order')) {
            $this->alert('error', __('messages.noPermission'), ['toast' => true, 'position' => 'top-end']);

            return;
        }

        $this->validate([
            'discountValue' => 'required|numeric|min:0',
            'discountType' => 'required|in:fixed,percent',
        ]);

        if ($this->discountType === 'percent' && $this->discountValue > 100) {
            $this->alert('error', __('messages.discountPercentError'), [
                'toast' => true,
                'position' => 'top-end',
                'showCancelButton' => false,
                'cancelButtonText' => __('app.close'),
            ]);

            return;
        }

        $order = $this->tableOrderID ? $this->tableOrder->activeOrder : $this->orderDetail;

        if ($order) {
            $subTotal = (float) $order->sub_total;

            if ($this->discountType === 'percent') {
                $discountAmount = round(($subTotal * $this->discountValue) / 100, 2);
            } else {
                $discountAmount = min((float) $this->discountValue, $subTotal);
            }

            $oldDiscount = (float) ($order->discount_amount ?? 0);
            $newTotal = max(0, ($order->total + $oldDiscount) - $discountAmount);
            $statusBefore = $order->status;

            try {
                DB::transaction(function () use ($order, $discountAmount, $newTotal, $statusBefore) {
                    $order->update([
                        'discount_type' => $this->discountType,
                        'discount_value' => $this->discountValue,
                        'discount_amount' => $discountAmount,
                        'total' => $newTotal,
                    ]);

                    if (in_array($statusBefore, ['paid', 'payment_due'], true)) {
                        $order->refresh();
                        $order->load('payments');
                        $canonicalTotal = (float) $order->total;
                        $this->reconcilePaymentsAfterDiscount($order, $canonicalTotal);
                    }
                });
            } catch (\RuntimeException $e) {
                $this->alert('warning', $e->getMessage(), [
                    'toast' => true,
                    'position' => 'top-end',
                    'showCancelButton' => false,
                    'cancelButtonText' => __('app.close'),
                ]);

                return;
            }

            $this->discountAmount = $discountAmount;
        }

        $this->calculateTotal();

        $this->showDiscountModal = false;
    }

    public function removeCurrentDiscount()
    {
        $order = $this->tableOrderID ? $this->tableOrder->activeOrder : $this->orderDetail;
        $isBilledOrPaid = $order && in_array($order->status, ['billed', 'paid', 'payment_due']);

        if ($isBilledOrPaid && ! user_can('Edit Billed Order')) {
            $this->alert('error', __('messages.noPermission'), ['toast' => true, 'position' => 'top-end']);

            return;
        }

        if (! $isBilledOrPaid && ! user_can('Update Order')) {
            $this->alert('error', __('messages.noPermission'), ['toast' => true, 'position' => 'top-end']);

            return;
        }

        if ($order) {
            $statusBefore = $order->status;
            $removedDiscount = (float) $order->discount_amount;
            $newTotal = $order->total + $removedDiscount;

            if (in_array($statusBefore, ['paid', 'payment_due'], true)) {
                $amountPaid = $order->payments()
                    ->where('payment_method', '!=', 'due')
                    ->sum('amount');

                if ($amountPaid < $newTotal - 0.0001 && ! $order->canRecordDueBalance()) {
                    $this->alert('warning', __('modules.order.customerRequiredForDuePayment'), [
                        'toast' => true,
                        'position' => 'top-end',
                        'showCancelButton' => false,
                        'cancelButtonText' => __('app.close'),
                    ]);
                    $this->dispatch(
                        'showAddCustomerModal',
                        id: $order->id,
                        customerId: null,
                        fromPos: true,
                        forDuePayment: true,
                        preferDueAfterAttach: false
                    )->to(AddCustomer::class);

                    return;
                }
            }

            $order->update([
                'discount_type' => null,
                'discount_value' => null,
                'discount_amount' => null,
                'total' => $newTotal,
            ]);

            // If already paid/partially paid, the new higher total may create a shortfall
            if (in_array($statusBefore, ['paid', 'payment_due'], true)) {
                $order->refresh();
                $amountPaid = $order->payments()
                    ->where('payment_method', '!=', 'due')
                    ->sum('amount');

                if ($amountPaid < $newTotal - 0.0001) {
                    $shortfall = round($newTotal - $amountPaid, 2);
                    $order->payments()->create([
                        'payment_method' => 'due',
                        'amount' => $shortfall,
                        'order_id' => $order->id,
                    ]);
                    $order->update(['status' => 'payment_due']);
                }
            }
        }

        $this->discountType = null;
        $this->discountValue = null;
        $this->discountAmount = null;
        $this->calculateTotal();
    }

    /**
     * After a discount reduces the order total, trim any overpaid amounts and
     * re-evaluate the order status (paid vs payment_due).
     */
    private function reconcilePaymentsAfterDiscount($order, float $newTotal): void
    {
        $payments = $order->payments()
            ->where('payment_method', '!=', 'due')
            ->orderBy('id')
            ->get();

        $excess = round($payments->sum('amount') - $newTotal, 2);

        if ($excess > 0) {
            foreach ($payments->sortByDesc('id') as $payment) {
                if ($excess <= 0) {
                    break;
                }
                $canReduce = min((float) $payment->amount, $excess);
                $payment->update(['amount' => round($payment->amount - $canReduce, 2)]);
                $excess = round($excess - $canReduce, 2);
            }
        }

        $amountPaid = $order->payments()
            ->where('payment_method', '!=', 'due')
            ->sum('amount');

        $newStatus = ($amountPaid >= $newTotal - 0.0001) ? 'paid' : 'payment_due';

        if ($newStatus === 'payment_due' && ! $order->canRecordDueBalance()) {
            throw new \RuntimeException(__('modules.order.customerRequiredForDuePayment'));
        }

        $order->update([
            'amount_paid' => $amountPaid,
            'status' => $newStatus,
        ]);
    }

    public function removeExtraCharge($chargeId, $orderType)
    {
        $order = $this->tableOrderID ? $this->tableOrder->activeOrder : $this->orderDetail;

        if ($order) {
            $extraCharge = $this->extraCharges->firstWhere('id', $chargeId);
            if ($extraCharge) {
                $order->extraCharges()->detach($chargeId);
                $this->total -= $extraCharge->getAmount($this->discountedTotal);
                $order->update(['total' => $this->total]);
            }
        }

        $this->extraCharges = $this->extraCharges->filter(function ($charge) use ($chargeId) {
            return $charge->id != $chargeId;
        });

        $this->calculateTotal();
    }

    public function saveOrder($action, $secondAction = null, $thirdAction = null)
    {
        // Permission check before any other processing
        if ($action === 'cancel') {
            if (! user_can('Delete Order')) {
                $this->alert('error', __('messages.noPermission'), ['toast' => true, 'position' => 'top-end']);

                return;
            }
        } elseif ($this->orderID) {
            $orderForPerm = Order::find($this->orderID);
            $kotAfterBilled = $action === 'kot'
                && $orderForPerm
                && in_array($orderForPerm->status, ['billed', 'paid', 'payment_due'], true);

            if ($kotAfterBilled) {
                if (! user_can('Edit Billed Order')) {
                    $this->alert('error', __('messages.noPermission'), ['toast' => true, 'position' => 'top-end']);

                    return;
                }
            } elseif (! user_can('Update Order')) {
                $this->alert('error', __('messages.noPermission'), ['toast' => true, 'position' => 'top-end']);

                return;
            }
        } else {
            if (! user_can('Create Order')) {
                $this->alert('error', __('messages.noPermission'), ['toast' => true, 'position' => 'top-end']);

                return;
            }
        }

        // Check if table is locked by another user before saving order
        if ($this->tableId && $this->orderType === 'dine_in') {
            $table = Table::find($this->tableId);
            if ($table && ! $table->canBeAccessedByUser(user()->id)) {
                $session = $table->tableSession;
                $lockedByUser = $session?->lockedByUser;
                $lockedUserName = $lockedByUser?->name ?? 'Another user';

                $this->alert('error', __('messages.tableHandledByUser', ['user' => $lockedUserName, 'table' => $table->table_code]), [
                    'toast' => true,
                    'position' => 'top-end',
                ]);

                return;
            }
        }

        $this->showErrorModal = true;

        $rules = [
            'selectDeliveryExecutive' => Rule::requiredIf($action !== 'cancel' && $this->orderType === 'delivery' && $this->selectedDeliveryApp === 'default'),
            'orderItemList' => 'required',
            'deliveryFee' => 'nullable|numeric|min:0',
        ];

        if (! $this->orderID && ! $this->tableOrderID) {
            $rules['selectWaiter'] = 'required_if:orderType,dine_in';
        }

        $messages = [
            'noOfPax.required_if' => __('messages.enterPax'),
            'tableNo.required_if' => __('messages.setTableNo'),
            'selectWaiter.required_if' => __('messages.selectWaiter'),
            'orderItemList.required' => __('messages.orderItemRequired'),
        ];

        $this->validate($rules, $messages);

        // Defensive recalculation: ensure orderItemAmount matches qty × price
        // This prevents desync when user edits qty and clicks KOT/Bill before
        // wire:change fires updateQty() (Livewire batching race condition)
        foreach ($this->orderItemList as $key => $item) {
            // Skip combo items - they have special pricing
            if (! empty($this->orderItemComboPack[$key])) {
                continue;
            }

            if ($this->orderTypeId) {
                $item->setPriceContext($this->orderTypeId, $this->normalizeDeliveryAppId());
                if (isset($this->orderItemVariation[$key])) {
                    $this->orderItemVariation[$key]->setPriceContext($this->orderTypeId, $this->normalizeDeliveryAppId());
                }
            }

            $basePrice = $this->orderItemVariation[$key]->price ?? $item->price;
            $expectedAmount = $this->orderItemQty[$key] * ($basePrice + ($this->orderItemModifiersPrice[$key] ?? 0));

            if (abs(($this->orderItemAmount[$key] ?? 0) - $expectedAmount) > 0.01) {
                \Log::warning('POS amount desync corrected in saveOrder', [
                    'item' => $item->item_name ?? $key,
                    'qty' => $this->orderItemQty[$key],
                    'old_amount' => $this->orderItemAmount[$key] ?? 0,
                    'corrected_amount' => $expectedAmount,
                    'base_price' => $basePrice,
                    'modifier_price' => $this->orderItemModifiersPrice[$key] ?? 0,
                ]);
                $this->orderItemAmount[$key] = $expectedAmount;
            }
        }

        // Recalculate totals after any amount corrections
        $this->calculateTotal();

        // Phase 1.4: Re-validate stock for all combo packs in the cart before saving
        if ($action !== 'cancel') {
            // Extract integer combo pack IDs from instance keys (e.g. "5_0" -> 5)
            $uniqueComboIds = array_unique(array_map(
                fn ($v) => (int) explode('_', (string) $v)[0],
                array_filter(array_values($this->orderItemComboPack))
            ));
            foreach ($uniqueComboIds as $comboPackId) {
                $comboPack = \App\Models\ComboPack::with(['comboPackItems.menuItem.recipes.inventoryItem'])->find($comboPackId);
                if ($comboPack) {
                    $stockResult = $comboPack->validateStock();
                    if (! $stockResult['valid']) {
                        $this->alert('error', $stockResult['message'], [
                            'toast' => true,
                            'position' => 'top-end',
                        ]);

                        return;
                    }
                }
            }
        }
        switch ($action) {
            case 'bill':
                $successMessage = __('messages.billedSuccess');
                $status = 'billed';
                // Billing closes the table (free it for new guests)
                $tableStatus = 'available';
                break;

            case 'kot':
                $successMessage = __('messages.kotGenerated');
                $status = 'kot';
                $tableStatus = 'running';
                break;

            case 'cancel':
                $successMessage = __('messages.orderCanceled');
                $status = 'canceled';
                $tableStatus = 'available';
                break;
        }

        // Get order type name if not already set
        $orderTypeName = $this->orderType;
        if ($this->orderTypeId) {
            $orderType = OrderType::select('order_type_name')->find($this->orderTypeId);
            $orderTypeName = $orderType->order_type_name ?? $orderTypeName;
        }

        if ((! $this->tableOrderID && ! $this->orderID) || ($this->tableOrderID && ! $this->tableOrder->activeOrder)) {

            $orderNumberData = Order::generateOrderNumber(branch());
            $table = Table::find($this->tableId);
            $reservationId = $table?->activeReservation?->id;

            // Check if there's an active reservation and show confirmation modal
            if ($reservationId && $this->orderType === 'dine_in' && ! $this->isSameCustomer && ! $this->intendedOrderAction) {
                $this->reservationId = $reservationId;
                $this->reservationCustomer = $table->activeReservation->customer;
                $this->reservation = $table->activeReservation;
                $this->showReservationModal = true;
                $this->intendedOrderAction = $action; // Store the intended action

                return;
            }
            $order = Order::create([
                'order_number' => $orderNumberData['order_number'],
                'formatted_order_number' => $orderNumberData['formatted_order_number'],
                'date_time' => now(),
                'table_id' => $this->tableId,
                'number_of_pax' => $this->noOfPax,
                'discount_type' => $this->discountType,
                'discount_value' => $this->discountValue,
                'discount_amount' => $this->discountAmount,
                'reward_point_discount' => $this->rewardPointDiscount > 0 ? $this->rewardPointDiscount : null,
                'reward_points_redeemed' => $this->rewardPointsRedeemed > 0 ? $this->rewardPointsRedeemed : null,
                'waiter_id' => $this->selectWaiter,
                'sub_total' => $this->subTotal,
                'total' => $this->total,
                'order_type' => $this->orderType,
                'order_type_id' => $this->orderTypeId,
                'custom_order_type_name' => $orderTypeName,
                'pickup_date' => $this->orderType === 'pickup' ? $this->deliveryDateTime : null,
                'delivery_executive_id' => ($this->orderType == 'delivery' ? $this->selectDeliveryExecutive : null),
                'delivery_fee' => ($this->orderType == 'delivery' ? $this->deliveryFee : 0),
                'delivery_app_id' => ($this->orderType == 'delivery' ? $this->normalizeDeliveryAppId() : null),
                'status' => $status,
                'order_status' => $this->orderStatus ?? 'confirmed',
                'placed_via' => 'pos',
                'tax_mode' => $this->taxMode,
                'reservation_id' => $this->isSameCustomer ? $this->reservationId : null,
                'customer_id' => $this->isSameCustomer ? $this->reservationCustomer->id : $this->customerId,
            ]);

            if (! empty($this->extraCharges)) {
                $chargesData = collect($this->extraCharges)
                    ->map(fn ($charge) => [
                        'charge_id' => $charge->id,
                    ])->toArray();

                $order->charges()->createMany($chargesData);
            }

            // Reset reservation properties after order creation
            $this->resetReservationProperties();
        } else {

            if ($this->orderID) {
                $this->orderDetail = Order::find($this->orderID);
            }

            $order = ($this->tableOrderID ? $this->tableOrder->activeOrder : $this->orderDetail);
            $order->update([
                'date_time' => now(),
                'order_type' => $this->orderType,
                'order_type_id' => $this->orderTypeId,
                'custom_order_type_name' => $orderTypeName,
                'delivery_executive_id' => ($this->orderType == 'delivery' ? $this->selectDeliveryExecutive : null),
                'number_of_pax' => $this->noOfPax,
                'waiter_id' => $this->selectWaiter,
                'pickup_date' => $this->orderType === 'pickup' ? $this->deliveryDateTime : null,
                'table_id' => $this->tableId ?? $order->table_id,
                'sub_total' => $this->subTotal,
                'total' => $this->total,
                'delivery_fee' => ($this->orderType == 'delivery' ? $this->deliveryFee : 0),
                'delivery_app_id' => ($this->orderType == 'delivery' ? $this->normalizeDeliveryAppId() : null),
                'status' => $status,
                'order_status' => $this->orderStatus ?? 'confirmed',
            ]);
        }

        $this->syncOrderExtras($order);

        if ($status == 'canceled') {
            $order->delete();

            Table::where('id', $this->tableId)->update([
                'available_status' => $tableStatus,
            ]);

            return $this->redirect(route('pos.index'), navigate: true);
        }

        // Handle KOT creation and totals calculation

        $kot = null;
        $kotIds = [];
        if ($status == 'kot') {
            $hasLoadedKotLines = collect(array_keys($this->orderItemList))
                ->contains(fn ($lineKey) => str_starts_with((string) $lineKey, 'kot_') || str_starts_with((string) $lineKey, '"kot_'));
            $hasFullLoadedKotContext = $this->orderID
                ? $this->hasCompleteLoadedKotContext($order)
                : true;
            // New KOT "add-only" sessions must append new lines and never wipe existing rows.
            $appendOnlyKotSave = $this->orderID && (! $hasLoadedKotLines || ! $hasFullLoadedKotContext);

            if (in_array('Kitchen', restaurant_modules()) && in_array('kitchen', custom_module_plugins())) {
                // Group items by kitchen — each item goes to ONE kitchen only
                // For multi-kitchen items, use the primary (first) kitchen
                $groupedItems = [];

                $defaultKotPlaceId = KotPlace::where('branch_id', $order->branch_id)
                    ->where('is_default', true)
                    ->value('id');

                if (! $defaultKotPlaceId) {
                    $defaultKotPlaceId = KotPlace::where('branch_id', $order->branch_id)->value('id');
                }

                foreach ($this->orderItemList as $key => $item) {
                    // Skip items already in an existing KOT (kot_*) or already stored as order_items (order_item_*).
                    // Only NEW items (added in the current session) should go into this new KOT.
                    if ($this->shouldExcludeFromNewKotTicketPayload($key)) {
                        continue;
                    }

                    $menuItem = $this->orderItemVariation[$key]->menuItem ?? $item;

                    // Get the primary kitchen for this item
                    $kitchenIds = $menuItem->getKitchenPlaceIds();
                    $isMultiKitchen = count($kitchenIds) > 1;

                    if (empty($kitchenIds)) {
                        $kotPlaceId = $menuItem->kot_place_id ?? null;
                        if (! $kotPlaceId && $defaultKotPlaceId) {
                            // Persist the fallback so this doesn't break future orders/KOTs.
                            MenuItem::withoutGlobalScopes()
                                ->whereKey($menuItem->id)
                                ->update(['kot_place_id' => $defaultKotPlaceId]);
                            $kotPlaceId = $defaultKotPlaceId;
                        }

                        if ($kotPlaceId) {
                            $kitchenIds = [$kotPlaceId];
                        }

                        if (empty($kitchenIds)) {
                            continue;
                        }
                    }

                    // Use the first (primary) kitchen — item goes to ONE KOT only
                    $primaryKitchenId = $kitchenIds[0];

                    $itemData = [
                        'key' => $key,
                        'menu_item_id' => $menuItem->id,
                        'variation_id' => $this->orderItemVariation[$key]->id ?? null,
                        'quantity' => $this->orderItemQty[$key],
                        'modifiers' => $this->itemModifiersSelected[$key] ?? [],
                        'note' => $this->itemNotes[$key] ?? null,
                        'combo_pack_id' => isset($this->orderItemComboPack[$key]) ? (int) explode('_', (string) $this->orderItemComboPack[$key])[0] : null,
                        'original_price' => $this->orderItemOriginalPrice[$key] ?? null,
                        'combo_discount_amount' => $this->orderItemComboDiscount[$key] ?? null,
                        'is_combo_item' => ! empty($this->orderItemComboPack[$key]),
                        'is_multi_kitchen' => $isMultiKitchen,
                    ];

                    $groupedItems[$primaryKitchenId][] = $itemData;
                }

                foreach ($groupedItems as $kotPlaceId => $items) {
                    $kot = Kot::create([
                        'branch_id' => $order->branch_id,
                        'kot_number' => Kot::generateKotNumber($order->branch),
                        'order_id' => $order->id,
                        'order_type_id' => $order->order_type_id,
                        'token_number' => Kot::generateTokenNumber(branch()->id, $order->order_type_id),
                        'kitchen_place_id' => $kotPlaceId,
                        'note' => $this->orderNote,
                    ]);

                    $kotIds[] = $kot->id;

                    foreach ($items as $item) {
                        $note = $item['note'] ?? '';

                        $kotItem = KotItem::create([
                            'kot_id' => $kot->id,
                            'menu_item_id' => $item['menu_item_id'],
                            'menu_item_variation_id' => $item['variation_id'],
                            'quantity' => $item['quantity'],
                            'note' => $note,
                            'combo_pack_id' => $item['combo_pack_id'] ?? null,
                            'order_type_id' => $order->order_type_id ?? null,
                            'order_type' => $order->order_type ?? null,
                            'is_multi_kitchen' => $item['is_multi_kitchen'],
                        ]);
                        $kotItem->modifierOptions()->sync($this->buildModifierSyncData($item['modifiers'] ?? []));
                    }
                }
            } else {
                // No kitchen module: single KOT for all items
                $kot = Kot::create([
                    'branch_id' => $order->branch_id,
                    'kot_number' => Kot::generateKotNumber($order->branch) + 1,
                    'order_id' => $order->id,
                    'order_type_id' => $order->order_type_id,
                    'token_number' => Kot::generateTokenNumber(branch()->id, $order->order_type_id),
                    'note' => $this->orderNote,
                ]);

                foreach ($this->orderItemList as $key => $value) {
                    // Skip items already on a KOT or already persisted as order_items.
                    if ($this->shouldExcludeFromNewKotTicketPayload($key)) {
                        continue;
                    }

                    $comboInstanceKey = $this->orderItemComboPack[$key] ?? null;
                    $note = $this->withComboInstanceNote($this->itemNotes[$key] ?? null, $comboInstanceKey);

                    $kotItem = KotItem::create([
                        'kot_id' => $kot->id,
                        'menu_item_id' => $this->orderItemVariation[$key]->menu_item_id ?? $value->id,
                        'menu_item_variation_id' => $this->orderItemVariation[$key]->id ?? null,
                        'quantity' => $this->orderItemQty[$key],
                        'note' => $note,
                        'combo_pack_id' => isset($this->orderItemComboPack[$key])
                            ? (int) explode('_', (string) $this->orderItemComboPack[$key])[0]
                            : null,
                        'order_type_id' => $order->order_type_id ?? null,
                        'order_type' => $order->order_type ?? null,
                    ]);
                    $kotItem->modifierOptions()->sync($this->buildModifierSyncData($this->itemModifiersSelected[$key] ?? []));
                }
            }

            // Persist OrderItems immediately so combo pricing survives when order is re-opened before billing.
            // The billing paths will delete and recreate them with full billing data.
            // Skip when immediately chaining to bill – the billing block handles OrderItem creation.
            if ($secondAction !== 'bill') {
                // New KOT can be opened in "add-only" mode where existing KOT lines are not
                // loaded in-memory. In that case, keep previous order_items and append only
                // the newly added lines instead of deleting historical rows.
                if (! $this->orderID || ! $appendOnlyKotSave) {
                    $order->items()->delete();
                }

                foreach ($this->orderItemList as $key => $value) {
                    if ($appendOnlyKotSave && (str_starts_with((string) $key, 'kot_') || str_starts_with((string) $key, '"kot_'))) {
                        continue;
                    }
                    if ($appendOnlyKotSave && $this->isAlreadyPersistedOrderItemCartKey((string) $key)) {
                        continue;
                    }

                    $comboInstanceKey = $this->orderItemComboPack[$key] ?? null;
                    $comboPackIdKot = $comboInstanceKey ? (int) explode('_', (string) $comboInstanceKey)[0] : null;
                    $isComboItemKot = ! empty($comboInstanceKey);
                    $note = $this->withComboInstanceNote($this->itemNotes[$key] ?? null, $comboInstanceKey);

                    if ($this->orderTypeId) {
                        $value->setPriceContext($this->orderTypeId, $this->normalizeDeliveryAppId());
                        if (isset($this->orderItemVariation[$key])) {
                            $this->orderItemVariation[$key]->setPriceContext($this->orderTypeId, $this->normalizeDeliveryAppId());
                        }
                    }

                    $itemPriceKot = $isComboItemKot && $this->orderItemQty[$key] > 0
                        ? $this->orderItemAmount[$key] / $this->orderItemQty[$key]
                        : (isset($this->orderItemVariation[$key]) ? $this->orderItemVariation[$key]->price : $value->price);

                    $kotOrderItem = OrderItem::create([
                        'order_id' => $order->id,
                        'menu_item_id' => isset($this->orderItemVariation[$key]) ? $this->orderItemVariation[$key]->menu_item_id : $value->id,
                        'menu_item_variation_id' => isset($this->orderItemVariation[$key]) ? $this->orderItemVariation[$key]->id : null,
                        'combo_pack_id' => $comboPackIdKot ?: null,
                        'quantity' => $this->orderItemQty[$key],
                        'price' => $itemPriceKot,
                        'original_price' => $this->orderItemOriginalPrice[$key] ?? null,
                        'combo_discount_amount' => $this->orderItemComboDiscount[$key] ?? null,
                        'is_combo_item' => $isComboItemKot,
                        'amount' => $this->orderItemAmount[$key],
                        'note' => $note,
                        'tax_amount' => $this->orderItemTaxDetails[$key]['tax_amount'] ?? null,
                        'tax_percentage' => $this->orderItemTaxDetails[$key]['tax_percent'] ?? null,
                    ]);
                    $kotOrderItem->modifierOptions()->sync($this->buildModifierSyncData($this->itemModifiersSelected[$key] ?? []));
                }
            }

            // Recalculate totals after KOT creation if editing an existing order.
            // Use in-memory arrays (calculated via calculateTotal) — they are always correct
            // regardless of what is in the DB at this point.
            if ($this->orderID) {
                // In add-only New KOT sessions, in-memory arrays contain only newly added lines.
                // Rehydrate from DB KOTs before total update so Orders overview gets full total.
                if ($appendOnlyKotSave) {
                    $this->orderDetail = Order::with([
                        'kot.items.menuItem',
                        'kot.items.menuItemVariation',
                        'kot.items.modifierOptions',
                    ])->find($order->id);

                    if ($this->orderDetail) {
                        $this->setupOrderItems();
                    }
                }

                if (! $appendOnlyKotSave) {
                    $this->calculateTotal();
                }

                Order::where('id', $order->id)->update([
                    'sub_total' => $this->subTotal,
                    'total' => $this->total,
                    'discount_amount' => $this->discountAmount,
                    'total_tax_amount' => $this->totalTaxAmount,
                    'tax_mode' => $this->taxMode,
                ]);
            }

            if ($secondAction == 'bill' && $thirdAction == 'payment') {
                // Update order status to billed
                $order->update([
                    'status' => 'billed',
                ]);

                // Now bill the order
                foreach ($this->orderItemList as $key => $value) {
                    // Set price context before using price
                    if ($this->orderTypeId) {
                        $value->setPriceContext($this->orderTypeId, $this->normalizeDeliveryAppId());
                        if (isset($this->orderItemVariation[$key])) {
                            $this->orderItemVariation[$key]->setPriceContext($this->orderTypeId, $this->normalizeDeliveryAppId());
                        }
                    }

                    // Check if this item is from a combo pack (instance key -> real DB id)
                    $comboInstanceKey = $this->orderItemComboPack[$key] ?? null;
                    $comboPackId = $comboInstanceKey ? (int) explode('_', (string) $comboInstanceKey)[0] : null;
                    $originalPrice = $this->orderItemOriginalPrice[$key] ?? null;
                    $comboDiscountAmount = $this->orderItemComboDiscount[$key] ?? null;
                    $isComboItem = ! empty($comboInstanceKey);
                    $note = $this->withComboInstanceNote($this->itemNotes[$key] ?? null, $comboInstanceKey);

                    // For combo items, calculate per-unit price from orderItemAmount
                    // For regular items, use menu item price
                    $itemPrice = $isComboItem && $this->orderItemQty[$key] > 0
                        ? $this->orderItemAmount[$key] / $this->orderItemQty[$key] // Discounted per-unit price
                        : (isset($this->orderItemVariation[$key]) ? $this->orderItemVariation[$key]->price : $value->price);

                    $orderItem = OrderItem::create([
                        'branch_id' => $order->branch_id,
                        'order_id' => $order->id,
                        'menu_item_id' => (isset($this->orderItemVariation[$key]) ? $this->orderItemVariation[$key]->menu_item_id : $this->orderItemList[$key]->id),
                        'menu_item_variation_id' => (isset($this->orderItemVariation[$key]) ? $this->orderItemVariation[$key]->id : null),
                        'combo_pack_id' => $comboPackId ?: null,
                        'quantity' => $this->orderItemQty[$key],
                        'price' => $itemPrice, // Use discounted per-unit price for combo items
                        'original_price' => $originalPrice,
                        'combo_discount_amount' => $comboDiscountAmount,
                        'is_combo_item' => $isComboItem,
                        'amount' => $this->orderItemAmount[$key],
                        'note' => $note,
                    ]);
                    $this->itemModifiersSelected[$key] = $this->itemModifiersSelected[$key] ?? [];
                    $orderItem->modifierOptions()->sync($this->buildModifierSyncData($this->itemModifiersSelected[$key]));
                }

                if ($this->taxMode === 'order') {
                    foreach ($this->taxes as $key => $value) {
                        OrderTax::create([
                            'order_id' => $order->id,
                            'tax_id' => $value->id,
                        ]);
                    }
                }

                // ... (repeat the billing total calculation logic as in the 'billed' case)
                // Then show the payment modal
                $this->dispatch('showPaymentModal', id: $order->id);

                $this->printKot($order, $kot);
                $this->printOrder($order);
                $this->resetPos();

                return;
            }
        }

        if ($status == 'billed') {

            $order->items()->delete();
            $order->taxes()->delete();

            foreach ($this->orderItemList as $key => $value) {
                // Set price context before using price
                if ($this->orderTypeId) {
                    $value->setPriceContext($this->orderTypeId, $this->normalizeDeliveryAppId());
                    if (isset($this->orderItemVariation[$key])) {
                        $this->orderItemVariation[$key]->setPriceContext($this->orderTypeId, $this->normalizeDeliveryAppId());
                    }
                }

                $taxBreakup = isset($this->orderItemTaxDetails[$key]['tax_breakup']) ? json_encode($this->orderItemTaxDetails[$key]['tax_breakup']) : null;

                // Check if this item is from a combo pack (instance key -> real DB id)
                $comboInstanceKey = $this->orderItemComboPack[$key] ?? null;
                $comboPackId = $comboInstanceKey ? (int) explode('_', (string) $comboInstanceKey)[0] : null;
                $originalPrice = $this->orderItemOriginalPrice[$key] ?? null;
                $comboDiscountAmount = $this->orderItemComboDiscount[$key] ?? null;
                $isComboItem = ! empty($comboInstanceKey);
                $note = $this->withComboInstanceNote($this->itemNotes[$key] ?? null, $comboInstanceKey);

                // For combo items, calculate per-unit price from orderItemAmount
                // For regular items, use menu item price
                $itemPrice = $isComboItem && $this->orderItemQty[$key] > 0
                    ? $this->orderItemAmount[$key] / $this->orderItemQty[$key] // Discounted per-unit price
                    : (isset($this->orderItemVariation[$key]) ? $this->orderItemVariation[$key]->price : $value->price);

                $orderItem = OrderItem::create([
                    'branch_id' => $order->branch_id,
                    'order_type' => $this->orderType,
                    'order_type_id' => $this->orderTypeId,
                    'order_id' => $order->id,
                    'menu_item_id' => (isset($this->orderItemVariation[$key]) ? $this->orderItemVariation[$key]->menu_item_id : $this->orderItemList[$key]->id),
                    'menu_item_variation_id' => (isset($this->orderItemVariation[$key]) ? $this->orderItemVariation[$key]->id : null),
                    'combo_pack_id' => $comboPackId ?: null,
                    'quantity' => $this->orderItemQty[$key],
                    'price' => $itemPrice, // Use discounted per-unit price for combo items
                    'original_price' => $originalPrice,
                    'combo_discount_amount' => $comboDiscountAmount,
                    'is_combo_item' => $isComboItem,
                    'amount' => $this->orderItemAmount[$key],
                    'note' => $note,
                    'tax_amount' => $this->orderItemTaxDetails[$key]['tax_amount'] ?? null,
                    'tax_percentage' => $this->orderItemTaxDetails[$key]['tax_percent'] ?? null,
                    'tax_breakup' => $taxBreakup,
                ]);

                $this->itemModifiersSelected[$key] = $this->itemModifiersSelected[$key] ?? [];
                $orderItem->modifierOptions()->sync($this->buildModifierSyncData($this->itemModifiersSelected[$key]));
            }

            if ($this->taxMode === 'order') {
                foreach ($this->taxes as $key => $value) {
                    OrderTax::create([
                        'order_id' => $order->id,
                        'tax_id' => $value->id,
                    ]);
                }
            }

            $order->load('charges');

            $validCharges = collect($this->extraCharges ?? [])
                ->filter(fn ($charge) => in_array($this->orderTypeSlug, $charge->order_types));

            $currentChargeIds = $order->charges->pluck('charge_id');
            $validChargeIds = $validCharges->pluck('id');

            // Remove invalid charges and add new valid charges
            $order->charges()->whereNotIn('charge_id', $validChargeIds)->delete();

            $validChargeIds->diff($currentChargeIds)->each(
                fn ($chargeId) => OrderCharge::create(['order_id' => $order->id, 'charge_id' => $chargeId])
            );

            $this->total = 0;
            $this->subTotal = 0;

            foreach ($order->load('items')->items as $value) {
                $this->subTotal = ($this->subTotal + $value->amount);
                $this->total = ($this->total + $value->amount);
            }

            // Include custom extras in billed totals (extras are not part of sub_total)
            $stateExtrasTotal = $this->getOrderExtrasTotal();
            $dbExtrasTotal = (float) $order->extras()->sum('amount');
            $extrasTotal = max($stateExtrasTotal, $dbExtrasTotal);
            if ($extrasTotal > 0) {
                $this->total += $extrasTotal;
            }

            $this->discountedTotal = $this->total;

            if ($order->discount_type === 'percent') {
                $this->discountAmount = round(($this->subTotal * $order->discount_value) / 100, 2);
            } elseif ($order->discount_type === 'fixed') {
                $this->discountAmount = min($order->discount_value, $this->subTotal);
            }

            $this->total -= $this->discountAmount;
            $this->discountedTotal = $this->total;
            // Use centralized tax calculation
            $this->recalculateTaxTotals();

            if ($this->taxMode === 'item' && (restaurant()->tax_inclusive ?? false)) {
                $this->subTotal -= $this->totalTaxAmount;
            }

            foreach ($this->extraCharges ?? [] as $value) {
                $this->total += $value->getAmount($this->discountedTotal);
            }

            if ($this->tipAmount > 0) {
                $this->total += $this->tipAmount;
            }

            if ($this->deliveryFee > 0) {
                $this->total += $this->deliveryFee;
            }

            // Apply reward points discount to billed total
            if ($this->rewardPointDiscount > 0) {
                $this->total = max(0, $this->total - $this->rewardPointDiscount);
            }

            Order::where('id', $order->id)->update([
                'sub_total' => $this->subTotal,
                'total' => $this->total,
                'discount_amount' => $this->discountAmount,
                'reward_point_discount' => $this->rewardPointDiscount > 0 ? $this->rewardPointDiscount : null,
                'reward_points_redeemed' => $this->rewardPointsRedeemed > 0 ? $this->rewardPointsRedeemed : null,
                'total_tax_amount' => $this->totalTaxAmount,
                'tax_mode' => $this->taxMode,
            ]);

            // Execute reward points redemption via the service (creates transaction, deducts balance)
            if ($this->rewardPointsRedeemed > 0 && $this->customerId) {
                try {
                    $customer = Customer::find($this->customerId);
                    $freshOrder = Order::find($order->id);
                    if ($customer && $freshOrder) {
                        $rewardService = app(RewardPointsService::class);
                        $rewardService->redeemPoints($freshOrder, $customer, $this->rewardPointsRedeemed);
                    }
                } catch (\Exception $e) {
                    Log::error('Error redeeming reward points at billing: ' . $e->getMessage());
                    // Revert reward fields on failure to maintain consistency
                    Order::where('id', $order->id)->update([
                        'reward_point_discount' => null,
                        'reward_points_redeemed' => null,
                    ]);
                    $this->rewardPointDiscount = 0;
                    $this->rewardPointsRedeemed = 0;
                    // Recalculate total without reward discount
                    $this->calculateTotal();
                }
            }

            if ($order->placed_via == null || $order->placed_via == 'pos') {
                NewOrderCreated::dispatch($order);
            }

            // Do NOT call $this->resetPos() here!
            // The customer display will now show the thank you/payment screen.

            // Update customer display cache to set status to 'billed'
            $this->setCustomerDisplayStatus('billed');
        }

        Table::where('id', $this->tableId)->update([
            'available_status' => $tableStatus,
        ]);

        $this->dispatch('posOrderSuccess');

        $this->alert('success', $successMessage, [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close'),
        ]);

        if ($status == 'kot') {
            if ($secondAction == 'print') {
                // Check if the 'kitchen' package is enabled
                $this->printKot($order, $kot, $kotIds);
            }

            if ($this->orderID) {
                return $this->redirect(route('pos.kot', $order->id).'?show-order-detail=true', navigate: true);
            }

            $this->dispatch('resetPos');
            $this->dispatch('refreshPos');
            // return $this->redirect(route('kots.index'), navigate: true);
        }

        if ($status == 'billed') {
            $billFollowUp = app(BillSecondaryActionResolver::class)->resolve('bill', $secondAction);

            if ($billFollowUp['open_payment']) {
                $this->dispatch('showPaymentModal', id: $order->id);
            }

            if ($billFollowUp['print_receipt']) {
                $orderPlace = \App\Models\MultipleOrder::with('printerSetting')->first();
                $printerSetting = $orderPlace?->printerSetting;

                try {
                    switch ($printerSetting?->printing_choice) {
                        case 'directPrint':
                            $this->handleOrderPrint($order->id);
                            break;
                        default:
                            $url = route('orders.print', $order->id);
                            $this->dispatch('print_location', $url);
                            break;
                    }
                } catch (\Throwable $e) {
                    Log::info($e->getMessage());
                    $this->alert('error', __('messages.printerNotConnected').' '.$e->getMessage(), [
                        'toast' => true,
                        'position' => 'top-end',
                        'showCancelButton' => false,
                        'cancelButtonText' => __('app.close'),
                    ]);
                }
            }

            if ($billFollowUp['show_order_detail']) {
                $this->dispatch('showOrderDetail', id: $order->id, fromPos: true);
            }

            $this->dispatch('resetPos');
            $this->dispatch('refreshPos');
        }

        // Handle default case outside the switch block

    }

    public function printOrder($order)
    {
        // Handle if $order is just an ID instead of an Order object
        if (! is_object($order)) {
            $order = Order::find($order);
            if (! $order) {
                $this->alert('error', __('messages.orderNotFound'), [
                    'toast' => true,
                    'position' => 'top-end',
                    'showCancelButton' => false,
                    'cancelButtonText' => __('app.close'),
                ]);

                return;
            }
        }

        Log::info("printOrder called with Order ID: {$order->id}, Order Number: {$order->order_number}");

        $orderPlace = \App\Models\MultipleOrder::with('printerSetting')->first();

        $printerSetting = $orderPlace->printerSetting;

        switch ($printerSetting?->printing_choice) {

            case 'directPrint':
                $this->handleOrderPrint($order->id);
                break;
            default:
                $url = route('orders.print', $order->id);
                $this->dispatch('print_location', $url);
                break;
        }
    }

    public function printKot($order, $kot = null, $kotIds = [])
    {
        // Check if the 'kitchen' package is enabled
        if (in_array('Kitchen', restaurant_modules()) && in_array('kitchen', custom_module_plugins())) {
            // Get all KOTs for this order (created above)

            if ($kotIds) {
                $kots = $order->kot()->whereIn('id', $kotIds)->with('items')->get();
            } else {
                $kots = $order->kot()->with('items')->get();
            }

            foreach ($kots as $kot) {
                // Each KOT now has kitchen_place_id set directly (multi-kitchen routing)
                $kotPlaceId = $kot->kitchen_place_id;
                if (! $kotPlaceId) {
                    // Fallback for legacy KOTs: derive from first item
                    $firstItem = $kot->items->first();
                    $kotPlaceId = $firstItem?->menuItem?->kot_place_id;
                }

                if (! $kotPlaceId) {
                    continue;
                }

                $kotPlace = KotPlace::with('printerSetting')->find($kotPlaceId);
                if (! $kotPlace) {
                    continue;
                }

                $printerSetting = $kotPlace->printerSetting;

                if ($printerSetting && $printerSetting->is_active == 0) {
                    $printerSetting = Printer::where('is_default', true)->first();
                }

                // If no printer is set, fallback to print URL dispatch
                if (! $printerSetting) {
                    $url = route('kot.print', [$kot->id, $kotPlace?->id]);
                    $this->dispatch('print_location', $url);

                    continue;
                }

                try {
                    switch ($printerSetting->printing_choice) {
                        case 'directPrint':
                            $this->handleKotPrint($kot->id, $kotPlace->id);
                            break;
                        default:
                            $url = route('kot.print', [$kot->id, $kotPlace?->id]);
                            $this->dispatch('print_location', $url);
                            break;
                    }
                } catch (\Throwable $e) {
                    $this->alert('error', __('messages.printerNotConnected').' '.$e->getMessage(), [
                        'toast' => true,
                        'position' => 'top-end',
                        'showCancelButton' => false,
                        'cancelButtonText' => __('app.close'),
                    ]);
                }
            }
        } else {
            $kotPlace = KotPlace::where('is_default', 1)->first();
            $printerSetting = $kotPlace->printerSetting;

            // Get the KOT for this order
            $kot = $kot ?? $order->kot()->first();

            // If no printer is set, fallback to print URL dispatch
            if (! $printerSetting) {
                $url = route('kot.print', [$kot->id, $kotPlace?->id]);
                $this->dispatch('print_location', $url);
            }

            try {
                switch ($printerSetting->printing_choice) {
                    case 'directPrint':
                        $this->handleKotPrint($kot->id, $kotPlace->id);
                        break;

                    default:
                        $url = route('kot.print', [$kot->id]);
                        $this->dispatch('print_location', $url);
                        break;
                }
            } catch (\Throwable $e) {
                $this->alert('error', __('messages.printerNotConnected').' '.$e->getMessage(), [
                    'toast' => true,
                    'position' => 'top-end',
                    'showCancelButton' => false,
                    'cancelButtonText' => __('app.close'),
                ]);
            }
        }
    }

    #[On('resetPos')]
    public function resetPos()
    {
        $this->search = null;
        $this->filterCategories = null;
        $this->menuItem = null;
        $this->subTotal = 0;
        $this->total = 0;
        $this->orderNumber = null;
        $this->formattedOrderNumber = null;
        $this->discountedTotal = 0;
        $this->tipAmount = 0;
        $this->deliveryFee = 0;
        $this->tableNo = null;
        $this->tableId = null;
        $this->noOfPax = null;
        $this->selectWaiter = user()->id;
        $this->orderItemList = [];
        $this->orderItemVariation = [];
        $this->orderItemQty = [];
        $this->orderItemAmount = [];
        $this->orderItemUnitPrice = [];
        $this->orderItemDisplayPrice = [];
        // Prefer restoring last selection from session (New Order should not force Dine In)
        $this->selectedDeliveryApp = null;
        $this->selectedDeliveryPlatformName = null;
        $this->orderTypeName = null;

        // Reset reward points state
        $this->rewardPointDiscount = 0;
        $this->rewardPointsRedeemed = 0;
        $this->rewardPointsAvailable = 0;
        $this->rewardSettings = null;
        $this->rewardDisplayName = 'Reward';

        $sessionOrderTypeId = session()->get('pos.order_type_id');
        $sessionDeliveryAppId = session()->get('pos.delivery_app_id');

        $sessionOrderType = $sessionOrderTypeId
            ? OrderType::where('id', (int) $sessionOrderTypeId)->where('is_active', true)->first()
            : null;

        if ($sessionOrderType) {
            $this->orderTypeId = $sessionOrderType->id;
            $this->orderType = $sessionOrderType->type;
            $this->orderTypeSlug = $sessionOrderType->slug;
            $this->orderTypeName = $sessionOrderType->order_type_name;

            if ($this->orderTypeSlug === 'delivery') {
                $this->selectedDeliveryApp = $sessionDeliveryAppId;
                $this->refreshSelectedDeliveryPlatformName();
            }
        } else {
            // Fallback: keep existing default behavior
            $defaultOrderType = OrderType::where('type', 'dine_in')
                ->where('is_active', true)
                ->first();

            if ($defaultOrderType) {
                $this->orderType = $defaultOrderType->type;
                $this->orderTypeSlug = $defaultOrderType->slug;
                $this->orderTypeId = $defaultOrderType->id;
                $this->orderTypeName = $defaultOrderType->order_type_name;
            } else {
                // If no dine-in exists, fall back to the first active order type.
                $fallbackOrderType = OrderType::where('is_active', true)->orderBy('order_type_name')->first();

                if ($fallbackOrderType) {
                    $this->orderType = $fallbackOrderType->type;
                    $this->orderTypeSlug = $fallbackOrderType->slug;
                    $this->orderTypeId = $fallbackOrderType->id;
                    $this->orderTypeName = $fallbackOrderType->order_type_name;
                } else {
                    // Extremely defensive fallback if no order types exist at all
                    $this->orderType = 'dine_in';
                    $this->orderTypeSlug = 'dine_in';
                }
            }
        }

        $this->discountType = null;
        $this->discountValue = null;
        $this->showDiscountModal = false;
        $this->selectedModifierItem = null;
        $this->modifiers = null;
        $this->itemModifiersSelected = [];
        $this->discountAmount = null;
        $this->orderStatus;
        $this->showNewKotButton = false;
        $this->itemNotes = []; // Reset item notes
        $this->orderItemTaxDetails = [];
        $this->orderItemPersistedTaxOverride = [];
        $this->totalTaxAmount = 0;
        $this->customerDisplayStatus = 'idle'; // Reset customer display status to idle
        // Save empty cart state to cache for customer display
        $taxesForDisplay = [];
        if ($this->taxes) {
            $taxesForDisplay = $this->taxes->map(function ($tax) {
                return [
                    'name' => $tax->tax_name,
                    'percent' => $tax->tax_percent,
                    'amount' => 0,
                ];
            })->toArray();
        }
        $customerDisplayData = [
            'order_number' => $this->orderNumber,
            'formatted_order_number' => $this->formattedOrderNumber,
            'items' => [],
            'sub_total' => 0,
            'discount' => 0,
            'total' => 0,
            'taxes' => $taxesForDisplay,
            'extra_charges' => [],
            'tip' => 0,
            'delivery_fee' => 0,
            'order_type' => $this->orderType,
            'status' => 'idle',
            'cash_due' => null,
        ];

        $userId = auth()->id();
        // $cacheKey = 'customer_display_cart_user_' . $userId;
        // Cache::put($cacheKey, $customerDisplayData, now()->addMinutes(30));

        // Broadcast customer display update if Pusher is enabled
        if (pusherSettings()->is_enabled_pusher_broadcast) {
            broadcast(new \App\Events\CustomerDisplayUpdated($customerDisplayData, $userId));
        }
        // Optionally, still dispatch browser event
        $this->dispatch('orderUpdated', [
            'order_number' => $this->orderNumber,
            'formatted_order_number' => $this->formattedOrderNumber,
            'items' => [],
            'sub_total' => 0,
            'discount' => 0,
            'total' => 0,
        ]);
    }

    protected function promptRemovalReason(string $itemId, string $action, ?int $newQuantity = null): void
    {
        $this->pendingRemovalItem = $itemId;
        $this->pendingRemovalAction = $action;
        $this->pendingRemovalNewQuantity = $newQuantity;
        $this->removalReason = '';
        $this->showRemovalReasonModal = true;
    }

    protected function resetRemovalReasonState(): void
    {
        $this->showRemovalReasonModal = false;
        $this->removalReason = '';
        $this->pendingRemovalItem = null;
        $this->pendingRemovalAction = null;
        $this->pendingRemovalNewQuantity = null;
        $this->pendingRemovalComboItems = [];
    }

    public function cancelRemovalReason(): void
    {
        $this->resetRemovalReasonState();
    }

    public function confirmRemovalReason(): void
    {
        $this->validate([
            'removalReason' => 'required|string|min:3',
        ]);

        if (! $this->pendingRemovalItem || ! $this->pendingRemovalAction) {
            $this->resetRemovalReasonState();

            return;
        }

        if ($this->pendingRemovalAction === 'delete') {
            $this->executeDeleteCartItems($this->pendingRemovalItem, $this->removalReason);
        } elseif ($this->pendingRemovalAction === 'decrement') {
            $this->applyKotQuantityChange(
                $this->pendingRemovalItem,
                $this->pendingRemovalNewQuantity ?? 0,
                $this->removalReason
            );
        } elseif ($this->pendingRemovalAction === 'delete_combo') {
            foreach ((array) $this->pendingRemovalComboItems as $comboKey) {
                $this->executeDeleteCartItems($comboKey, $this->removalReason);
            }
        }

        $this->resetRemovalReasonState();
    }

    protected function requiresRemovalReason($id): bool
    {
        if (! $this->orderID) {
            return false;
        }

        $context = $this->parseKotContext($id);

        return ! is_null($context);
    }

    /**
     * KOT-persisted and saved order_item_* cart lines need a removal reason (same as deleteCartItems).
     */
    protected function shouldBatchRemovalPromptForPersistedLine(string $lineKey): bool
    {
        if (! $this->orderID) {
            return false;
        }

        if ($this->requiresRemovalReason($lineKey)) {
            return true;
        }

        return $this->isAlreadyPersistedOrderItemCartKey($lineKey);
    }

    protected function parseKotContext($id): ?array
    {
        $parts = explode('_', str_replace('"', '', $id));

        if (count($parts) < 3 || $parts[0] !== 'kot') {
            return null;
        }

        return [
            'kot_id' => $parts[1],
            'kot_item_id' => $parts[2],
        ];
    }

    protected function parsePersistedOrderItemContext($id): ?array
    {
        $parts = explode('_', str_replace('"', '', (string) $id));

        if (count($parts) < 3 || $parts[0] !== 'order' || $parts[1] !== 'item') {
            return null;
        }

        $orderItemId = (int) ($parts[2] ?? 0);
        if ($orderItemId <= 0) {
            return null;
        }

        return ['order_item_id' => $orderItemId];
    }

    protected function extractComboInstanceKey(?string $note): ?string
    {
        if (! $note) {
            return null;
        }

        if (preg_match('/\[COMBO_INSTANCE:([^\]]+)\]/', $note, $matches) && ! empty($matches[1])) {
            return trim((string) $matches[1]);
        }

        return null;
    }

    protected function withComboInstanceNote(?string $note, ?string $instanceKey): ?string
    {
        $base = trim((string) preg_replace('/\s*\[COMBO_INSTANCE:[^\]]+\]\s*/', ' ', (string) $note));

        if (empty($instanceKey)) {
            return $base !== '' ? $base : null;
        }

        $merged = trim($base.' [COMBO_INSTANCE:'.$instanceKey.']');

        return $merged !== '' ? $merged : null;
    }

    protected function applyKotQuantityChange(string $itemId, int $newQuantity, ?string $note = null): void
    {
        $context = $this->parseKotContext($itemId);
        if (! $context) {
            return;
        }

        $kotItem = KotItem::with('kot')->find($context['kot_item_id']);

        if (! $kotItem) {
            return;
        }

        $previousQuantity = $kotItem->quantity;

        if ($newQuantity <= 0) {
            $this->executeDeleteCartItems($itemId, $note);

            return;
        }

        $kotItem->update(['quantity' => $newQuantity]);

        if ($note) {
            $this->logKotItemAdjustment($kotItem, 'quantity_updated', $note, $previousQuantity, $newQuantity);
        }

        $this->orderItemQty[$itemId] = $newQuantity;
        $basePrice = $this->orderItemVariation[$itemId]->price ?? $this->orderItemList[$itemId]->price ?? 0;
        $this->orderItemAmount[$itemId] = $newQuantity * ($basePrice + ($this->orderItemModifiersPrice[$itemId] ?? 0));
        $this->calculateTotal();

        if ($this->orderID) {
            Order::where('id', $this->orderID)->update([
                'sub_total' => $this->subTotal,
                'total' => $this->total,
                'discount_amount' => $this->discountAmount,
                'total_tax_amount' => $this->totalTaxAmount,
            ]);
        }
    }

    /**
     * Remove the persisted order_items row that matches this KOT line so billed/paid
     * edits stay consistent when using cart keys (deleteCartItems / removeComboGroup).
     */
    protected function deletePersistedOrderItemForKotLine(KotItem $kotItem): void
    {
        if (! $this->orderDetail instanceof Order) {
            return;
        }

        $query = OrderItem::query()
            ->where('order_id', $this->orderDetail->id)
            ->where('menu_item_id', $kotItem->menu_item_id)
            ->where('quantity', $kotItem->quantity);

        if ($kotItem->menu_item_variation_id) {
            $query->where('menu_item_variation_id', $kotItem->menu_item_variation_id);
        } else {
            $query->whereNull('menu_item_variation_id');
        }

        if ($kotItem->combo_pack_id) {
            $query->where('is_combo_item', true)
                ->where('combo_pack_id', $kotItem->combo_pack_id);
        } else {
            $query->where(function ($q) {
                $q->where('is_combo_item', false)->orWhereNull('is_combo_item');
            })->whereNull('combo_pack_id');
        }

        $orderItem = $query->orderBy('id')->first();

        if (! $orderItem) {
            $orderItem = OrderItem::query()
                ->where('order_id', $this->orderDetail->id)
                ->where('menu_item_id', $kotItem->menu_item_id)
                ->where('quantity', $kotItem->quantity)
                ->where('is_combo_item', true)
                ->whereNotNull('combo_pack_id')
                ->orderBy('id')
                ->first();
        }

        if ($orderItem) {
            $orderItem->modifierOptions()->detach();
            $orderItem->delete();
        }
    }

    protected function executeDeleteCartItems($id, ?string $note = null): void
    {
        if ($this->tableId) {
            $table = Table::find($this->tableId);
            $table?->updateActivity(user()->id);
        }

        unset($this->orderItemList[$id]);
        unset($this->orderItemQty[$id]);
        unset($this->orderItemAmount[$id]);
        unset($this->orderItemVariation[$id]);
        unset($this->itemModifiersSelected[$id]);
        unset($this->itemNotes[$id]);
        unset($this->orderItemModifiersPrice[$id]);
        unset($this->orderItemTaxDetails[$id]);
        unset($this->orderItemComboPack[$id]);
        unset($this->orderItemOriginalPrice[$id]);
        unset($this->orderItemComboDiscount[$id]);
        unset($this->orderItemUnitPrice[$id]);
        unset($this->orderItemDisplayPrice[$id]);

        if (! $this->orderDetail || ! is_object($this->orderDetail)) {
            $this->calculateTotal();

            return;
        }

        $persistedOrderItemContext = $this->parsePersistedOrderItemContext($id);
        if ($persistedOrderItemContext) {
            if (! $this->canModifyCurrentOrderItems()) {
                $this->alert('error', __('messages.noPermission'), ['toast' => true, 'position' => 'top-end']);

                return;
            }

            $orderItem = OrderItem::query()
                ->where('id', $persistedOrderItemContext['order_item_id'])
                ->where('order_id', $this->orderDetail->id)
                ->first();

            if ($orderItem) {
                $orderItem->modifierOptions()->detach();
                $orderItem->delete();
            }

            $this->calculateTotal();

            if ($this->orderID) {
                Order::where('id', $this->orderID)->update([
                    'sub_total' => $this->subTotal,
                    'total' => $this->total,
                    'discount_amount' => $this->discountAmount,
                    'total_tax_amount' => $this->totalTaxAmount,
                ]);
                $this->orderDetail?->refresh();
            }

            return;
        }

        $context = $this->parseKotContext($id);

        if (! $context) {
            $this->calculateTotal();

            return;
        }

        $kotItem = KotItem::with('kot')->where('kot_id', $context['kot_id'])
            ->where('id', $context['kot_item_id'])
            ->first();

        if ($kotItem) {
            if ($note) {
                $this->logKotItemAdjustment($kotItem, 'deleted', $note, $kotItem->quantity, 0);
            }
            $kotItem->modifierOptions()->detach();
            if ($this->orderID && $this->orderDetail instanceof Order) {
                $this->deletePersistedOrderItemForKotLine($kotItem);
            }
            $kotItem->delete();
        }

        if (! empty($this->orderItemList)) {
            $this->calculateTotal();

            if ($this->orderID) {
                Order::where('id', $this->orderID)->update([
                    'sub_total' => $this->subTotal,
                    'total' => $this->total,
                    'discount_amount' => $this->discountAmount,
                    'total_tax_amount' => $this->totalTaxAmount,
                ]);
                $this->orderDetail?->refresh();
            }

            return;
        }

        $kot = Kot::find($context['kot_id']);
        if (! $kot) {
            $this->calculateTotal();

            return;
        }

        $order = $this->orderDetail;
        $kot->delete();

        if (! $order || ! ($order instanceof Order)) {
            $this->calculateTotal();

            return;
        }

        if ($order->table_id) {
            Table::where('id', $order->table_id)->update(['available_status' => 'available']);
        }

        // Check if order has audit logs (adjustments)
        // If yes, we CANCEL it instead of DELETE to preserve the order number sequence and audit trail.
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
                'cancelButtonText' => __('app.close'),
            ]);
        } else {
            $order->delete();

            $this->alert('success', __('messages.orderDeleted'), [
                'toast' => true,
                'position' => 'top-end',
                'showCancelButton' => false,
                'cancelButtonText' => __('app.close'),
            ]);
        }

        $this->orderDetail = null;
        $this->orderID = null;

        $this->redirect(route('pos.index'), navigate: true);
    }

    protected function logKotItemAdjustment(
        KotItem $kotItem,
        string $action,
        string $note,
        int $quantityBefore,
        int $quantityAfter = 0
    ): void {
        $kotItem->loadMissing([
            'menuItem',
            'menuItemVariation.menuItem',
            'kot.order.table',
            'kot.table',
        ]);

        $order = $kotItem->kot?->order;
        $tableCode = $order?->table?->table_code ?? $kotItem->kot?->table?->table_code;
        $menuItemName = $kotItem->menuItem?->item_name
            ?? $kotItem->menuItemVariation?->menuItem?->item_name
            ?? __('messages.menuItemDeleted');
        $variationName = $kotItem->menuItemVariation?->variation;

        KotAdjustmentLogger::log($kotItem, $action, $note, $quantityBefore, $quantityAfter);
    }

    public function showAddDiscount()
    {
        $orderDetail = Order::find($this->orderID);
        $this->discountType = $orderDetail->discount_type ?? $this->discountType ?? 'fixed';
        $this->discountValue = $orderDetail->discount_value ?? $this->discountValue ?? null;
        $this->showDiscountModal = true;
    }

    #[On('closeModifiersModal')]
    public function closeModifiersModal()
    {
        $this->selectedModifierItem = null;
        $this->showModifiersModal = false;
    }

    #[On('setPosModifier')]
    public function setPosModifier($modifierIds)
    {
        // Handle modifier selection from modal
        $this->handleSetPosModifier($modifierIds);
    }

    /**
     * Optimistic qty update - updates UI immediately, syncs to server in background
     * This provides instant feedback for quantity changes without Livewire round-trip delay
     */
    public function optimisticAddQty($id)
    {
        // Check permissions first
        if (($this->orderID && ! user_can('Update Order')) || (! $this->orderID && ! user_can('Create Order'))) {
            return;
        }

        // Reject if combo item
        if ($this->isComboCartLine($id)) {
            $this->alert('error', 'Combo item quantity cannot be edited individually. Edit/remove the whole combo.', ['toast' => true, 'position' => 'top-end']);

            return;
        }

        // Update table activity
        if ($this->tableId) {
            $table = Table::find($this->tableId);
            $table?->updateActivity(user()->id);
        }

        // OPTIMISTIC: Update UI immediately
        $oldQty = $this->orderItemQty[$id] ?? 0;
        $this->orderItemQty[$id] = $oldQty + 1;

        // Update amount for display
        if ($this->orderTypeId) {
            if (isset($this->orderItemVariation[$id])) {
                $this->orderItemVariation[$id]->setPriceContext($this->orderTypeId, $this->normalizeDeliveryAppId());
            }
            if (isset($this->orderItemList[$id])) {
                $this->orderItemList[$id]->setPriceContext($this->orderTypeId, $this->normalizeDeliveryAppId());
            }
        }

        $basePrice = $this->orderItemVariation[$id]->price ?? $this->orderItemList[$id]->price;
        $this->orderItemAmount[$id] = $this->orderItemQty[$id] * ($basePrice + ($this->orderItemModifiersPrice[$id] ?? 0));

        // OPTIMISTIC: Update totals for display
        $this->calculateTotal();

        // Queue debounced server sync
        $this->pendingQtySyncs[$id] = [
            'action' => 'add',
            'qty' => $this->orderItemQty[$id],
            'timestamp' => now()->timestamp,
        ];

        $this->debouncedQtySync();
    }

    /**
     * Optimistic qty dec - updates UI immediately, syncs to server in background
     */
    public function optimisticSubQty($id)
    {
        // Check permissions
        if (($this->orderID && ! user_can('Update Order')) || (! $this->orderID && ! user_can('Create Order'))) {
            return;
        }

        // Reject if combo item
        if ($this->isComboCartLine($id)) {
            $this->alert('error', 'Combo items cannot be removed individually. Remove the whole combo.', ['toast' => true, 'position' => 'top-end']);

            return;
        }

        // Check if removal reason is required (KOT-backed item that was already saved)
        if ($this->requiresRemovalReason($id)) {
            if (! user_can('Delete KOT Item')) {
                $this->alert('error', __('messages.kotDeletePermissionDenied'), [
                    'toast' => true,
                    'position' => 'top-end',
                    'showCancelButton' => false,
                    'cancelButtonText' => __('app.close'),
                ]);

                return;
            }

            // For saved items, use normal flow since it requires modal
            $this->subQty($id);

            return;
        }

        // Update table activity
        if ($this->tableId) {
            $table = Table::find($this->tableId);
            $table?->updateActivity(user()->id);
        }

        // OPTIMISTIC: Update UI immediately
        $oldQty = $this->orderItemQty[$id] ?? 1;
        $newQty = max(0, $oldQty - 1);

        if ($newQty <= 0) {
            // Item completely removed
            $comboInstanceKey = $this->orderItemComboPack[$id] ?? null;

            unset($this->orderItemQty[$id]);
            unset($this->orderItemAmount[$id]);
            unset($this->orderItemList[$id]);
            unset($this->orderItemVariation[$id]);
            unset($this->itemModifiersSelected[$id]);
            unset($this->orderItemModifiersPrice[$id]);
            unset($this->orderItemTaxDetails[$id]);
            unset($this->itemNotes[$id]);
            unset($this->orderItemComboPack[$id]);
            unset($this->orderItemComboDiscount[$id]);
            unset($this->orderItemUnitPrice[$id]);
            unset($this->orderItemDisplayPrice[$id]);
            unset($this->orderItemOriginalPrice[$id]);
            unset($this->orderItemPersistedTaxOverride[$id]);

            if ($comboInstanceKey) {
                unset($this->orderItemComboName[$comboInstanceKey]);
            }
        } else {
            // Qty decreased
            $this->orderItemQty[$id] = $newQty;

            // Update amount for display
            if ($this->orderTypeId) {
                if (isset($this->orderItemVariation[$id])) {
                    $this->orderItemVariation[$id]->setPriceContext($this->orderTypeId, $this->normalizeDeliveryAppId());
                }
                if (isset($this->orderItemList[$id])) {
                    $this->orderItemList[$id]->setPriceContext($this->orderTypeId, $this->normalizeDeliveryAppId());
                }
            }

            $basePrice = $this->orderItemVariation[$id]->price ?? $this->orderItemList[$id]->price;
            $this->orderItemAmount[$id] = $this->orderItemQty[$id] * ($basePrice + ($this->orderItemModifiersPrice[$id] ?? 0));
        }

        // OPTIMISTIC: Update totals for display
        $this->calculateTotal();

        // Queue debounced server sync
        $this->pendingQtySyncs[$id] = [
            'action' => $newQty <= 0 ? 'delete' : 'sub',
            'qty' => $newQty,
            'timestamp' => now()->timestamp,
        ];

        $this->debouncedQtySync();
    }

    /**
     * Debounced queue for syncing pending qty changes to server
     * Collects multiple qty updates within 1 second, then syncs as batch
     */
    public function debouncedQtySync()
    {
        // Clear existing debounce timer if any
        if ($this->qtyDebounceTimer) {
            // Timer would be client-side in JS, but for now just track
        }

        // Set debounce delay (1 second) - this triggers the actual sync
        // In real implementation with JS, this prevents multiple round-trips
        $this->dispatch('scheduleQtySync', ['delay' => 1000]);
    }

    /**
     * Execute queued qty syncs to the server
     * Called after debounce period expires
     */
    public function syncPendingQtys()
    {
        if (empty($this->pendingQtySyncs)) {
            return;
        }

        try {
            $syncStartedAt = microtime(true);
            $batchResult = app(PosBatchSyncService::class)->apply($this->exportBatchSyncState(), $this->pendingQtySyncs);
            $this->applyBatchSyncState($batchResult['state'] ?? []);

            // Clear pending syncs
            $this->pendingQtySyncs = [];

            // Final recalc to ensure server and UI are in sync
            $this->calculateTotal();

            Log::info('POS batch qty sync completed', [
                'restaurant_id' => restaurant()->id ?? null,
                'branch_id' => branch()->id ?? null,
                'applied' => $batchResult['applied'] ?? 0,
                'removed' => $batchResult['removed_ids'] ?? [],
                'ms' => (int) round((microtime(true) - $syncStartedAt) * 1000),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to sync pending qty changes: '.$e->getMessage());
            $this->reloadAuthoritativeOrderState();
            $this->alert('error', 'Failed to sync quantity changes. Please refresh and try again.', [
                'toast' => true,
                'position' => 'top-end',
            ]);
        }
    }

    protected function reloadAuthoritativeOrderState(): void
    {
        $this->pendingQtySyncs = [];
        $this->qtyDebounceTimer = null;

        $order = null;

        if ($this->orderDetail instanceof Order) {
            $order = $this->orderDetail->refresh()->load([
                'kot.items.menuItem',
                'kot.items.menuItemVariation',
                'kot.items.modifierOptions',
                'items.menuItem',
                'items.menuItemVariation',
                'items.modifierOptions',
                'table',
            ]);
        } elseif ($this->orderID) {
            $order = Order::with([
                'kot.items.menuItem',
                'kot.items.menuItemVariation',
                'kot.items.modifierOptions',
                'items.menuItem',
                'items.menuItemVariation',
                'items.modifierOptions',
                'table',
            ])->find($this->orderID);
        }

        if ($order) {
            $this->orderDetail = $order;
            $this->setupOrderItems();
            $this->calculateTotal();
        }
    }

    protected function exportBatchSyncState(): array
    {
        return [
            'orderItemQty' => $this->orderItemQty,
            'orderItemAmount' => $this->orderItemAmount,
            'orderItemList' => $this->orderItemList,
            'orderItemVariation' => $this->orderItemVariation,
            'itemModifiersSelected' => $this->itemModifiersSelected,
            'orderItemModifiersPrice' => $this->orderItemModifiersPrice,
            'orderItemTaxDetails' => $this->orderItemTaxDetails,
            'itemNotes' => $this->itemNotes,
            'orderItemComboPack' => $this->orderItemComboPack,
            'orderItemComboDiscount' => $this->orderItemComboDiscount,
            'orderItemUnitPrice' => $this->orderItemUnitPrice,
            'orderItemDisplayPrice' => $this->orderItemDisplayPrice,
            'orderItemOriginalPrice' => $this->orderItemOriginalPrice,
            'orderItemPersistedTaxOverride' => $this->orderItemPersistedTaxOverride,
            'orderItemComboName' => $this->orderItemComboName,
        ];
    }

    protected function applyBatchSyncState(array $state): void
    {
        foreach (array_keys($this->exportBatchSyncState()) as $key) {
            if (array_key_exists($key, $state)) {
                $this->{$key} = $state[$key];
            }
        }
    }

    // Continuation of setPosModifier method body
    protected function handleSetPosModifier($modifierIds)
    {
        $this->showModifiersModal = false;

        $selection = is_array($modifierIds) ? (reset($modifierIds) ?: []) : [];
        $modifierQtyMap = $this->normalizeModifierQuantities(is_array($selection) ? $selection : []);

        $signatureParts = [];
        foreach ($modifierQtyMap as $modifierOptionId => $qty) {
            $signatureParts[] = $modifierOptionId.':'.$qty;
        }
        $signature = implode('|', $signatureParts);
        $sortNumber = $signature ? md5($signature) : '0';

        $keyId = $this->selectedModifierItem.'-'.$sortNumber;
        if (isset(explode('_', $this->selectedModifierItem)[1])) {
            $menuItemVariation = MenuItemVariation::find(explode('_', $this->selectedModifierItem)[1]);

            // Set price context BEFORE storing to prevent price flickering
            if ($this->orderTypeId) {
                $menuItemVariation->setPriceContext($this->orderTypeId, $this->normalizeDeliveryAppId());
            }

            $this->orderItemVariation[$keyId] = $menuItemVariation;
            $this->selectedModifierItem = explode('_', $this->selectedModifierItem)[0];

            // Set price context on menu item
            if ($this->orderTypeId && isset($this->orderItemList[$keyId])) {
                $this->orderItemList[$keyId]->setPriceContext($this->orderTypeId, $this->normalizeDeliveryAppId());
            }

            $this->orderItemAmount[$keyId] = 1 * ($this->orderItemVariation[$keyId]->price ?? $this->orderItemList[$keyId]->price);
        }

        $this->itemModifiersSelected[$keyId] = $modifierQtyMap;
        $this->orderItemQty[$this->selectedModifierItem] = isset($this->orderItemQty[$this->selectedModifierItem]) ? ($this->orderItemQty[$this->selectedModifierItem] + 1) : 1;

        // Get modifier options with price context set
        $modifierOptions = $this->getModifierOptionsProperty();
        $this->orderItemModifiersPrice[$keyId] = $this->calculateModifierTotal($modifierQtyMap, $modifierOptions);

        $this->syncCart($keyId);
    }

    public function getModifierOptionsProperty()
    {
        $modifiers = ModifierOption::whereIn('id', $this->getSelectedModifierOptionIds())->get();

        // Set price context on modifier options
        if ($this->orderTypeId) {
            foreach ($modifiers as $modifier) {
                $modifier->setPriceContext($this->orderTypeId, $this->normalizeDeliveryAppId());
            }
        }

        return $modifiers->keyBy('id');
    }

    public function saveDeliveryExecutive()
    {
        $this->orderDetail->update(['delivery_executive_id' => $this->selectDeliveryExecutive]);
        $this->orderDetail->refresh();
        $this->alert('success', __('messages.deliveryExecutiveAssigned'), [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close'),
        ]);
    }

    public function cancelOrder()
    {
        if (! user_can('Delete Order')) {
            $this->alert('error', __('messages.noPermission'), ['toast' => true, 'position' => 'top-end']);

            return;
        }

        if (! $this->cancelReason && ! $this->cancelReasonText) {
            $this->alert('error', __('modules.settings.cancelReasonRequired'), [
                'toast' => true,
                'position' => 'top-end',
                'showCancelButton' => false,
                'cancelButtonText' => __('app.close'),
            ]);

            return;
        }

        if ($this->orderID) {
            $order = Order::find($this->orderID);

            if ($order) {

                $order->update([
                    'status' => 'canceled',
                    'order_status' => 'cancelled',
                    'cancel_reason_id' => $this->cancelReason,
                    'cancel_reason_text' => $this->cancelReasonText ?? null,
                ]);

                Table::where('id', $order->table_id)->update([
                    'available_status' => 'available',
                ]);

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

    public function updatedSelectWaiter($value)
    {

        if ($this->orderID) {
            $order = Order::find($this->orderID);

            if ($order) {
                $order->update(['waiter_id' => intval($value)]);
                $this->alert('success', __('messages.waiterUpdated'), [
                    'toast' => true,
                    'position' => 'top-end',
                    'showCancelButton' => false,
                    'cancelButtonText' => __('app.close'),
                ]);
            } else {
                $this->selectWaiter = $order->waiter_id;
            }
        }
    }

    public function closeErrorModal()
    {
        $this->showErrorModal = false;
        $this->showNewKotButton = false;
    }

    public function render()
    {
        // Only generate order number if there is no existing order or table order without active order
        if ((! $this->orderID && ! $this->tableOrderID) || ($this->tableOrderID && ! $this->tableOrder->activeOrder)) {
            $orderNumberData = Order::generateOrderNumber(branch());
            $this->orderNumber = $orderNumberData['order_number'];
            $this->formattedOrderNumber = $orderNumberData['formatted_order_number'];
        }

        $query = MenuItem::withCount('variations', 'modifierGroups');

        if (! empty($this->filterCategories)) {
            $query = $query->where('item_category_id', $this->filterCategories);
        }

        if (! empty($this->menuId)) {
            $query = $query->where('menu_id', $this->menuId);
        }

        $query = $query->search('item_name', $this->search)->get();

        // Set price context on all menu items
        if ($this->orderTypeId) {
            foreach ($query as $menuItem) {
                $menuItem->setPriceContext($this->orderTypeId, $this->normalizeDeliveryAppId());
            }
        }

        // Load combo packs
        $branch = branch();
        $comboPacks = collect([]);
        if ($branch) {
            $comboPacks = \App\Models\ComboPack::where('branch_id', $branch->id)
                ->where('is_active', true)
                ->with(['comboPackItems.menuItem', 'comboPackItems.menuItemVariation'])
                ->orderBy('sort_order', 'asc')
                ->orderBy('id', 'asc')
                ->get()
                ->filter(function ($combo) {
                    // Filter out combos that don't have items or aren't available
                    return $combo->comboPackItems->isNotEmpty() && $combo->isAvailable();
                });
        }

        $showCustomOrderTypes = restaurant()->show_order_type_options;
        $orderTypes = OrderType::where('branch_id', branch()->id)
            ->where('is_active', true)
            ->when(! $showCustomOrderTypes, fn ($q) => $q->where('is_default', true))
            ->get();

        return view('livewire.pos.pos', [
            'menuItems' => $query,
            'comboPacks' => $comboPacks,
            'orderTypes' => $orderTypes,
        ]);
    }

    // Update item notes and save to database if applicable
    public function updateItemNote($itemId, $note)
    {
        $this->itemNotes[$itemId] = $note;

        if (! $this->orderDetail) {
            return;
        }

        // Extract the KOT ID and item ID from the itemId string
        $parts = explode('_', str_replace('"', '', $itemId));

        if (count($parts) < 3 || $parts[0] !== 'kot') {
            return;
        }

        KotItem::where('kot_id', $parts[1])
            ->where('id', $parts[2])
            ->update(['note' => $note]);
    }

    public function updateOrderItemTaxDetails()
    {
        $this->orderItemTaxDetails = [];

        if ($this->taxMode !== 'item' || ! is_array($this->orderItemAmount)) {
            return;
        }

        foreach ($this->orderItemAmount as $key => $value) {
            $menuItem = isset($this->orderItemVariation[$key]) ? $this->orderItemVariation[$key]->menuItem : $this->orderItemList[$key];

            // Set price context before using price
            if ($this->orderTypeId) {
                $menuItem->setPriceContext($this->orderTypeId, $this->normalizeDeliveryAppId());
                if (isset($this->orderItemVariation[$key])) {
                    $this->orderItemVariation[$key]->setPriceContext($this->orderTypeId, $this->normalizeDeliveryAppId());
                }
            }

            $qty = $this->orderItemQty[$key] ?? 1;
            $basePrice = isset($this->orderItemVariation[$key]) ? $this->orderItemVariation[$key]->price : $menuItem->price;
            $modifierPrice = $this->orderItemModifiersPrice[$key] ?? 0;
            $itemPriceWithModifiers = $basePrice + $modifierPrice;
            $taxes = $menuItem->taxes ?? collect();
            $isInclusive = restaurant()->tax_inclusive;
            $taxResult = MenuItem::calculateItemTaxes($itemPriceWithModifiers, $taxes, $isInclusive);
            $this->orderItemTaxDetails[$key] = [
                'tax_amount' => $taxResult['tax_amount'] * $qty,
                'tax_percent' => $taxResult['tax_percentage'],
                'tax_breakup' => $taxResult['tax_breakdown'],
                'tax_type' => $taxResult['inclusive'],
                'base_price' => $itemPriceWithModifiers,
                'display_price' => $isInclusive ? ($itemPriceWithModifiers - ($taxResult['tax_amount'] ?? 0)) : $itemPriceWithModifiers,
                'qty' => $qty,
            ];

            if (isset($this->orderItemPersistedTaxOverride[$key])) {
                $ov = $this->orderItemPersistedTaxOverride[$key];
                $this->orderItemTaxDetails[$key]['tax_amount'] = $ov['tax_amount'];
                if ($ov['tax_percentage'] !== null) {
                    $this->orderItemTaxDetails[$key]['tax_percent'] = $ov['tax_percentage'];
                }
                $breakup = $ov['tax_breakup'];
                if ($breakup !== null && $breakup !== '') {
                    $this->orderItemTaxDetails[$key]['tax_breakup'] = is_array($breakup)
                        ? $breakup
                        : json_decode((string) $breakup, true);
                }
            }
        }
    }

    /**
     * Get the display price for an item (base price without tax for inclusive items)
     */
    public function getItemDisplayPrice($key)
    {
        // KOT lines and rehydrated persisted order_item_* combo lines use stored combo amounts
        $isKotComboKey = (str_starts_with($key, 'kot_') || str_starts_with($key, '"kot_')) && isset($this->orderItemComboPack[$key]);
        $isPersistedOiComboKey = str_starts_with($key, 'order_item_') && isset($this->orderItemComboPack[$key]);
        if (($isKotComboKey || $isPersistedOiComboKey)) {
            // This is a combo item from KOT, calculate per-unit price
            if (isset($this->orderItemOriginalPrice[$key]) && isset($this->orderItemQty[$key]) && $this->orderItemQty[$key] > 0) {
                // Calculate unit price: (original - discount) / quantity
                $totalDiscountedPrice = $this->orderItemOriginalPrice[$key] - ($this->orderItemComboDiscount[$key] ?? 0);

                return $totalDiscountedPrice / $this->orderItemQty[$key];
            }
            // Fallback: use amount / quantity if available
            if (isset($this->orderItemAmount[$key]) && isset($this->orderItemQty[$key]) && $this->orderItemQty[$key] > 0) {
                return $this->orderItemAmount[$key] / $this->orderItemQty[$key];
            }
        }

        // Rehydrated persisted order_item_* rows should keep their original saved display price.
        if (str_starts_with((string) $key, 'order_item_')) {
            if (isset($this->orderItemDisplayPrice[$key])) {
                return (float) $this->orderItemDisplayPrice[$key];
            }
            if (isset($this->orderItemUnitPrice[$key])) {
                return (float) $this->orderItemUnitPrice[$key];
            }
        }

        // For saved order items (viewing order detail), use the saved price from database
        // The key format for saved orders is typically numeric (item index) or the item ID
        if ($this->orderDetail && is_numeric($key)) {
            // Try to find the order item by index
            $items = $this->orderDetail->items->values();
            if (isset($items[$key])) {
                $orderItem = $items[$key];
                // For combo items, show the discounted price (which is the saved price)
                // The original_price is stored separately for reference
                if ($orderItem->is_combo_item && $orderItem->original_price) {
                    // Return the discounted price (price field) which is what was charged
                    return $orderItem->price;
                }

                // For non-combo items, return the saved price
                return $orderItem->price;
            }
        }

        if ($this->taxMode === 'item' && isset($this->orderItemTaxDetails[$key])) {
            return $this->orderItemTaxDetails[$key]['display_price'] ?? 0;
        }

        // Check if we have session data arrays (for active POS session)
        if (isset($this->orderItemList[$key])) {
            // Check if this is a combo item and we have original price stored
            if (str_starts_with($key, 'combo_') && isset($this->orderItemOriginalPrice[$key])) {
                // For combo items in active session, return the discounted price
                return ($this->orderItemOriginalPrice[$key] - ($this->orderItemComboDiscount[$key] ?? 0)) / ($this->orderItemQty[$key] ?? 1);
            }

            // Set price context before using price
            if ($this->orderTypeId) {
                $this->orderItemList[$key]->setPriceContext($this->orderTypeId, $this->normalizeDeliveryAppId());
                if (isset($this->orderItemVariation[$key])) {
                    $this->orderItemVariation[$key]->setPriceContext($this->orderTypeId, $this->normalizeDeliveryAppId());
                }
            }

            $basePrice = isset($this->orderItemVariation[$key]) ? $this->orderItemVariation[$key]->price : $this->orderItemList[$key]->price;
            $modifierPrice = $this->orderItemModifiersPrice[$key] ?? 0;

            return $basePrice + $modifierPrice;
        }

        // For existing order items (when viewing order details), calculate from the order item itself
        if ($this->orderDetail && isset($this->orderDetail->items[$key])) {
            $orderItem = $this->orderDetail->items[$key];

            // Set price context on menu items and variations
            if ($this->orderTypeId) {
                $orderItem->menuItem->setPriceContext($this->orderTypeId, $this->normalizeDeliveryAppId());
                if ($orderItem->menuItemVariation) {
                    $orderItem->menuItemVariation->setPriceContext($this->orderTypeId, $this->normalizeDeliveryAppId());
                }
                // Set price context on modifier options
                foreach ($orderItem->modifierOptions as $modifierOption) {
                    $modifierOption->setPriceContext($this->orderTypeId, $this->normalizeDeliveryAppId());
                }
            }

            $basePrice = ! is_null($orderItem->menuItemVariation) ? $orderItem->menuItemVariation->price : $orderItem->menuItem->price;
            $modifierPrice = $orderItem->modifierOptions->sum(function ($modifier) {
                $qty = (int) ($modifier->pivot->quantity ?? 1);

                return $modifier->price * max(1, $qty);
            });

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

    // Add a helper to format items for customer display
    private function getCustomerDisplayItems()
    {
        $items = [];
        $selectedModifiersByItem = [];
        $modifierIds = [];

        foreach ($this->orderItemList as $key => $item) {
            $selected = [];
            if (! empty($this->itemModifiersSelected[$key])) {
                $selected = $this->normalizeModifierQuantities($this->itemModifiersSelected[$key]);
                $modifierIds = array_merge($modifierIds, array_keys($selected));
            }

            $selectedModifiersByItem[$key] = $selected;
        }

        $modifierOptions = collect();
        if (! empty($modifierIds)) {
            $modifierIds = array_values(array_unique(array_map('intval', $modifierIds)));
            $modifierOptions = \App\Models\ModifierOption::whereIn('id', $modifierIds)->get()->keyBy('id');

            if ($this->orderTypeId) {
                foreach ($modifierOptions as $modifierOption) {
                    $modifierOption->setPriceContext($this->orderTypeId, $this->normalizeDeliveryAppId());
                }
            }
        }

        foreach ($this->orderItemList as $key => $item) {
            // Set price context before using prices
            if ($this->orderTypeId) {
                $item->setPriceContext($this->orderTypeId, $this->normalizeDeliveryAppId());
                if (isset($this->orderItemVariation[$key])) {
                    $this->orderItemVariation[$key]->setPriceContext($this->orderTypeId, $this->normalizeDeliveryAppId());
                }
            }

            $variation = $this->orderItemVariation[$key] ?? null;
            $basePrice = $variation->price ?? $item->price ?? 0;
            $modifiers = [];
            $modifierTotal = 0;
            $selected = $selectedModifiersByItem[$key] ?? [];
            if (! empty($selected)) {
                foreach ($selected as $modifierId => $qty) {
                    $modifier = $modifierOptions->get((int) $modifierId);
                    if (! $modifier) {
                        continue;
                    }

                    $modifiers[] = [
                        'name' => $modifier->name,
                        'price' => $modifier->price,
                        'quantity' => (int) $qty,
                    ];
                    $modifierTotal += ($modifier->price * (int) $qty);
                }
            }
            $totalUnitPrice = $basePrice + $modifierTotal;
            $items[] = [
                'name' => $item->item_name ?? ($item['name'] ?? 'Item'),
                'qty' => $this->orderItemQty[$key] ?? 1,
                'price' => $basePrice, // keep for reference
                'total_unit_price' => $totalUnitPrice, // <-- add this
                'variation' => $variation ? [
                    'name' => $variation->variation ?? null,
                    'price' => $variation->price ?? null,
                ] : null,
                'modifiers' => $modifiers,
                'notes' => $this->itemNotes[$key] ?? null,
            ];
        }

        return $items;
    }

    public function newOrder()
    {
        $this->resetPos();

        // Set the default order type after reset
        $defaultOrderType = OrderType::where('branch_id', branch()->id)
            ->where('is_active', true)
            ->first();

        if ($defaultOrderType) {
            $this->orderTypeId = $defaultOrderType->id;
            $this->orderType = $defaultOrderType->type;
            $this->orderTypeSlug = $defaultOrderType->slug;
        }

        $this->setCustomerDisplayStatus('idle');
        $this->calculateTotal();
    }

    public function updateQty($id)
    {
        if (($this->orderID && ! user_can('Update Order')) || (! $this->orderID && ! user_can('Create Order'))) {
            return;
        }

        if ($this->isComboCartLine($id)) {
            // Do not allow manual per-line quantity edits for combo members.
            // Keep current qty as-is and guide user to remove/edit whole combo.
            $context = $this->parseKotContext($id);
            if ($context) {
                $kotItem = KotItem::find($context['kot_item_id']);
                if ($kotItem) {
                    $this->orderItemQty[$id] = (int) $kotItem->quantity;
                }
            }

            $this->alert('error', 'Combo item quantity cannot be edited individually. Edit/remove the whole combo.', ['toast' => true, 'position' => 'top-end']);

            return;
        }
        // Ensure quantity is at least 1
        $this->orderItemQty[$id] = max(1, intval($this->orderItemQty[$id]));

        // Set price context before using price
        if ($this->orderTypeId) {
            if (isset($this->orderItemVariation[$id])) {
                $this->orderItemVariation[$id]->setPriceContext($this->orderTypeId, $this->normalizeDeliveryAppId());
            }
            if (isset($this->orderItemList[$id])) {
                $this->orderItemList[$id]->setPriceContext($this->orderTypeId, $this->normalizeDeliveryAppId());
            }
        }

        // Update the amount based on the new quantity
        $basePrice = $this->orderItemVariation[$id]->price ?? $this->orderItemList[$id]->price;
        $this->orderItemAmount[$id] = $this->orderItemQty[$id] * ($basePrice + ($this->orderItemModifiersPrice[$id] ?? 0));

        // Recalculate the total
        $this->calculateTotal();
    }

    protected function isComboCartLine($id): bool
    {
        return ! empty($this->orderItemComboPack[$id] ?? null);
    }

    protected function canModifyCurrentOrderItems(): bool
    {
        if (! $this->orderID) {
            return true;
        }

        $orderStatus = $this->orderDetail?->status;
        if (! $orderStatus) {
            $orderStatus = Order::where('id', $this->orderID)->value('status');
        }

        $isBilledOrPaid = in_array($orderStatus, ['billed', 'paid', 'payment_due'], true);

        if ($isBilledOrPaid) {
            return user_can('Edit Billed Order');
        }

        return user_can('Delete Order') || user_can('Update Order');
    }

    /**
     * True only when current in-memory cart has all persisted KOT-backed line keys.
     * Used to decide whether saveOrder('kot') can safely replace order_items rows.
     */
    protected function hasCompleteLoadedKotContext(Order $order): bool
    {
        $expectedKotItemCount = KotItem::query()
            ->whereHas('kot', fn ($q) => $q->where('order_id', $order->id))
            ->count();

        if ($expectedKotItemCount === 0) {
            return false;
        }

        $loadedKotKeysCount = collect(array_keys($this->orderItemList))
            ->filter(fn ($lineKey) => str_starts_with((string) $lineKey, 'kot_') || str_starts_with((string) $lineKey, '"kot_'))
            ->count();

        return $loadedKotKeysCount >= $expectedKotItemCount;
    }

    /**
     * Set the customer display status and immediately update the cache.
     */
    public function setCustomerDisplayStatus($status)
    {
        $this->customerDisplayStatus = $status;
        $this->calculateTotal();
    }

    /**
     * Confirm that the customer is the same as the reservation
     */
    public function confirmSameCustomer()
    {
        $this->isSameCustomer = true;
        $this->showReservationModal = false;
        $this->saveOrder($this->intendedOrderAction ?? 'kot');
    }

    /**
     * Confirm that the customer is different from the reservation
     */
    public function confirmDifferentCustomer()
    {
        $this->isSameCustomer = false;
        $this->showReservationModal = false;
        $this->saveOrder($this->intendedOrderAction ?? 'kot');
    }

    /**
     * Close the reservation modal
     */
    public function closeReservationModal()
    {
        $this->showReservationModal = false;
        $this->reservationId = null;
        $this->reservationCustomer = null;
        $this->reservation = null;
        $this->isSameCustomer = false;
        $this->intendedOrderAction = null;
    }

    /**
     * Reset reservation properties
     */
    public function resetReservationProperties()
    {
        $this->reservationId = null;
        $this->reservationCustomer = null;
        $this->reservation = null;
        $this->isSameCustomer = false;
        $this->intendedOrderAction = null;
    }
}
