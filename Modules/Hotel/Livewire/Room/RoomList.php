<?php

namespace Modules\Hotel\Livewire\Room;

use Livewire\Component;
use Livewire\Attributes\On;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Modules\Hotel\Entities\Room;
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

    public $room_number = '';
    public $floor = '';
    public $room_type_id = '';
    public $status = 'available';

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
            'room_number' => 'required|string|max:50', // Unique check processed manually to handle edit exclusions
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

        // Custom validation for uniqueness within branch logic if needed, 
        // but basic unique rule works if we ignore ID.
        $this->validate([
            'room_number' => [
                'required', 
                'string', 
                'max:50',
                // Ensure room number is unique for this branch
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
            // New rooms default to clean
            $data['last_cleaned_at'] = now();
            Room::create($data);
            $message = 'Room created successfully';
        }

        $this->alert('success', $message);

        $this->showAddRoom = false;
        $this->showEditRoom = false;
        $this->resetForm();
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

    public function deleteRoom($id)
    {
        abort_unless(user_can('delete_room'), 403);
        $room = Room::find($id);
        if ($room) {
            // Check if room has active reservations
            if ($room->reservations()->whereIn('status', ['confirmed', 'checked_in'])->count() > 0) {
                $this->alert('error', 'This room has active reservations.');
                return;
            }
            $room->delete();
            $this->alert('success', 'Room deleted successfully');
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
            ->orderBy('room_number')
            ->get();

        $roomTypes = RoomType::all();

        return view('hotel::livewire.room.room-list', [
            'rooms' => $rooms,
            'roomTypes' => $roomTypes,
        ])->layout('layouts.app');
    }
}
