<?php

namespace App\Livewire\Concerns;

use App\Services\MenuBranchProvisioningService;

trait ManagesMenuBranchSelection
{
    /** @var array<int|string> */
    public array $selectedBranchIds = [];

    /** @var array<int, array{id: int, name: string}> */
    public array $restaurantBranches = [];

    /** Branches that already have this record; cannot be deselected on edit forms. */
    public array $lockedBranchIds = [];

    /** Branches selected only for new provisioning on menu/category edit forms. */
    public array $additionalBranchIds = [];

    protected function initializeMenuBranchSelection(): void
    {
        $branches = app(MenuBranchProvisioningService::class)->restaurantBranches();

        $this->restaurantBranches = $branches
            ->map(fn ($branch) => ['id' => (int) $branch->id, 'name' => $branch->name])
            ->values()
            ->all();

        if ($this->selectedBranchIds === [] && branch()) {
            $this->selectedBranchIds = [(int) branch()->id];
        }
    }

    /**
     * @return array<int>
     */
    protected function normalizeSelectedBranchIds(mixed $value = null): array
    {
        $value = $value ?? $this->selectedBranchIds;

        if (! is_array($value)) {
            return $value === null || $value === '' ? [] : [(int) $value];
        }

        return array_values(array_unique(array_map('intval', $value)));
    }

    public function toggleBranchSelection(int $branchId): void
    {
        $branchId = (int) $branchId;
        $selected = $this->normalizeSelectedBranchIds($this->selectedBranchIds);

        if (in_array($branchId, $selected, true)) {
            $selected = array_values(array_filter(
                $selected,
                fn ($id) => (int) $id !== $branchId
            ));
        } else {
            $selected[] = $branchId;
        }

        $this->selectedBranchIds = array_values(array_unique($selected));
        $this->afterBranchSelectionChanged();
    }

    protected function afterBranchSelectionChanged(): void
    {
        if (method_exists($this, 'syncKitchensForSelectedBranches')) {
            $this->syncKitchensForSelectedBranches();
        }
    }

    public function isBranchSelected(int $branchId): bool
    {
        return in_array($branchId, $this->normalizeSelectedBranchIds($this->selectedBranchIds), true);
    }

    public function isBranchLocked(int $branchId): bool
    {
        return in_array((int) $branchId, $this->lockedBranchIds, true);
    }

    public function toggleAdditionalBranchSelection(int $branchId): void
    {
        $branchId = (int) $branchId;
        $selected = $this->normalizeSelectedBranchIds($this->additionalBranchIds);

        if (in_array($branchId, $selected, true)) {
            $selected = array_values(array_filter(
                $selected,
                fn ($id) => (int) $id !== $branchId
            ));
        } else {
            $selected[] = $branchId;
        }

        $this->additionalBranchIds = array_values(array_unique($selected));
        $this->afterAdditionalBranchSelectionChanged();
    }

    protected function afterAdditionalBranchSelectionChanged(): void
    {
    }

    public function isAdditionalBranchSelected(int $branchId): bool
    {
        return in_array($branchId, $this->normalizeSelectedBranchIds($this->additionalBranchIds), true);
    }

    protected function menuBranchSelectionRules(): array
    {
        $allowed = collect($this->restaurantBranches)->pluck('id')->map(fn ($id) => (int) $id)->all();

        return [
            'selectedBranchIds' => ['required', 'array', 'min:1'],
            'selectedBranchIds.*' => ['integer', 'in:' . implode(',', $allowed)],
        ];
    }

    protected function additionalBranchSelectionRules(): array
    {
        $allowed = collect($this->restaurantBranches)->pluck('id')->map(fn ($id) => (int) $id)->all();

        return [
            'additionalBranchIds' => ['nullable', 'array'],
            'additionalBranchIds.*' => ['integer', 'in:' . implode(',', $allowed)],
        ];
    }
}
