<?php

namespace Modules\Hrm\Livewire\Designations;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Hrm\Entities\Department;
use Modules\Hrm\Entities\Designation;

class DesignationsList extends Component
{
    use WithPagination, AuthorizesRequests;

    public string $search = '';

    public bool $showModal = false;
    public ?int $editingId = null;

    public ?int $department_id = null;
    public string $name = '';
    public ?string $description = null;
    public bool $is_active = true;

    public bool $showDeleteModal = false;
    public ?int $deleteId = null;

    protected $queryString = ['search'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->authorize('Create Designation');

        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $this->authorize('Update Designation');

        $designation = Designation::query()
            ->where('restaurant_id', restaurant()->id)
            ->findOrFail($id);

        $this->editingId = $designation->id;
        $this->department_id = $designation->department_id;
        $this->name = (string) $designation->name;
        $this->description = $designation->description;
        $this->is_active = (bool) $designation->is_active;

        $this->showModal = true;
    }

    public function save(): void
    {
        if ($this->editingId) {
            $this->authorize('Update Designation');
        } else {
            $this->authorize('Create Designation');
        }

        $this->validate([
            'department_id' => ['nullable', 'integer', Rule::exists('hrm_departments', 'id')->where(fn($q) => $q->where('restaurant_id', restaurant()->id))],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('hrm_designations', 'name')
                    ->where(fn($q) => $q->where('restaurant_id', restaurant()->id))
                    ->ignore($this->editingId),
            ],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $designation = $this->editingId
            ? Designation::query()->where('restaurant_id', restaurant()->id)->findOrFail($this->editingId)
            : new Designation();

        $designation->restaurant_id = restaurant()->id;
        $designation->department_id = $this->department_id;
        $designation->name = $this->name;
        $designation->description = $this->description;
        $designation->is_active = $this->is_active;
        $designation->save();

        $this->showModal = false;
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $this->authorize('Delete Designation');

        $this->deleteId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $this->authorize('Delete Designation');

        if (!$this->deleteId) {
            $this->showDeleteModal = false;
            return;
        }

        $designation = Designation::query()
            ->where('restaurant_id', restaurant()->id)
            ->findOrFail($this->deleteId);
        $designation->delete();

        $this->showDeleteModal = false;
        $this->deleteId = null;
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->deleteId = null;
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->department_id = null;
        $this->name = '';
        $this->description = null;
        $this->is_active = true;
    }

    public function render()
    {
        $departments = Department::query()
            ->where('restaurant_id', restaurant()->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $designations = Designation::query()
            ->where('restaurant_id', restaurant()->id)
            ->with(['department:id,name'])
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->paginate(15);

        return view('hrm::livewire.designations-list', [
            'departments' => $departments,
            'designations' => $designations,
        ])->layout('layouts.app');
    }
}
