<?php

namespace App\Livewire\Forms;

use App\Models\DeliveryExecutive;
use App\Enums\ActivityEvent;
use App\Support\ActivityLogger;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class AddExecutive extends Component
{

    use LivewireAlert;

    public $memberName;
    public $memberPhone;
    public $status = 'available';

    public function submitForm()
    {
        $this->validate([
            'memberName' => 'required',
            'memberPhone' => 'required|unique:delivery_executives,phone'
        ]);

        $executive = DeliveryExecutive::create([
            'name' => $this->memberName,
            'phone' => $this->memberPhone,
            'status' => $this->status,
        ]);

        ActivityLogger::recordEvent(
            activityEvent: ActivityEvent::DeliveryExecutiveCreated,
            description: "Delivery executive created: {$executive->name}",
            subject: $executive,
            properties: [
                'delivery_executive_id' => $executive->id,
                'name' => $executive->name,
                'phone' => $executive->phone,
                'status' => $executive->status,
            ],
            branchId: branch()?->id ? (int) branch()->id : null,
        );

        // Reset the value
        $this->memberName = '';
        $this->memberPhone = '';
        $this->status = 'available';

        $this->dispatch('hideAddStaff');

        $this->alert('success', __('messages.memberAdded'), [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close')
        ]);
    }

    public function render()
    {
        return view('livewire.forms.add-executive');
    }

}
