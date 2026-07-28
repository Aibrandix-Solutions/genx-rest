<?php

namespace Modules\Hrm\Livewire\Departments;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Hrm\Entities\Department;
use Modules\Hrm\Support\Workplace;

class DepartmentsList extends Component
{
    use WithPagination, AuthorizesRequests;

    public string $search = '';

    public string $workplaceFilter = 'all';

    public bool $showModal = false;
    public ?int $editingId = null;

    public string $name = '';
    public string $workplace = Workplace::RESTAURANT;
    public ?string $description = null;
    public bool $is_active = true;

    public bool $showDeleteModal = false;
    public ?int $deleteId = null;

    protected $queryString = ['search', 'workplaceFilter'];

    public function mount(): void
    {
        $this->workplace = Workplace::default();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingWorkplaceFilter(): void
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
        $this->workplace = Workplace::normalize($department->workplace);
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

        $this->workplace = Workplace::normalize($this->workplace);

        $this->validate([
            'workplace' => ['required', Rule::in(Workplace::allowedValues())],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('hrm_departments', 'name')
                    ->where(fn ($q) => $q
                        ->where('restaurant_id', restaurant()->id)
                        ->where('workplace', $this->workplace))
                    ->ignore($this->editingId),
            ],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $department = $this->editingId
            ? Department::query()->where('restaurant_id', restaurant()->id)->findOrFail($this->editingId)
            : new Department();

        $department->restaurant_id = restaurant()->id;
        $department->workplace = $this->workplace;
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
        $this->workplace = Workplace::default();
        $this->description = null;
        $this->is_active = true;
    }

    public function render()
    {
        $workplaceOptions = Workplace::options();

        $departments = Department::query()
            ->where('restaurant_id', restaurant()->id)
            ->when(
                $this->workplaceFilter !== 'all' && isset($workplaceOptions[$this->workplaceFilter]),
                fn ($q) => $q->where('workplace', $this->workplaceFilter)
            )
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy('workplace')
            ->orderBy('name')
            ->paginate(15);

        return view('hrm::livewire.departments-list', [
            'departments' => $departments,
            'workplaceOptions' => $workplaceOptions,
            'showWorkplaceFilter' => count($workplaceOptions) > 1,
        ])->layout('layouts.app');
    }
}
