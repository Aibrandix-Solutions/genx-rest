<?php

namespace Modules\Hotel\Livewire\Room;

use Livewire\Component;
use Livewire\Attributes\On;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Modules\Hotel\Entities\Room;
use Modules\Hotel\Entities\Reservation;
use Modules\Hotel\Entities\RoomType;

class RoomList extends Component
{
    use LivewireAlert;
    public $showAddRoom = false;
    public $showEditRoom = false;
    public $editingRoomId = null;
    public $search = '';
    public $statusFilter = 'all';
    public $roomTypeFilter = 'all';
    public $floorFilter = 'all';

    public $room_number = '';
    public $floor = '';
    public $room_type_id = '';
    public $status = 'available';

    // Room reservations modal
    public $showRoomReservations = false;
    public $selectedRoomId = null;
    public $selectedRoomNumber = '';
    public $roomReservations = [];
    public $reservationStatusFilter = 'all';

    // Delete confirmation
    public $pendingDeleteRoomId = null;

    #[On('room-saved')]
    public function roomSaved()
    {
        $this->showAddRoom = false;
        $this->showEditRoom = false;
        $this->editingRoomId = null;
        $this->dispatch('$refresh');
    }

    protected function rules()
    {
        return [
            'room_number' => 'required|string|max:50',
            'floor' => 'nullable|string|max:50',
            'room_type_id' => 'required|exists:hotel_room_types,id',
            'status' => 'required|in:available,occupied,cleaning,maintenance,blocked',
        ];
    }

    public function mount()
    {
        abort_unless(user_can('view_hotel_rooms'), 403);
    }

    public function createRoom()
    {
        abort_unless(user_can('create_room'), 403);
        $this->resetForm();
        $this->showAddRoom = true;
    }

    public function editRoom($id)
    {
        abort_unless(user_can('edit_room'), 403);
        $this->resetForm();
        $this->editingRoomId = $id;
        $room = Room::find($id);
        
        if ($room) {
            $this->room_number = $room->room_number;
            $this->floor = $room->floor;
            $this->room_type_id = $room->room_type_id;
            $this->status = $room->status;
            
            $this->showEditRoom = true;
        }
    }

    public function saveRoom()
    {
        abort_unless(user_can($this->editingRoomId ? 'edit_room' : 'create_room'), 403);

        $this->validate([
            'room_number' => [
                'required', 
                'string', 
                'max:50',
                function ($attribute, $value, $fail) {
                    $query = Room::where('room_number', $value);
                        
                    if ($this->editingRoomId) {
                        $query->where('id', '!=', $this->editingRoomId);
                    }
                    
                    if ($query->exists()) {
                        $fail('The room number has already been taken.');
                    }
                }
            ],
            'floor' => 'nullable|string|max:50',
            'room_type_id' => 'required|exists:hotel_room_types,id',
            'status' => 'required|in:available,occupied,cleaning,maintenance,blocked',
        ]);

        $data = [
            'room_number' => $this->room_number,
            'floor' => $this->floor,
            'room_type_id' => $this->room_type_id,
            'status' => $this->status,
        ];

        if ($this->editingRoomId) {
            $room = Room::find($this->editingRoomId);
            $room->update($data);
            $message = 'Room updated successfully';
        } else {
            $data['last_cleaned_at'] = now();
            Room::create($data);
            $message = 'Room created successfully';
        }

        $this->alert('success', $message);

        $this->showAddRoom = false;
        $this->showEditRoom = false;
        $this->resetForm();
    }

    public function clearFilters()
    {
        $this->statusFilter = 'all';
        $this->roomTypeFilter = 'all';
        $this->floorFilter = 'all';
        $this->search = '';
    }

    private function resetForm()
    {
        $this->editingRoomId = null;
        $this->room_number = '';
        $this->floor = '';
        $this->room_type_id = '';
        $this->status = 'available';
        $this->resetErrorBag();
    }

    public function confirmDeleteRoom($id)
    {
        $this->pendingDeleteRoomId = $id;
        $this->alert('warning', 'Are you sure you want to delete this room?', [
            'showConfirmButton' => true,
            'showCancelButton' => true,
            'confirmButtonText' => 'Yes, Delete',
            'cancelButtonText' => 'Cancel',
            'onConfirmed' => 'deleteRoomConfirmed',
        ]);
    }

    #[On('deleteRoomConfirmed')]
    public function deleteRoom()
    {
        $id = $this->pendingDeleteRoomId;
        abort_unless(user_can('delete_room'), 403);
        $room = Room::find($id);
        if ($room) {
            if ($room->reservations()->whereIn('status', ['confirmed', 'checked_in'])->count() > 0) {
                $this->alert('error', 'This room has active reservations.');
                return;
            }
            $room->delete();
            $this->alert('success', 'Room deleted successfully');
        }
        $this->pendingDeleteRoomId = null;
    }

    public function viewRoomReservations($id)
    {
        $room = Room::find($id);
        if ($room) {
            $this->selectedRoomId = $id;
            $this->selectedRoomNumber = $room->room_number;
            $this->reservationStatusFilter = 'all';
            $this->loadRoomReservations();
            $this->showRoomReservations = true;
        }
    }

    public function updatedReservationStatusFilter()
    {
        $this->loadRoomReservations();
    }

    private function loadRoomReservations()
    {
        $query = Reservation::with(['guest'])
            ->where('room_id', $this->selectedRoomId)
            ->when($this->reservationStatusFilter !== 'all', function ($q) {
                $q->where('status', $this->reservationStatusFilter);
            })
            ->orderByDesc('check_in_date');

        $this->roomReservations = $query->get()->toArray();
    }

    public function updateRoomStatus($roomId, $newStatus)
    {
        abort_unless(user_can('edit_room'), 403);
        $room = Room::find($roomId);
        if ($room) {
            if ($room->status === 'occupied' && $newStatus !== 'occupied') {
                $this->alert('error', 'Cannot change status of an occupied room. Checkout the guest first.');
                return;
            }
            $room->update(['status' => $newStatus]);
            $this->alert('success', "Room {$room->room_number} marked as " . ucfirst($newStatus));
        }
    }

    public function render()
    {
        $rooms = Room::with(['roomType', 'currentReservation.guest'])
            ->when($this->search, function ($query) {
                $query->where('room_number', 'like', '%' . $this->search . '%')
                    ->orWhere('floor', 'like', '%' . $this->search . '%');
            })
            ->when($this->statusFilter !== 'all', function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->when($this->roomTypeFilter !== 'all', function ($query) {
                $query->where('room_type_id', $this->roomTypeFilter);
            })
            ->when($this->floorFilter !== 'all', function ($query) {
                $query->where('floor', $this->floorFilter);
            })
            ->orderBy('room_number')
            ->get();

        $roomTypes = RoomType::all();

        // Get available floors for filter
        $floors = Room::whereNotNull('floor')
            ->where('floor', '!=', '')
            ->distinct()
            ->orderBy('floor')
            ->pluck('floor');

        // Summary stats
        $allRooms = Room::selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available,
            SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) as occupied,
            SUM(CASE WHEN status = 'cleaning' THEN 1 ELSE 0 END) as cleaning,
            SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance,
            SUM(CASE WHEN status = 'blocked' THEN 1 ELSE 0 END) as blocked
        ")->first();

        return view('hotel::livewire.room.room-list', [
            'rooms' => $rooms,
            'roomTypes' => $roomTypes,
            'floors' => $floors,
            'stats' => $allRooms,
        ])->layout('layouts.app');
    }
}
