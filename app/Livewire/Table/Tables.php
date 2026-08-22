<?php

namespace App\Livewire\Table;

use App\Models\Area;
use App\Models\Table;
use Livewire\Component;
use App\Models\Reservation;
use Livewire\Attributes\On;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class Tables extends Component
{

    use LivewireAlert;

    public $activeTable;
    public $areaID = null;
    public $showAddTableModal = false;
    public $showEditTableModal = false;
    public $confirmDeleteTableModal = false;
    public $tableToDelete = null;
    public $filterAvailable = null;
    public $viewType = 'list';
    public $reservations;
    public $reservedTables;
    public $timeSlotDifference;

    protected $listeners = [
        'tableLockUpdated' => 'handleTableLockUpdate',
        'refreshTables' => '$refresh'
    ];

    public function mount()
    {
        // Get the saved view type from session, default to 'list' if not set
        $this->viewType = session('table_view_type', 'list');
        $this->reservations = Reservation::where('table_id', '!=', null)->get();
        // dd($this->reservations);
        $this->reservedTables = $this->reservations->pluck('table_id', 'reservation_date_time', 'reservation_status');
        // dd($this->reservedTables);

        $this->refreshDataWithCleanup();
    }

    public function updatedViewType($value)
    {
        $this->refreshDataWithCleanup();

        // Save the view type preference to session whenever it changes
        session(['table_view_type' => $value]);
    }

    #[On('refreshTables')]
    public function refreshTables()
    {
        $this->render();
    }

    #[On('hideAddTable')]
    public function hideAddTable()
    {
        $this->showAddTableModal = false;
    }

    #[On('hideEditTable')]
    public function hideEditTable()
    {
        $this->showEditTableModal = false;
    }

    public function showEditTable($id)
    {
        $this->activeTable = Table::findOrFail($id);
        $this->showEditTableModal = true;
    }

    public function confirmDeleteTable($id)
    {
        if (!user_can('Delete Table')) {
            $this->alert('error', __('messages.tableUnlockFailed'), [
                'toast' => true,
                'position' => 'top-end'
            ]);
            return;
        }

        $table = Table::with('activeOrder')->find($id);
        if (!$table) {
            $this->alert('error', __('messages.tableNotFound'), [
                'toast' => true,
                'position' => 'top-end'
            ]);
            return;
        }

        // A running table (active order) cannot be deleted.
        if ($table->activeOrder) {
            $this->alert('error', __('modules.table.cannotDeleteRunningTable'), [
                'toast' => true,
                'position' => 'top-end'
            ]);
            return;
        }

        $this->tableToDelete = $id;
        $this->confirmDeleteTableModal = true;
    }

    public function deleteTable()
    {
        if (!user_can('Delete Table') || !$this->tableToDelete) {
            $this->confirmDeleteTableModal = false;
            $this->tableToDelete = null;
            return;
        }

        $table = Table::with('activeOrder')->find($this->tableToDelete);

        if (!$table) {
            $this->confirmDeleteTableModal = false;
            $this->tableToDelete = null;
            return;
        }

        if ($table->activeOrder) {
            $this->alert('error', __('modules.table.cannotDeleteRunningTable'), [
                'toast' => true,
                'position' => 'top-end'
            ]);
            $this->confirmDeleteTableModal = false;
            $this->tableToDelete = null;
            return;
        }

        // Release any dangling manual lock before deletion to avoid orphan sessions.
        if ($table->tableSession && !$table->tableSession->isOrderLock()) {
            $table->tableSession->releaseLock();
        }

        $table->delete();

        $this->confirmDeleteTableModal = false;
        $this->tableToDelete = null;

        $this->alert('success', __('messages.tableDeleted'), [
            'toast' => true,
            'position' => 'top-end'
        ]);

        $this->dispatch('refreshTables');
    }

    public function showTableOrder($id)
    {
        // Check if table is locked before allowing access
        $table = Table::with('activeOrder:id,table_id')->find($id);

        if ($table && !$table->canBeAccessedByUser(user()->id)) {
            $session = $table->tableSession;
            $lockedByUser = $session?->lockedByUser;
            $lockedUserName = $lockedByUser?->name ?? 'Admin';

            $this->alert('error', __('messages.tableLockedByUser', ['user' => $lockedUserName]), [
                'toast' => true,
                'position' => 'top-end'
            ]);
            return;
        }

        // Redirect to Vue POS instead of legacy Livewire POS
        // If the table has an active order, open it in Vue POS; otherwise start new order
        if ($table && $table->activeOrder) {
            return $this->redirect(
                route('pos.kot', $table->activeOrder->id),
                navigate: true
            );
        }

        // No active order: redirect to Vue POS for new order with table pre-selected.
        return $this->redirect(route('pos.vue', ['table_id' => $id]), navigate: true);
    }

    public function showTableOrderDetail($id)
    {
        // Previously redirected to route('pos.order', [$id]) passing the table_id,
        // but /pos/order/{id} is served by the Vue POS which reads the path segment
        // as an order_id (PosApp.vue getUrlParams). That caused an unrelated/historical
        // order (often cancelled) to render. Resolve the table's real active order
        // and use the canonical show-order-detail URL used elsewhere in the codebase
        // (Pos.php showOrderDetail redirect, PosApp.vue navigateToLinkedOrderDetail).
        $table = Table::with('activeOrder:id,table_id')->find($id);

        if (!$table) {
            return;
        }

        $activeOrderId = $table->activeOrder?->id;

        if ($activeOrderId) {
            return $this->redirect(
                route('pos.kot', $activeOrderId) . '?show-order-detail=true',
                navigate: true
            );
        }

        return $this->redirect(route('pos.vue', ['table_id' => $id]), navigate: true);
    }

    public function forceUnlockTable($tableId)
    {
        $table = Table::find($tableId);

        if (!$table) {
            $this->alert('error', __('messages.tableNotFound'), [
                'toast' => true,
                'position' => 'top-end'
            ]);
            return;
        }

        // Check permissions in one condition
        $hasPermission = user()->hasRole('Admin_' . user()->restaurant_id) ||
                        ($table->tableSession && $table->tableSession->locked_by_user_id == user()->id);

        if (!$hasPermission) {
            $this->alert('error', __('messages.tableUnlockFailed'), [
                'toast' => true,
                'position' => 'top-end'
            ]);
            return;
        }

        // Force unlock and handle result
        $result = $table->unlock(null, true);

        $this->alert(
            $result['success'] ? 'success' : 'error',
            $result['success']
                ? __('messages.tableUnlockedSuccess', ['table' => $table->table_code])
                : __('messages.tableUnlockFailed'),
            ['toast' => true, 'position' => 'top-end']
        );

        $this->dispatch('refreshTables');
    }

    public function refreshDataWithCleanup()
    {
        try {
            // First, clean up expired locks and get the result
            \App\Models\Table::cleanupExpiredLocks();
            // Then refresh the component
            $this->dispatch('$refresh');

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('SetTable: Error in refreshDataWithCleanup', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    public function render()
    {
        // Clean up expired locks before building the query so the filter remains accurate.
        // This ensures tables with timed-out manual locks are not treated as "running".
        \App\Models\Table::cleanupExpiredLocks();

        // "Running" = has an active order OR the table session is locked
        // (manual user-lock or order-lock). A locked table is an assigned table.
        // Note: Order locks (locked_by_order=true) NEVER expire and persist until explicitly released.
        $lockedSessionFilter = function ($s) {
            $s->where('locked_by_order', true)
              ->orWhere(function ($q) {
                  $q->whereNotNull('locked_by_user_id')
                    ->whereNotNull('locked_at');
              });
        };

        $query = Area::with(['tables' => function ($query) use ($lockedSessionFilter) {
            // Filter by the effective availability:
            //  - running   => active order OR locked session
            //  - reserved  => not running AND stored column = 'reserved'
            //  - available => not running AND stored column != 'reserved'
            if ($this->filterAvailable === 'running') {
                $query->where(function ($q) use ($lockedSessionFilter) {
                    $q->whereHas('activeOrder')
                      ->orWhereHas('tableSession', $lockedSessionFilter);
                });
            } elseif ($this->filterAvailable === 'reserved') {
                $query->whereDoesntHave('activeOrder')
                    ->whereDoesntHave('tableSession', $lockedSessionFilter)
                    ->where('available_status', 'reserved');
            } elseif ($this->filterAvailable === 'available') {
                $query->whereDoesntHave('activeOrder')
                    ->whereDoesntHave('tableSession', $lockedSessionFilter)
                    ->where(function ($q) {
                        $q->where('available_status', '!=', 'reserved')
                          ->orWhereNull('available_status');
                    });
            }
        }, 'tables.activeOrder', 'tables.tableSession.lockedByUser']);

        if (!is_null($this->areaID)) {
            $query = $query->where('id', $this->areaID);
        }

        $query = $query->get();

        // Get all table IDs to check for reservations
        $tableIds = $query->flatMap(function($area) {
            return $area->tables->pluck('id');
        });

        // Get reservations for these tables
        $tableReservations = $this->reservations->whereIn('table_id', $tableIds)
            ->keyBy('table_id')
            ->map(function($reservation) {
                // Get the time slot difference for this reservation's slot type
                $timeSlotDifference = \App\Models\ReservationSetting::where('slot_type', $reservation->reservation_slot_type)->first();

                return [
                    'date' => $reservation->reservation_date_time->format('M d, Y'),
                    'time' => $reservation->reservation_date_time->format('h:i A'),
                    'datetime' => $reservation->reservation_date_time->format('M d, Y h:i A'),
                    'status' => $reservation->reservation_status,
                    'reservation_slot_type' => $reservation->reservation_slot_type,
                    'timeSlotDifference' => $timeSlotDifference ? $timeSlotDifference->time_slot_difference : null
                ];
            });



        return view('livewire.table.tables', [
            'tables' => $query,
            'areas' => Area::get(),
            'tableReservations' => $tableReservations
        ]);
    }

}
