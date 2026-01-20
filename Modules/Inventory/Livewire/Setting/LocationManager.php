<?php

namespace Modules\Inventory\Livewire\Setting;

use App\Models\Branch;
use Livewire\Component;
use Modules\Inventory\Entities\PurchaseLocation;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class LocationManager extends Component
{
    use LivewireAlert;

    public $locations = [];
    public $branches = [];

    public $showModal = false;
    public $editingId = null;

    public $name = '';
    public $type = 'warehouse';
    public $branch_id = null;
    public $address = '';
    public $is_active = true;

    public function mount(): void
    {
        abort_if(!user_can('Manage Locations'), 403);
        $this->loadBranches();
        $this->loadLocations();
    }

    public function render()
    {
        return view('inventory::livewire.setting.location-manager');
    }

    public function startCreate(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function startEdit(int $locationId): void
    {
        $location = PurchaseLocation::where('restaurant_id', restaurant()->id)->findOrFail($locationId);

        $this->editingId = $location->id;
        $this->name = $location->name;
        $this->type = $location->type;
        $this->branch_id = $location->branch_id;
        $this->address = $location->address;
        $this->is_active = $location->is_active;

        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate($this->rules());

        if ($this->type === 'warehouse') {
            $this->branch_id = null;
        }

        if ($this->type === 'branch' && $this->branch_id) {
            $exists = PurchaseLocation::where('restaurant_id', restaurant()->id)
                ->where('type', 'branch')
                ->where('branch_id', $this->branch_id)
                ->when($this->editingId, fn ($query) => $query->where('id', '!=', $this->editingId))
                ->exists();

            if ($exists) {
                $this->addError('branch_id', __('inventory::modules.locations.branchAlreadyHasLocation'));
                return;
            }
        }

        PurchaseLocation::updateOrCreate(
            ['id' => $this->editingId],
            [
                'restaurant_id' => restaurant()->id,
                'name' => $this->name,
                'type' => $this->type,
                'branch_id' => $this->branch_id,
                'address' => $this->address,
                'is_active' => (bool) $this->is_active,
            ]
        );

        $this->loadLocations();
        $this->showModal = false;
        $this->alert('success', __('inventory::modules.locations.locationSaved'));
    }

    public function toggleStatus(int $locationId): void
    {
        $location = PurchaseLocation::where('restaurant_id', restaurant()->id)->findOrFail($locationId);
        $location->update(['is_active' => !$location->is_active]);
        $this->loadLocations();
        $this->alert('success', __('inventory::modules.locations.locationUpdated'));
    }

    public function updatedType(): void
    {
        if ($this->type === 'warehouse') {
            $this->branch_id = null;
        }
    }

    protected function loadLocations(): void
    {
        $this->locations = PurchaseLocation::where('restaurant_id', restaurant()->id)
            ->with('branch')
            ->orderBy('type')
            ->orderBy('name')
            ->get();
    }

    protected function loadBranches(): void
    {
        $this->branches = Branch::where('restaurant_id', restaurant()->id)
            ->orderBy('name')
            ->get();
    }

    protected function rules(): array
    {
        $branchRule = $this->type === 'branch' ? 'required|exists:branches,id' : 'nullable';

        return [
            'name' => 'required|string|max:255',
            'type' => 'required|in:warehouse,branch',
            'branch_id' => $branchRule,
            'address' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->type = 'warehouse';
        $this->branch_id = null;
        $this->address = '';
        $this->is_active = true;
    }
}
