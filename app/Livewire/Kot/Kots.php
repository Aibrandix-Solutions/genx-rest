<?php

namespace App\Livewire\Kot;

use Carbon\Carbon;
use App\Models\Kot;
use Livewire\Component;
use App\Models\KotSetting;
use Livewire\Attributes\On;
use App\Models\KotCancelReason;
use App\Models\KotPlace;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class Kots extends Component
{
    use LivewireAlert;

    protected $listeners = [
        'refreshKots' => '$refresh',
        'playFoodReadySound' => 'notifyFoodReady',
    ];
    public $filterOrders;
    public $dateRangeType;
    public $startDate;
    public $endDate;
    public $kotSettings;
    public $confirmDeleteKotModal = false;
    public $cancelReasons;
    public $kot;
    public $cancelReasonText;
    public $cancelReason;
    public $selectedCancelKotId;
    public $kotPlace;
    public $showAllKitchens = false;
    public $selectedKitchen = '';
    public $search = '';

    public function mount($kotPlace = null, $showAllKitchens = false)
    {
        // Load date range type from cookie
        $this->kotSettings = KotSetting::first();
        $this->dateRangeType = request()->cookie('kots_date_range_type', 'today');
        $this->filterOrders = ($this->kotSettings->default_status == 'pending') ? 'pending_confirmation' : 'in_kitchen';
        $this->startDate = now()->startOfWeek()->format('m/d/Y');
        $this->endDate = now()->endOfWeek()->format('m/d/Y');
        $this->cancelReasons = KotCancelReason::where('cancel_kot', true)->get();
        $this->showAllKitchens = $showAllKitchens;

        if ($this->showAllKitchens) {
            // For all kitchens view, don't set a specific kotPlace
            $this->kotPlace = null;
        } elseif (!in_array('Kitchen', restaurant_modules())) {
            $this->kotPlace = KotPlace::with('printerSetting')->first();
        } else {
            $this->kotPlace = $kotPlace;
        }

        $this->setDateRange();
    }

    public function setDateRange()
    {
        switch ($this->dateRangeType) {
            case 'today':
                $this->startDate = now()->startOfDay()->format('m/d/Y');
                $this->endDate = now()->startOfDay()->format('m/d/Y');
                break;

            case 'yesterday':
                $this->startDate = now()->subDay()->startOfDay()->format('m/d/Y');
                $this->endDate = now()->subDay()->startOfDay()->format('m/d/Y');
                break;

            case 'lastWeek':
                $this->startDate = now()->subWeek()->startOfWeek()->format('m/d/Y');
                $this->endDate = now()->subWeek()->endOfWeek()->format('m/d/Y');
                break;

            case 'last7Days':
                $this->startDate = now()->subDays(7)->format('m/d/Y');
                $this->endDate = now()->startOfDay()->format('m/d/Y');
                break;

            case 'currentMonth':
                $this->startDate = now()->startOfMonth()->format('m/d/Y');
                $this->endDate = now()->startOfDay()->format('m/d/Y');
                break;

            case 'lastMonth':
                $this->startDate = now()->subMonth()->startOfMonth()->format('m/d/Y');
                $this->endDate = now()->subMonth()->endOfMonth()->format('m/d/Y');
                break;

            case 'currentYear':
                $this->startDate = now()->startOfYear()->format('m/d/Y');
                $this->endDate = now()->startOfDay()->format('m/d/Y');
                break;

            case 'lastYear':
                $this->startDate = now()->subYear()->startOfYear()->format('m/d/Y');
                $this->endDate = now()->subYear()->endOfYear()->format('m/d/Y');
                break;

            default:
                $this->startDate = now()->startOfWeek()->format('m/d/Y');
                $this->endDate = now()->endOfWeek()->format('m/d/Y');
                break;
        }
    }

    #[On('setStartDate')]
    public function setStartDate($start)
    {
        $this->startDate = $start;
    }

    #[On('setEndDate')]
    public function setEndDate($end)
    {
        $this->endDate = $end;
    }

    #[On('showCancelKotModal')]
    public function showCancelKotModal($id = null)
    {
        $this->confirmDeleteKotModal = true;
        $this->selectedCancelKotId = $id;
    }

    public function updatedDateRangeType($value)
    {
        cookie()->queue(cookie('kots_date_range_type', $value, 60 * 24 * 30)); // 30 days
    }

    public function deleteKot($id)
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

        $kot = Kot::with('items')->findOrFail($id);

        // Guard: prevent cancelling a KOT whose items are all claimed by another kitchen
        $currentKitchenId = $this->kotPlace?->id;
        if ($currentKitchenId) {
            $allClaimedByOthers = $kot->items->count() > 0 && $kot->items->every(function ($item) use ($currentKitchenId) {
                return $item->is_multi_kitchen && $item->claimed_by_kitchen_id && $item->claimed_by_kitchen_id != $currentKitchenId;
            });
            if ($allClaimedByOthers) {
                $this->confirmDeleteKotModal = false;
                $this->dispatch('refreshKots');
                return;
            }
        }

        $order = $kot->order;
        $kotCounts = $order->kot()->whereNot('status', 'cancelled')->count();

        // Update cancel reason info
        $kot->cancel_reason_id = $this->cancelReason;
        $kot->cancel_reason_text = $this->cancelReasonText;
        $kot->status = 'cancelled';
        $kot->save();


        // If this is the only KOT in the order, cancel the order
        if ($kotCounts === 1) {
            $order->status = 'canceled';
            $order->order_status = 'cancelled';
            $order->save();

            if ($order->table) {
                $order->table->update(['available_status' => 'available']);
            }
        }

        // Optional: soft delete kot or destroy it
        // Kot::destroy($id); // if using force delete

        $this->confirmDeleteKotModal = false;

        $this->reset(['cancelReason', 'cancelReasonText', 'selectedCancelKotId']);

        $this->dispatch('refreshKots');
    }

    public function render()
    {

        $playFoodReadySound = false;

        $tz = timezone();

        $start = Carbon::createFromFormat('m/d/Y', $this->startDate, $tz)
            ->startOfDay()
            ->toDateTimeString();

        $end = Carbon::createFromFormat('m/d/Y', $this->endDate, $tz)
            ->endOfDay()
            ->toDateTimeString();

        if ($this->showAllKitchens) {
            // For all kitchens view - show KOTs from all kitchens
            $kots = Kot::withCount('items')
                ->select('kots.*')
                ->orderBy('kots.id', 'desc')
                ->join('orders', 'kots.order_id', '=', 'orders.id')
                ->where('orders.date_time', '>=', $start)
                ->where('orders.date_time', '<=', $end)
                ->where('orders.status', '<>', 'draft')
                ->whereHas('items')
                ->with([
                    'kotPlace',
                    'items.menuItem',
                    'items.claimedByKitchen',
                    'order',
                    'order.waiter',
                    'order.table',
                    'order.orderType',
                    'items.menuItemVariation',
                    'items.modifierOptions',
                    'cancelReason'
                ]);

            // Filter by kitchen if selected (use KOT's kitchen_place_id for multi-kitchen support)
            if ($this->selectedKitchen) {
                $kots = $kots->where('kots.kitchen_place_id', $this->selectedKitchen);
            }

            // Search functionality
            if ($this->search) {
                $kots = $kots->where(function ($q) {
                    $q->where('kots.kot_number', 'like', '%' . $this->search . '%')
                        ->orWhere('orders.order_number', 'like', '%' . $this->search . '%')
                        ->orWhereHas('order.waiter', function ($waiterQuery) {
                            $waiterQuery->where('name', 'like', '%' . $this->search . '%');
                        })
                        ->orWhereHas('order.table', function ($tableQuery) {
                            $tableQuery->where('table_code', 'like', '%' . $this->search . '%');
                        });
                });
            }

            // Waiter role filter
            if (user()->hasRole('Waiter_' . user()->restaurant_id)) {
                $kots = $kots->where('orders.waiter_id', user()->id);
            }

            $kots = $kots->get();
        } elseif (module_enabled('Kitchen') && in_array('Kitchen', restaurant_modules())) {
            // Kitchen module logic — show KOTs assigned to this kitchen OR
            // KOTs containing multi-kitchen items assigned to this kitchen via pivot
            $currentKitchenId = $this->kotPlace?->id;
            $kots = Kot::withCount('items')
                ->select('kots.*')
                ->distinct()
                ->orderBy('kots.id', 'desc')
                ->join('orders', 'kots.order_id', '=', 'orders.id')
                ->where('orders.date_time', '>=', $start)->where('orders.date_time', '<=', $end)
                ->where('orders.status', '<>', 'draft')
                ->whereHas('items')
                ->where(function ($q) use ($currentKitchenId) {
                    // KOTs directly assigned to this kitchen
                    $q->where('kots.kitchen_place_id', $currentKitchenId)
                      // OR KOTs with multi-kitchen items that are assigned to this kitchen
                      ->orWhereHas('items', function ($itemQuery) use ($currentKitchenId) {
                          $itemQuery->where('is_multi_kitchen', true)
                              ->whereHas('menuItem.kotPlaces', function ($pivotQuery) use ($currentKitchenId) {
                                  $pivotQuery->where('kot_places.id', $currentKitchenId);
                              });
                      });
                })
                ->with([
                    'kotPlace',
                    'items' => function ($query) {
                        $query->with(['menuItem', 'menuItemVariation', 'modifierOptions', 'claimedByKitchen']);
                    },
                    'order',
                    'order.waiter',
                    'order.table',
                    'order.orderType',
                    'cancelReason'
                ]);

            if (user()->hasRole('Waiter_' . user()->restaurant_id)) {
                $kots = $kots->where('orders.waiter_id', user()->id);
            }

            $kots = $kots->get();
        } else {
            // Original non-kitchen module logic
            $kots = Kot::withCount('items')
                ->select('kots.*')
                ->orderBy('kots.id', 'desc')
                ->join('orders', 'kots.order_id', '=', 'orders.id')
                ->where('orders.date_time', '>=', $start)
                ->where('orders.date_time', '<=', $end)
                ->where('orders.status', '<>', 'draft')
                ->whereHas('items')
                ->with('kotPlace', 'items', 'items.menuItem', 'items.claimedByKitchen', 'order', 'order.waiter', 'order.table', 'items.menuItemVariation', 'items.modifierOptions', 'cancelReason');

            if (user()->hasRole('Waiter_' . user()->restaurant_id)) {
                $kots = $kots->where('orders.waiter_id', user()->id);
            }

            $kots = $kots->get();
        }

        if ($this->kotSettings->default_status == 'pending') {
            $inKitchen = $kots->filter(function ($order) {
                return $order->status == 'in_kitchen';
            });
        } else {
            $inKitchen = $kots->filter(function ($order) {
                return $order->status == 'in_kitchen' || $order->status == 'pending_confirmation';
            });
        }

        $foodReady = $kots->filter(function ($order) {
            return $order->status == 'food_ready';
        });

        $foodReadyCount = count($foodReady);
        $sessionKey = 'kots_food_ready_count_' . ($this->showAllKitchens ? 'all' : ($this->kotPlace?->id ?? 'none'));

        if (session()->has($sessionKey) && session($sessionKey) < $foodReadyCount) {
            $playFoodReadySound = true;

            $this->notifyFoodReady();
        }

        session([$sessionKey => $foodReadyCount]);

        $pendingConfirmation = $kots->filter(function ($order) {
            return $order->status == 'pending_confirmation';
        });

        $cancelled = $kots->filter(function ($order) {
            return $order->status == 'cancelled';
        });

        switch ($this->filterOrders) {
            case 'in_kitchen':
                $kotList = $inKitchen;
                break;

            case 'food_ready':
                $kotList = $foodReady;
                break;

            case 'pending_confirmation':
                $kotList = $pendingConfirmation;
                break;

            case 'cancelled':
                $kotList = $cancelled;
                break;

            default:
                $kotList = $kots;
                break;
        }

        $kotSettings = $this->kotSettings;
        $cancelReasons = $this->cancelReasons;
        $kitchens = KotPlace::where('is_active', true)->get();

        return view('livewire.kot.kots', [
            'kots' => $kotList,
            'inKitchenCount' => count($inKitchen),
            'foodReadyCount' => $foodReadyCount,
            'pendingConfirmationCount' => count($pendingConfirmation),
            'cancelledCount' => count($cancelled),
            'kotSettings' => $kotSettings,
            'cancelReasons' => $cancelReasons,
            'kitchens' => $kitchens,
            'showAllKitchens' => $this->showAllKitchens,
            'playFoodReadySound' => $playFoodReadySound,
        ]);
    }

    public function notifyFoodReady(): void
    {
        $this->dispatch('food_ready_sound');

        $this->alert('success', __('messages.foodReady'), [
            'toast' => true,
            'position' => 'top-end'
        ]);
    }
}
