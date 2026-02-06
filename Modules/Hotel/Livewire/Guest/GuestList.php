<?php

namespace Modules\Hotel\Livewire\Guest;

use Livewire\Component;
use Livewire\Attributes\On;
use Modules\Hotel\Entities\Guest;
use App\Models\Customer;

class GuestList extends Component
{
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
        $this->resetForm();
        $this->showAddGuest = true;
    }

    public function editGuest($id)
    {
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

        $this->dispatch('show-notification', [
            'title' => 'Success',
            'message' => $message,
            'type' => 'success'
        ]);

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

    public function deleteGuest($id)
    {
        $guest = Guest::find($id);
        if ($guest) {
            // Check if guest has active reservations
            if ($guest->reservations()->whereIn('status', ['confirmed', 'checked_in'])->count() > 0) {
                $this->dispatch('show-notification', [
                    'title' => 'Cannot Delete',
                    'message' => 'This guest has active reservations.',
                    'type' => 'error'
                ]);
                return;
            }
            $guest->delete();
            $this->dispatch('show-notification', [
                'title' => 'Success',
                'message' => 'Guest deleted successfully',
                'type' => 'success'
            ]);
        }
    }

    public function render()
    {
        $guests = Guest::with(['customer', 'reservations'])
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
