<?php

namespace App\Livewire\Reservations;

use App\Models\Reservation;
use App\Models\ReservationSetting;
use Carbon\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class ReservationCard extends Component
{
    use LivewireAlert;

    public $reservation;
    public $tableReservation;
    public $showTableModal = false;
    public $reservationStatus;

    // Edit form state
    public $showEditModal = false;
    public $editDate;
    public $editTime;
    public $editPartySize;
    public $editSlotType;
    public $editSpecialRequests;
    public $editTimeSlots = [];

    public function mount()
    {
        $this->reservationStatus = $this->reservation->reservation_status;
    }

    public function assignTable($reservationId)
    {
        $this->tableReservation = Reservation::find($reservationId);
        $this->showTableModal = true;
    }

    public function changeTable($reservationId)
    {
        $this->tableReservation = Reservation::find($reservationId);
        $this->showTableModal = true;
    }

    public function updatedReservationStatus($status)
    {
        $this->reservation->update(['reservation_status' => $status]);

        if ($status === 'Cancelled' && $this->reservation->table_id) {
            $this->reservation->table->update(['available_status' => 'available']);
            $this->reservation->update(['table_id' => null]);
        }
    }

    // ─── Edit Reservation ───────────────────────────────────────────

    public function editReservation()
    {
        $r = $this->reservation;
        $this->editDate = $r->reservation_date_time->format('Y-m-d');
        $this->editTime = $r->reservation_date_time->format('H:i:s');
        $this->editPartySize = $r->party_size;
        $this->editSlotType = $r->reservation_slot_type ?? 'Lunch';
        $this->editSpecialRequests = $r->special_requests;

        $this->loadEditTimeSlots();
        $this->showEditModal = true;
    }

    public function updatedEditDate()
    {
        $this->loadEditTimeSlots();
    }

    public function updatedEditSlotType()
    {
        $this->loadEditTimeSlots();
    }

    public function loadEditTimeSlots()
    {
        $this->editTimeSlots = [];

        if (! $this->editDate || ! $this->editSlotType) {
            return;
        }

        $parsedDate = Carbon::parse($this->editDate);
        $dayOfWeek = $parsedDate->format('l');
        $selectedDate = $parsedDate->format('Y-m-d');
        $currentTimezone = timezone() ?: 'UTC';

        $now = Carbon::now($currentTimezone);
        $restaurant = restaurant();
        $disableSlotMinutes = $restaurant ? (int) ($restaurant->disable_slot_minutes ?? 30) : 30;
        $currentTimeWithBuffer = $now->copy()->addMinutes($disableSlotMinutes);

        $settings = ReservationSetting::where('day_of_week', $dayOfWeek)
            ->where('slot_type', $this->editSlotType)
            ->where('available', 1)
            ->first();

        if (! $settings) {
            return;
        }

        $startTime = Carbon::parse($settings->time_slot_start);
        $endTime = Carbon::parse($settings->time_slot_end);
        $slotDifference = (int) $settings->time_slot_difference;

        while ($startTime->lte($endTime)) {
            $slotTime = $startTime->format('H:i:s');
            $slotDateTime = Carbon::parse("{$selectedDate} {$slotTime}", $currentTimezone);

            $isToday = $selectedDate === $now->format('Y-m-d');
            $isDisabled = $isToday && $slotDateTime->lte($currentTimeWithBuffer);

            $this->editTimeSlots[] = [
                'time' => $slotTime,
                'disabled' => $isDisabled,
            ];

            $startTime->addMinutes($slotDifference);
        }
    }

    public function updateReservation()
    {
        $minimumPartySize = restaurant()->minimum_party_size ?? 1;

        $this->validate([
            'editDate' => 'required|date',
            'editTime' => 'required',
            'editPartySize' => "required|integer|min:{$minimumPartySize}",
            'editSlotType' => 'required|in:Breakfast,Lunch,Dinner',
        ]);

        $this->reservation->update([
            'reservation_date_time' => $this->editDate . ' ' . $this->editTime,
            'party_size' => $this->editPartySize,
            'reservation_slot_type' => $this->editSlotType,
            'special_requests' => $this->editSpecialRequests,
        ]);

        $this->reservation->refresh();
        $this->reservationStatus = $this->reservation->reservation_status;
        $this->showEditModal = false;

        $this->alert('success', __('messages.updateSuccess'), [
            'toast' => true,
            'position' => 'top-end',
            'timer' => 3000,
        ]);
    }

    // ─── Delete Reservation ─────────────────────────────────────────

    protected $listeners = ['confirmDeleteReservation'];

    public function askDeleteReservation()
    {
        $this->confirm(__('modules.reservation.deleteReservationConfirm') ?: 'Are you sure you want to delete this reservation?', [
            'position' => 'center',
            'toast' => false,
            'showConfirmButton' => true,
            'confirmButtonText' => __('messages.confirmDelete'),
            'confirmButtonColor' => '#dc2626',
            'showCancelButton' => true,
            'cancelButtonText' => __('app.cancel'),
            'onConfirmed' => 'confirmDeleteReservation',
        ]);
    }

    public function confirmDeleteReservation()
    {
        // Release the table if one was assigned
        if ($this->reservation->table_id) {
            $this->reservation->table->update(['available_status' => 'available']);
        }

        $this->reservation->delete();

        $this->alert('success', __('messages.deleteSuccess'), [
            'toast' => false,
            'position' => 'center',
            'showCancelButton' => true,
            'cancelButtonText' => __('app.close'),
        ]);

        return $this->redirect(route('reservations.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.reservations.reservation-card');
    }
}

