<?php

namespace Modules\Hotel\Livewire\Guest;

use Livewire\Component;
use Livewire\Attributes\On;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Modules\Hotel\Entities\Guest;
use App\Models\Customer;

class GuestList extends Component
{
    use LivewireAlert;
    public $showAddGuest = false;
    public $showEditGuest = false;
    public $editingGuestId = null;
    public $search = '';

    public $first_name = '';
    public $last_name = '';
    public $email = '';
    public $phone = '';
    public $id_type = '';
    public $id_number = '';
    public $address = '';
    public $city = '';
    public $country = '';
    public $notes = '';

    public function mount()
    {
        abort_unless(user_can('view_hotel_guests'), 403);
    }

    #[On('guest-saved')]
    public function guestSaved()
    {
        $this->showAddGuest = false;
        $this->showEditGuest = false;
        $this->editingGuestId = null;
        $this->dispatch('$refresh');
    }

    protected function rules()
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'id_type' => 'nullable|string|max:50',
            'id_number' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ];
    }

    public function createGuest()
    {
        abort_unless(user_can('create_guest'), 403);
        $this->resetForm();
        $this->showAddGuest = true;
    }

    public function editGuest($id)
    {
        abort_unless(user_can('edit_guest'), 403);
        $this->resetForm();
        $this->editingGuestId = $id;
        $guest = Guest::find($id);
        
        if ($guest) {
            $this->first_name = $guest->first_name;
            $this->last_name = $guest->last_name;
            $this->email = $guest->email;
            $this->phone = $guest->phone;
            $this->id_type = $guest->id_type;
            $this->id_number = $guest->id_number;
            $this->address = $guest->address;
            $this->city = $guest->city;
            $this->country = $guest->country;
            $this->notes = $guest->notes;
            
            $this->showEditGuest = true;
        }
    }

    public function saveGuest()
    {
        abort_unless(user_can($this->editingGuestId ? 'edit_guest' : 'create_guest'), 403);

        $this->validate();

        $data = [
            'branch_id' => auth()->user()->branch_id ?? 1,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'id_type' => $this->id_type,
            'id_number' => $this->id_number,
            'address' => $this->address,
            'city' => $this->city,
            'country' => $this->country,
            'notes' => $this->notes,
        ];

        if ($this->editingGuestId) {
            $guest = Guest::find($this->editingGuestId);
            $guest->update($data);
            $message = 'Guest updated successfully';
        } else {
            Guest::create($data);
            $message = 'Guest created successfully';
        }

        $this->alert('success', $message);

        $this->showAddGuest = false;
        $this->showEditGuest = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->editingGuestId = null;
        $this->first_name = '';
        $this->last_name = '';
        $this->email = '';
        $this->phone = '';
        $this->id_type = '';
        $this->id_number = '';
        $this->address = '';
        $this->city = '';
        $this->country = '';
        $this->notes = '';
        $this->resetErrorBag();
    }

    public $pendingDeleteGuestId = null;

    public function confirmDeleteGuest($id)
    {
        $this->pendingDeleteGuestId = $id;
        $this->alert('warning', 'Are you sure you want to delete this guest?', [
            'showConfirmButton' => true,
            'showCancelButton' => true,
            'confirmButtonText' => 'Yes, Delete',
            'cancelButtonText' => 'Cancel',
            'onConfirmed' => 'deleteGuestConfirmed',
        ]);
    }

    #[On('deleteGuestConfirmed')]
    public function deleteGuest($id = null)
    {
        $id = $id ?? $this->pendingDeleteGuestId;
        abort_unless(user_can('delete_guest'), 403);
        $guest = Guest::find($id);
        if ($guest) {
            // Check if guest has active reservations
            if ($guest->reservations()->whereIn('status', ['confirmed', 'checked_in'])->count() > 0) {
                $this->alert('error', 'This guest has active reservations.');
                return;
            }
            $guest->delete();
            $this->alert('success', 'Guest deleted successfully');
        }
    }

    public function render()
    {
        $guests = Guest::with(['customer', 'reservations'])
            ->where('branch_id', auth()->user()->branch_id ?? restaurant()->default_branch_id)
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('first_name', 'like', '%' . $this->search . '%')
                        ->orWhere('last_name', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%')
                        ->orWhere('phone', 'like', '%' . $this->search . '%');
                });
            })
            ->latest()
            ->paginate(20);

        return view('hotel::livewire.guest.guest-list', [
            'guests' => $guests,
        ])->layout('layouts.app');
    }
}
