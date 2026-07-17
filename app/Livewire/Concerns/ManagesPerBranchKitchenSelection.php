<?php

namespace App\Livewire\Concerns;

use App\Models\MenuItem;
use App\Services\MenuBranchProvisioningService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;

trait ManagesPerBranchKitchenSelection
{
    /** @var array<string, array<int|string>> */
    public array $selectedKitchensByBranch = [];

    /** @var array<int, \Illuminate\Support\Collection|array> */
    public array $kitchensByBranch = [];

    public $kitchenTypes;

    public function toggleKitchenForBranch(int $branchId, int $kitchenId): void
    {
        $kitchenId = (int) $kitchenId;
        $branchId = (int) $branchId;
        $selected = $this->selectedKitchensByBranch[$branchId]
            ?? $this->selectedKitchensByBranch[(string) $branchId]
            ?? [];

        $selected = array_map('intval', (array) $selected);

        if (in_array($kitchenId, $selected, true)) {
            $selected = array_values(array_filter(
                $selected,
                fn ($id) => (int) $id !== $kitchenId
            ));
        } else {
            $selected[] = $kitchenId;
        }

        $this->selectedKitchensByBranch[$branchId] = $selected;
    }

    public function isKitchenSelected(int $branchId, int $kitchenId): bool
    {
        $selected = $this->selectedKitchensByBranch[$branchId]
            ?? $this->selectedKitchensByBranch[(string) $branchId]
            ?? [];

        return in_array($kitchenId, array_map('intval', (array) $selected), true);
    }

    public function updatedSelectedBranchIds(): void
    {
        $this->selectedBranchIds = $this->normalizeSelectedBranchIds($this->selectedBranchIds);
        $this->syncKitchensForSelectedBranches();
    }

    protected function syncKitchensForSelectedBranches(): void
    {
        $selected = array_map('strval', $this->normalizeSelectedBranchIds());

        foreach (array_keys($this->selectedKitchensByBranch) as $branchId) {
            if (! in_array((string) $branchId, $selected, true)) {
                unset($this->selectedKitchensByBranch[$branchId]);
            }
        }

        foreach ($this->normalizeSelectedBranchIds() as $branchId) {
            if (! array_key_exists($branchId, $this->selectedKitchensByBranch)) {
                $this->selectedKitchensByBranch[$branchId] = [];
            }
        }

        $this->refreshKitchensByBranch();
    }

    #[Computed]
    public function branchesWithKitchenOptions(): array
    {
        $options = [];

        foreach ($this->normalizeSelectedBranchIds() as $branchId) {
            $kitchens = $this->kitchensByBranch[$branchId]
                ?? $this->kitchensByBranch[(string) $branchId]
                ?? [];

            $branchName = collect($this->restaurantBranches)
                ->firstWhere('id', $branchId)['name'] ?? '';

            $options[$branchId] = [
                'name' => $branchName,
                'kitchens' => collect($kitchens)->map(fn ($kitchen) => [
                    'id' => (int) $kitchen['id'],
                    'name' => $kitchen['name'],
                    'selected' => $this->isKitchenSelected($branchId, (int) $kitchen['id']),
                ])->values()->all(),
            ];
        }

        return $options;
    }

    protected function refreshKitchensByBranch(): void
    {
        $branchIds = $this->normalizeSelectedBranchIds();

        if ($branchIds === []) {
            $this->kitchenTypes = collect();
            $this->kitchensByBranch = [];

            return;
        }

        $kitchens = app(MenuBranchProvisioningService::class)
            ->kitchensForBranches($branchIds);

        $this->kitchenTypes = $kitchens;
        $this->kitchensByBranch = $kitchens->groupBy('branch_id')
            ->map(fn ($group) => $group->map(fn ($kitchen) => [
                'id' => (int) $kitchen->id,
                'name' => $kitchen->name,
            ])->values()->all())
            ->all();

        $this->autoSelectSingleKitchensForBranches($branchIds);
    }

    /**
     * @param  array<int>  $branchIds
     */
    protected function autoSelectSingleKitchensForBranches(array $branchIds): void
    {
        foreach ($branchIds as $branchId) {
            $kitchens = $this->kitchensByBranch[$branchId]
                ?? $this->kitchensByBranch[(string) $branchId]
                ?? [];

            if (count($kitchens) !== 1) {
                continue;
            }

            $selected = $this->selectedKitchensByBranch[$branchId]
                ?? $this->selectedKitchensByBranch[(string) $branchId]
                ?? [];

            if ($selected === []) {
                $this->selectedKitchensByBranch[$branchId] = [(int) $kitchens[0]['id']];
            }
        }
    }

    /**
     * @return array<int>
     */
    protected function kitchenIdsForBranch(int $branchId): array
    {
        $raw = $this->selectedKitchensByBranch[(string) $branchId]
            ?? $this->selectedKitchensByBranch[$branchId]
            ?? [];

        return app(MenuBranchProvisioningService::class)->validateKitchenIdsForBranch(
            $branchId,
            array_map('intval', $raw)
        );
    }

    /** Branches that already had this item when the edit form opened. */
    public array $existingBranchIdsAtEdit = [];

    protected function initializeLinkedBranchKitchenSelections(MenuItem $menuItem): void
    {
        $siblings = app(MenuBranchProvisioningService::class)->siblingsInRestaurant($menuItem);

        $this->existingBranchIdsAtEdit = array_map('intval', array_keys($siblings));
        $this->selectedBranchIds = $this->existingBranchIdsAtEdit;

        foreach ($siblings as $branchId => $sibling) {
            $branchId = (int) $branchId;
            $pivotIds = $sibling->kotPlaces()->pluck('kot_places.id')->map(fn ($id) => (int) $id)->all();

            if ($pivotIds === [] && $sibling->kot_place_id) {
                $pivotIds = [(int) $sibling->kot_place_id];
            }

            $this->selectedKitchensByBranch[$branchId] = $pivotIds;
        }

        $this->refreshKitchensByBranch();
    }

    /**
     * @param  array<int>  $branchIds
     */
    protected function validateKitchenSelectionsForBranches(array $branchIds): bool
    {
        if (! in_array('Kitchen', restaurant_modules(), true)) {
            return true;
        }

        foreach ($branchIds as $branchId) {
            $availableKitchens = $this->kitchensByBranch[$branchId]
                ?? $this->kitchensByBranch[(string) $branchId]
                ?? [];

            if ($availableKitchens === []) {
                continue;
            }

            try {
                $kitchenIds = $this->kitchenIdsForBranch((int) $branchId);
                if ($kitchenIds === []) {
                    $branchName = collect($this->restaurantBranches)
                        ->firstWhere('id', (int) $branchId)['name'] ?? '';
                    $this->addError(
                        'selectedKitchensByBranch.' . $branchId,
                        __('validation.kitchenRequiredForBranchNamed', ['branch' => $branchName])
                    );
                }
            } catch (ValidationException $e) {
                foreach ($e->errors() as $messages) {
                    foreach ((array) $messages as $message) {
                        $this->addError('selectedKitchensByBranch.' . $branchId, $message);
                    }
                }
            }
        }

        return $this->getErrorBag()->isEmpty();
    }

    protected function syncKitchensToMenuItem(MenuItem $menuItem, int $branchId): void
    {
        $kitchenIds = $this->kitchenIdsForBranch($branchId);

        app(MenuBranchProvisioningService::class)->syncKitchensForMenuItem($menuItem, $kitchenIds);
    }

    protected function provisionMenuItemBranchesOnEdit(MenuItem $menuItem): void
    {
        $branchIds = app(MenuBranchProvisioningService::class)->validateBranchIds($this->selectedBranchIds);

        app(MenuBranchProvisioningService::class)->provisionMenuItemToSelectedBranches(
            $menuItem,
            $branchIds,
            $this->selectedKitchensByBranch,
            $this->existingBranchIdsAtEdit
        );
    }

    /**
     * When the edited item's branch was unchecked, continue saving against a remaining sibling.
     */
    protected function ensureEditMenuItemOnSelectedBranch(MenuItem $menuItem): MenuItem
    {
        $branchIds = app(MenuBranchProvisioningService::class)->validateBranchIds($this->selectedBranchIds);

        if (in_array((int) $menuItem->branch_id, $branchIds, true)) {
            return $menuItem;
        }

        $siblings = app(MenuBranchProvisioningService::class)->siblingsInRestaurant($menuItem);

        foreach ($branchIds as $branchId) {
            if (isset($siblings[$branchId])) {
                return $siblings[$branchId];
            }
        }

        return $menuItem;
    }
}
