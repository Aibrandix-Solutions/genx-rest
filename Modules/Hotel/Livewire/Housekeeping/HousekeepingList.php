<?php

namespace Modules\Hotel\Livewire\Housekeeping;

use Livewire\Component;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Modules\Hotel\Entities\HousekeepingTask;
use Modules\Hotel\Entities\Room;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class HousekeepingList extends Component
{
    use LivewireAlert;

    public $tasks;
    public $rooms;
    public $users;
    public $statusFilter = 'all';

    public $showTaskModal = false;
    public $editingTaskId = null;
    public $room_id = '';
    public $task_type = HousekeepingTask::TYPE_CLEANING;
    public $priority = HousekeepingTask::PRIORITY_MEDIUM;
    public $assigned_to_user_id = '';
    public $notes = '';

    public function mount()
    {
        abort_unless(user_can('view_hotel_housekeeping'), 403);

        // Respect enable_housekeeping_module setting
        $hotelSettings = \Modules\Hotel\Entities\HotelSetting::where('restaurant_id', restaurant()->id)->first();
        if ($hotelSettings && !$hotelSettings->enable_housekeeping_module) {
            abort(403, 'Housekeeping module is disabled. Enable it in Hotel Settings.');
        }

        $this->loadReferenceData();
        $this->loadTasks();
    }

    public function loadReferenceData()
    {
        $this->rooms = Room::where('restaurant_id', restaurant()->id)
            ->orderBy('room_number')
            ->get();

        $this->users = User::where('restaurant_id', restaurant()->id)
            ->orderBy('name')
            ->get();
    }

    public function updatedStatusFilter()
    {
        $this->loadTasks();
    }

    public function loadTasks()
    {
        $query = HousekeepingTask::with(['room', 'assignedTo'])
            ->where('restaurant_id', restaurant()->id)
            ->orderBy('created_at', 'desc');

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        $this->tasks = $query->get();
    }

    public function openTaskModal()
    {
        abort_unless(user_can('view_hotel_housekeeping'), 403);
        $this->resetForm();
        $this->showTaskModal = true;
    }

    public function editTask($id)
    {
        abort_unless(user_can('view_hotel_housekeeping'), 403);
        $task = HousekeepingTask::where('restaurant_id', restaurant()->id)->find($id);
        if (!$task) {
            return;
        }

        $this->editingTaskId = $task->id;
        $this->room_id = $task->room_id;
        $this->task_type = $task->task_type;
        $this->priority = $task->priority;
        $this->assigned_to_user_id = $task->assigned_to_user_id;
        $this->notes = $task->notes;
        $this->showTaskModal = true;
    }

    public function saveTask()
    {
        abort_unless(user_can('view_hotel_housekeeping'), 403);

        $this->validate([
            'room_id' => 'required|exists:hotel_rooms,id',
            'task_type' => 'required|in:cleaning,maintenance,inspection',
            'priority' => 'required|in:low,medium,high,urgent',
            'assigned_to_user_id' => 'nullable|exists:users,id',
            'notes' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () {
            if ($this->editingTaskId) {
                // Preserve existing status on edit — do not reset to pending
                $task = HousekeepingTask::where('restaurant_id', restaurant()->id)->find($this->editingTaskId);
                if ($task) {
                    $task->update([
                        'restaurant_id'        => restaurant()->id,
                        'room_id'              => $this->room_id,
                        'task_type'            => $this->task_type,
                        'priority'             => $this->priority,
                        'assigned_to_user_id'  => $this->assigned_to_user_id ?: null,
                        'notes'                => $this->notes ?: null,
                        // status intentionally omitted — keep whatever it already is
                    ]);
                }
            } else {
                HousekeepingTask::create([
                    'restaurant_id'        => restaurant()->id,
                    'room_id'              => $this->room_id,
                    'task_type'            => $this->task_type,
                    'priority'             => $this->priority,
                    'assigned_to_user_id'  => $this->assigned_to_user_id ?: null,
                    'notes'                => $this->notes ?: null,
                    'status'               => HousekeepingTask::STATUS_PENDING,
                ]);

                // Set room status only when creating (not on edit, to avoid overriding existing flow)
                $room = Room::where('restaurant_id', restaurant()->id)->find($this->room_id);
                if ($room) {
                    if ($this->task_type === HousekeepingTask::TYPE_CLEANING) {
                        $room->update(['status' => Room::STATUS_CLEANING]);
                    } elseif ($this->task_type === HousekeepingTask::TYPE_MAINTENANCE) {
                        $room->update(['status' => Room::STATUS_MAINTENANCE]);
                    }
                }
            }
        });

        $this->showTaskModal = false;
        $this->resetForm();
        $this->loadTasks();

        $this->alert('success', $this->editingTaskId ? 'Task updated successfully.' : 'Task created successfully.');
    }

    public function startTask($id)
    {
        abort_unless(user_can('view_hotel_housekeeping'), 403);
        $task = HousekeepingTask::where('restaurant_id', restaurant()->id)->find($id);
        if (!$task) {
            return;
        }

        $task->start();
        $this->loadTasks();
        $this->alert('success', 'Task marked as in progress.');
    }

    public function completeTask($id)
    {
        abort_unless(user_can('view_hotel_housekeeping'), 403);
        $task = HousekeepingTask::where('restaurant_id', restaurant()->id)->find($id);
        if (!$task) {
            return;
        }

        // complete() internally sets room to available for cleaning tasks
        $task->complete();

        // Handle maintenance separately — entity only handles cleaning internally
        if ($task->room && $task->task_type === HousekeepingTask::TYPE_MAINTENANCE) {
            $task->room->update(['status' => Room::STATUS_AVAILABLE]);
        }

        $this->loadTasks();
        $this->alert('success', 'Task completed. Room marked as available.');
    }

    private function resetForm()
    {
        $this->editingTaskId = null;
        $this->room_id = '';
        $this->task_type = HousekeepingTask::TYPE_CLEANING;
        $this->priority = HousekeepingTask::PRIORITY_MEDIUM;
        $this->assigned_to_user_id = '';
        $this->notes = '';
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('hotel::livewire.housekeeping.housekeeping-list')->layout('layouts.app');
    }
}
