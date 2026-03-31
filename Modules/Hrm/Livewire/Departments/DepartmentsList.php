<?php

namespace Modules\Hrm\Livewire\Departments;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Hrm\Entities\Department;

class DepartmentsList extends Component
{
    use WithPagination, AuthorizesRequests;

    public string $search = '';

    public bool $showModal = false;
    public ?int $editingId = null;

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
        $this->authorize('Create Department');

        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $this->authorize('Update Department');

        $department = Department::query()
            ->where('restaurant_id', restaurant()->id)
            ->findOrFail($id);

        $this->editingId = $department->id;
        $this->name = (string) $department->name;
        $this->description = $department->description;
        $this->is_active = (bool) $department->is_active;

        $this->showModal = true;
    }

    public function save(): void
    {
        if ($this->editingId) {
            $this->authorize('Update Department');
        } else {
            $this->authorize('Create Department');
        }

        $this->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('hrm_departments', 'name')
                    ->where(fn($q) => $q->where('restaurant_id', restaurant()->id))
                    ->ignore($this->editingId),
            ],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $department = $this->editingId
            ? Department::query()->where('restaurant_id', restaurant()->id)->findOrFail($this->editingId)
            : new Department();

        $department->restaurant_id = restaurant()->id;
        $department->name = $this->name;
        $department->description = $this->description;
        $department->is_active = $this->is_active;
        $department->save();

        $this->showModal = false;
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $this->authorize('Delete Department');

        $this->deleteId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $this->authorize('Delete Department');

        if (!$this->deleteId) {
            $this->showDeleteModal = false;
            return;
        }

        $department = Department::query()
            ->where('restaurant_id', restaurant()->id)
            ->findOrFail($this->deleteId);
        $department->delete();

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
        $this->name = '';
        $this->description = null;
        $this->is_active = true;
    }

    public function render()
    {
        $departments = Department::query()
            ->where('restaurant_id', restaurant()->id)
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->paginate(15);

        return view('hrm::livewire.departments-list', [
            'departments' => $departments,
        ])->layout('layouts.app');
    }
}
