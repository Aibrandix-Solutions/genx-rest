<?php

namespace Modules\Hotel\Livewire\RoomType;

use Livewire\Component;
use Livewire\Attributes\On;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Modules\Hotel\Entities\RoomType;

class RoomTypeList extends Component
{
    use LivewireAlert;
    public $showAddRoomType = false;
    public $showEditRoomType = false;
    public $editingRoomTypeId = null;
    public $search = '';

    public $name = '';
    public $description = '';
    public $base_price = 0;
    public $max_occupancy = 2;
    public $amenities = [];
    public $amenitiesInput = '';
    public $is_active = true;

    #[On('room-type-saved')]
    public function roomTypeSaved()
    {
        $this->showAddRoomType = false;
        $this->showEditRoomType = false;
        $this->editingRoomTypeId = null;
        $this->dispatch('$refresh');
    }

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'base_price' => 'required|numeric|min:0',
            'max_occupancy' => 'required|integer|min:1',
            'amenitiesInput' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }

    public function mount()
    {
        abort_unless(user_can('view_hotel_room_types'), 403);
    }

    public function createRoomType()
    {
        abort_unless(user_can('create_room_type'), 403);
        $this->resetForm();
        $this->showAddRoomType = true;
    }

    public function editRoomType($id)
    {
        abort_unless(user_can('edit_room_type'), 403);
        $this->resetForm();
        $this->editingRoomTypeId = $id;
        $roomType = RoomType::find($id);
        
        if ($roomType) {
            $this->name = $roomType->name;
            $this->description = $roomType->description;
            $this->base_price = $roomType->base_price;
            $this->max_occupancy = $roomType->max_occupancy;
            $this->amenities = $roomType->amenities ?? [];
            $this->amenitiesInput = implode(', ', $this->amenities);
            $this->is_active = (bool) $roomType->is_active;
            
            $this->showEditRoomType = true;
        }
    }

    public function saveRoomType()
    {
        abort_unless(user_can($this->editingRoomTypeId ? 'edit_room_type' : 'create_room_type'), 403);

        $this->validate();

        // Process amenities
        $amenities = array_map('trim', explode(',', $this->amenitiesInput));
        $amenities = array_filter($amenities); // Remove empty values

        $data = [
            'branch_id' => auth()->user()->branch_id ?? 1,
            'name' => $this->name,
            'description' => $this->description,
            'base_price' => $this->base_price,
            'max_occupancy' => $this->max_occupancy,
            'amenities' => $amenities,
            'is_active' => $this->is_active,
        ];

        if ($this->editingRoomTypeId) {
            $roomType = RoomType::find($this->editingRoomTypeId);
            $roomType->update($data);
            $message = 'Room Type updated successfully';
        } else {
            RoomType::create($data);
            $message = 'Room Type created successfully';
        }

        $this->alert('success', $message);

        $this->showAddRoomType = false;
        $this->showEditRoomType = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->editingRoomTypeId = null;
        $this->name = '';
        $this->description = '';
        $this->base_price = 0;
        $this->max_occupancy = 2;
        $this->amenities = [];
        $this->amenitiesInput = '';
        $this->is_active = true;
        $this->resetErrorBag();
    }

    public function deleteRoomType($id)
    {
        abort_unless(user_can('delete_room_type'), 403);
        $roomType = RoomType::find($id);
        if ($roomType) {
            // Check if there are rooms using this type
            if ($roomType->rooms()->count() > 0) {
                $this->alert('error', 'This room type has rooms assigned to it.');
                return;
            }
            $roomType->delete();
            $this->alert('success', 'Room Type deleted successfully');
        }
    }

    public function render()
    {
        $roomTypes = RoomType::with(['rooms'])
            ->where('branch_id', auth()->user()->branch_id ?? 1)
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%');
            })
            ->latest()
            ->get();

        return view('hotel::livewire.room-type.room-type-list', [
            'roomTypes' => $roomTypes,
        ])->layout('layouts.app');
    }
}
