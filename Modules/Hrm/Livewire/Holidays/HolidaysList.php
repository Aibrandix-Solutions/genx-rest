<?php

namespace Modules\Hrm\Livewire\Holidays;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Hrm\Entities\Holiday;

class HolidaysList extends Component
{
    use WithPagination, AuthorizesRequests;

    public string $search = '';
    public ?string $from = null;
    public ?string $to = null;

    public array $branches = [];
    public string $branchFilter = ''; // ''=all, '0'=global, '{id}'=branch

    public bool $showModal = false;
    public ?int $editingId = null;

    public ?string $date = null;
    public string $name = '';
    public ?int $branch_id = null; // null = global
    public ?string $note = null;

    public bool $showDeleteModal = false;
    public ?int $deleteId = null;

    protected $queryString = ['search', 'from', 'to', 'branchFilter'];

    public function mount(): void
    {
        $this->branches = DB::table('branches')
            ->select('id', 'name')
            ->where('restaurant_id', restaurant()->id)
            ->orderBy('name')
            ->get()
            ->map(fn ($b) => ['id' => $b->id, 'name' => $b->name])
            ->all();
    }

    public function updating($name, $value): void
    {
        if (in_array($name, ['search', 'from', 'to', 'branchFilter'], true)) {
            $this->resetPage();
        }
    }

    public function create(): void
    {
        $this->authorize('Manage Holidays');

        $this->resetForm();
        $this->date = now()->toDateString();
        $this->branch_id = null;
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $this->authorize('Manage Holidays');

        $h = Holiday::query()
            ->where('restaurant_id', restaurant()->id)
            ->findOrFail($id);

        $this->editingId = $h->id;
        $this->date = $h->date?->toDateString();
        $this->name = (string) $h->name;
        $this->branch_id = $h->branch_id ? (int) $h->branch_id : null;
        $this->note = $h->note;

        $this->showModal = true;
    }

    public function save(): void
    {
        $this->authorize('Manage Holidays');

        $this->validate([
            'date' => ['required', 'date'],
            'name' => ['required', 'string', 'max:255'],
            'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')->where(fn ($q) => $q->where('restaurant_id', restaurant()->id))],
            'note' => ['nullable', 'string'],
        ]);

        $h = $this->editingId
            ? Holiday::query()->where('restaurant_id', restaurant()->id)->findOrFail($this->editingId)
            : new Holiday();

        $h->restaurant_id = $h->restaurant_id ?: restaurant()->id;
        if ((int) $h->restaurant_id !== (int) restaurant()->id) {
            abort(403);
        }
        $h->branch_id = $this->branch_id ? (int) $this->branch_id : null;
        $h->date = $this->date;
        $h->name = $this->name;
        $h->note = $this->note;
        try {
            $h->save();
        } catch (QueryException $e) {
            if ((int) $e->getCode() === 23000) {
                $this->addError('name', 'Holiday already exists for this date and scope.');
                return;
            }
            throw $e;
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $this->authorize('Manage Holidays');

        $this->deleteId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $this->authorize('Manage Holidays');

        if (!$this->deleteId) {
            $this->showDeleteModal = false;
            return;
        }

        Holiday::query()
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
        $this->date = null;
        $this->name = '';
        $this->branch_id = null;
        $this->note = null;
    }

    public function render()
    {
        $rows = Holiday::query()
            ->where('restaurant_id', restaurant()->id)
            ->when($this->from, fn ($q) => $q->whereDate('date', '>=', $this->from))
            ->when($this->to, fn ($q) => $q->whereDate('date', '<=', $this->to))
            ->when($this->branchFilter !== '', function ($q) {
                if ($this->branchFilter === '0') {
                    $q->whereNull('branch_id');
                } else {
                    $q->where('branch_id', (int) $this->branchFilter);
                }
            })
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderByDesc('date')
            ->paginate(15);

        $branchMap = collect($this->branches)->keyBy('id');

        return view('hrm::livewire.holidays.holidays-list', [
            'rows' => $rows,
            'branchMap' => $branchMap,
        ])->layout('layouts.app');
    }
}
