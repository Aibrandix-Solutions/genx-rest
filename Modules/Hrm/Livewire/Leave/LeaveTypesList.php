<?php

namespace Modules\Hrm\Livewire\Leave;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Hrm\Entities\LeaveType;

class LeaveTypesList extends Component
{
    use WithPagination, AuthorizesRequests;

    public string $search = '';

    public bool $showModal = false;
    public ?int $editingId = null;

    public string $name = '';
    public int $max_per_year = 0;
    public bool $is_paid = true;
    public bool $is_active = true;
    public ?string $note = null;

    public bool $showDeleteModal = false;
    public ?int $deleteId = null;

    protected $queryString = ['search'];

    public function updating($name, $value): void
    {
        if (in_array($name, ['search'], true)) {
            $this->resetPage();
        }
    }

    public function create(): void
    {
        $this->authorize('Manage Leave Types');

        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $this->authorize('Manage Leave Types');

        $t = LeaveType::query()
            ->where('restaurant_id', restaurant()->id)
            ->findOrFail($id);

        $this->editingId = $t->id;
        $this->name = (string) $t->name;
        $this->max_per_year = (int) $t->max_per_year;
        $this->is_paid = (bool) $t->is_paid;
        $this->is_active = (bool) $t->is_active;
        $this->note = $t->note;

        $this->showModal = true;
    }

    public function save(): void
    {
        $this->authorize('Manage Leave Types');

        $this->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('hrm_leave_types', 'name')
                    ->where(fn($q) => $q->where('restaurant_id', restaurant()->id))
                    ->ignore($this->editingId),
            ],
            'max_per_year' => ['required', 'integer', 'min:0'],
            'is_paid' => ['boolean'],
            'is_active' => ['boolean'],
            'note' => ['nullable', 'string'],
        ]);

        $t = $this->editingId
            ? LeaveType::query()->where('restaurant_id', restaurant()->id)->findOrFail($this->editingId)
            : new LeaveType();

        $t->restaurant_id = restaurant()->id;
        $t->name = $this->name;
        $t->max_per_year = $this->max_per_year;
        $t->is_paid = $this->is_paid;
        $t->is_active = $this->is_active;
        $t->note = $this->note;
        $t->save();

        $this->showModal = false;
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $this->authorize('Manage Leave Types');

        $this->deleteId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $this->authorize('Manage Leave Types');

        if (!$this->deleteId) {
            $this->showDeleteModal = false;
            return;
        }

        LeaveType::query()
            ->where('restaurant_id', restaurant()->id)
            ->where('id', $this->deleteId)
            ->delete();

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
        $this->max_per_year = 0;
        $this->is_paid = true;
        $this->is_active = true;
        $this->note = null;
    }

    public function render()
    {
        $types = LeaveType::query()
            ->where('restaurant_id', restaurant()->id)
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->paginate(15);

        return view('hrm::livewire.leave.leave-types-list', [
            'types' => $types,
        ])->layout('layouts.app');
    }
}
