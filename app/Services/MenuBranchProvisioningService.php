<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\ItemCategory;
use App\Models\KotPlace;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\MenuItemPrices;
use App\Models\MenuItemTranslation;
use App\Models\MenuItemVariation;
use App\Scopes\BranchScope;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MenuBranchProvisioningService
{
    public function restaurantBranches(?int $restaurantId = null): Collection
    {
        $restaurantId = $restaurantId ?? restaurant()->id;

        return Branch::query()
            ->where('restaurant_id', $restaurantId)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * @param  array<int|string>  $branchIds
     * @return array<int>
     */
    public function validateBranchIds(array $branchIds, ?int $restaurantId = null): array
    {
        $allowed = $this->restaurantBranches($restaurantId)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $valid = array_values(array_unique(array_intersect(
            array_map('intval', $branchIds),
            $allowed
        )));

        if ($valid === []) {
            throw ValidationException::withMessages([
                'selectedBranchIds' => __('validation.menuBranchRequired'),
            ]);
        }

        return $valid;
    }

    public function newCatalogGroupUuid(): string
    {
        return (string) Str::uuid();
    }

    public function ensureCatalogGroupUuid(Menu|ItemCategory|MenuItem $model): string
    {
        if (! empty($model->catalog_group_uuid)) {
            return $model->catalog_group_uuid;
        }

        $uuid = $this->newCatalogGroupUuid();
        $model->withoutEvents(function () use ($model, $uuid) {
            $model->update(['catalog_group_uuid' => $uuid]);
        });

        return $uuid;
    }

    /**
     * @param  array<int|string>  $branchIds
     * @return array<int, int> branch_id => menu_id
     */
    public function provisionMenus(array $translations, array $branchIds, ?string $catalogGroupUuid = null): array
    {
        $branchIds = $this->validateBranchIds($branchIds);
        $groupUuid = $catalogGroupUuid ?? $this->newCatalogGroupUuid();
        $map = [];

        foreach ($branchIds as $branchId) {
            $menu = $this->createOrReuseMenu($groupUuid, (int) $branchId, [
                'menu_name' => $translations,
            ]);
            $map[$branchId] = $menu->id;
        }

        return $map;
    }

    /**
     * @param  array<int|string>  $branchIds
     * @return array<int, int> branch_id => category_id
     */
    public function provisionCategories(array $translations, array $branchIds, ?string $catalogGroupUuid = null): array
    {
        $branchIds = $this->validateBranchIds($branchIds);
        $groupUuid = $catalogGroupUuid ?? $this->newCatalogGroupUuid();
        $map = [];

        foreach ($branchIds as $branchId) {
            $category = $this->createOrReuseCategory($groupUuid, (int) $branchId, [
                'category_name' => $translations,
            ]);
            $map[$branchId] = $category->id;
        }

        return $map;
    }

    public function resolveMenuIdForBranch(Menu $sourceMenu, int $targetBranchId): int
    {
        $groupUuid = $this->ensureCatalogGroupUuid($sourceMenu);

        return $this->createOrReuseMenu($groupUuid, $targetBranchId, [
            'menu_name' => $sourceMenu->getTranslations('menu_name'),
            'sort_order' => $sourceMenu->sort_order,
        ])->id;
    }

    public function resolveCategoryIdForBranch(ItemCategory $sourceCategory, int $targetBranchId): int
    {
        $groupUuid = $this->ensureCatalogGroupUuid($sourceCategory);

        return $this->createOrReuseCategory($groupUuid, $targetBranchId, [
            'category_name' => $sourceCategory->getTranslations('category_name'),
            'sort_order' => $sourceCategory->sort_order ?? null,
        ])->id;
    }

    /**
     * @param  array<int|string>  $kitchenIds
     */
    public function validateKitchenIdsForBranch(int $branchId, array $kitchenIds): array
    {
        $kitchenIds = array_values(array_unique(array_map('intval', $kitchenIds)));

        if ($kitchenIds === []) {
            return [];
        }

        $valid = KotPlace::withoutGlobalScope(BranchScope::class)
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->whereIn('id', $kitchenIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (count($valid) !== count($kitchenIds)) {
            throw ValidationException::withMessages([
                'selectedKitchensByBranch' => __('validation.kitchenTypeInvalid'),
            ]);
        }

        return $valid;
    }

    /**
     * @return Collection<int, KotPlace>
     */
    public function kitchensForBranches(array $branchIds): Collection
    {
        $branchIds = $this->validateBranchIds($branchIds);

        return KotPlace::withoutGlobalScope(BranchScope::class)
            ->whereIn('branch_id', $branchIds)
            ->where('is_active', true)
            ->orderBy('branch_id')
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  array<int, array<int|string>>  $kitchensByBranch
     */
    public function replicateMenuItemToBranch(
        MenuItem $sourceItem,
        int $targetBranchId,
        array $kitchensByBranch,
        ?string $catalogGroupUuid = null
    ): MenuItem {
        $sourceItem->loadMissing(['translations', 'variations', 'prices', 'taxes', 'kotPlaces']);

        $groupUuid = $catalogGroupUuid ?? $this->ensureCatalogGroupUuid($sourceItem);

        if ($existing = $this->findMenuItemByCatalogGroup($groupUuid, $targetBranchId)) {
            return $existing;
        }

        $sourceMenu = Menu::withoutGlobalScope(BranchScope::class)->find($sourceItem->menu_id);
        $sourceCategory = $sourceItem->item_category_id
            ? ItemCategory::withoutGlobalScope(BranchScope::class)->find($sourceItem->item_category_id)
            : null;

        if (! $sourceMenu) {
            throw ValidationException::withMessages([
                'menu' => __('validation.menuNotFoundForBranchProvisioning'),
            ]);
        }

        if ($sourceItem->item_category_id && ! $sourceCategory) {
            throw ValidationException::withMessages([
                'itemCategory' => __('validation.categoryNotFoundForBranchProvisioning'),
            ]);
        }

        $menuId = $this->resolveMenuIdForBranch($sourceMenu, $targetBranchId);
        $categoryId = $sourceCategory
            ? $this->resolveCategoryIdForBranch($sourceCategory, $targetBranchId)
            : null;

        $kitchenIds = $this->validateKitchenIdsForBranch(
            $targetBranchId,
            $kitchensByBranch[$targetBranchId] ?? $kitchensByBranch[(string) $targetBranchId] ?? []
        );

        $maxAttempts = 15;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                return DB::transaction(function () use ($sourceItem, $targetBranchId, $menuId, $categoryId, $kitchenIds, $groupUuid) {
                    $itemCode = MenuItem::generateNextItemCodeForBranch($targetBranchId);

                    try {
                        $clone = MenuItem::withoutEvents(function () use ($sourceItem, $targetBranchId, $menuId, $categoryId, $itemCode, $groupUuid, $kitchenIds) {
                            $attributes = collect($sourceItem->getAttributes())
                                ->except(['id', 'created_at', 'updated_at', 'branch_id', 'menu_id', 'item_category_id', 'item_code', 'catalog_group_uuid', 'kot_place_id'])
                                ->all();

                            return MenuItem::withoutGlobalScopes()->create(array_merge($attributes, [
                                'branch_id' => $targetBranchId,
                                'menu_id' => $menuId,
                                'item_category_id' => $categoryId,
                                'item_code' => $itemCode,
                                'catalog_group_uuid' => $groupUuid,
                                'kot_place_id' => $kitchenIds[0] ?? null,
                            ]));
                        });
                    } catch (QueryException $e) {
                        if ($this->isDuplicateCatalogGroupBranchException($e)) {
                            if ($existing = $this->findMenuItemByCatalogGroup($groupUuid, $targetBranchId)) {
                                return $existing;
                            }
                        }

                        throw $e;
                    }

                    foreach ($sourceItem->translations as $translation) {
                        MenuItemTranslation::create([
                            'menu_item_id' => $clone->id,
                            'locale' => $translation->locale,
                            'item_name' => $translation->item_name,
                            'description' => $translation->description,
                        ]);
                    }

                    $variationMap = [];
                    foreach ($sourceItem->variations as $variation) {
                        $newVariation = MenuItemVariation::create([
                            'menu_item_id' => $clone->id,
                            'variation' => $variation->variation,
                            'price' => $variation->price,
                        ]);
                        $variationMap[$variation->id] = $newVariation->id;
                    }

                    foreach ($sourceItem->prices as $price) {
                        MenuItemPrices::create([
                            'menu_item_id' => $clone->id,
                            'order_type_id' => $price->order_type_id,
                            'delivery_app_id' => $price->delivery_app_id,
                            'menu_item_variation_id' => $price->menu_item_variation_id
                                ? ($variationMap[$price->menu_item_variation_id] ?? null)
                                : null,
                            'calculated_price' => $price->calculated_price,
                            'final_price' => $price->final_price,
                            'status' => $price->status,
                            'override_price' => $price->override_price,
                        ]);
                    }

                    if ($sourceItem->taxes->isNotEmpty()) {
                        $clone->taxes()->sync($sourceItem->taxes->pluck('id')->all());
                    }

                    if ($kitchenIds !== []) {
                        $pivotData = [];
                        foreach ($kitchenIds as $index => $kitchenId) {
                            $pivotData[$kitchenId] = ['is_primary' => $index === 0];
                        }
                        $clone->kotPlaces()->sync($pivotData);
                    }

                    return $clone->fresh();
                });
            } catch (QueryException $e) {
                if (MenuItem::isDuplicateBranchItemCodeException($e) && $attempt < $maxAttempts) {
                    continue;
                }

                throw $e;
            }
        }

        throw new \RuntimeException('Unable to allocate a unique item code for branch '.$targetBranchId);
    }

    /**
     * @param  array<int|string>  $kitchenIds
     */
    public function syncKitchensForMenuItem(MenuItem $menuItem, array $kitchenIds): void
    {
        $kitchenIds = $this->validateKitchenIdsForBranch((int) $menuItem->branch_id, $kitchenIds);

        $menuItem->update(['kot_place_id' => $kitchenIds[0] ?? null]);

        if ($kitchenIds === []) {
            $menuItem->kotPlaces()->detach();

            return;
        }

        $pivotData = [];
        foreach ($kitchenIds as $index => $kitchenId) {
            $pivotData[$kitchenId] = ['is_primary' => $index === 0];
        }

        $menuItem->kotPlaces()->sync($pivotData);
    }

    /**
     * @param  array<int|string>  $selectedBranchIds
     * @param  array<int, array<int|string>>  $kitchensByBranch
     * @param  array<int>  $existingBranchIds
     */
    public function provisionMenuItemToSelectedBranches(
        MenuItem $sourceItem,
        array $selectedBranchIds,
        array $kitchensByBranch,
        array $existingBranchIds
    ): void {
        $groupUuid = $this->ensureCatalogGroupUuid($sourceItem);
        $selectedBranchIds = $this->validateBranchIds($selectedBranchIds);
        $existingBranchIds = array_map('intval', $existingBranchIds);

        foreach ($selectedBranchIds as $branchId) {
            $branchId = (int) $branchId;

            if (in_array($branchId, $existingBranchIds, true)) {
                $sibling = $branchId === (int) $sourceItem->branch_id
                    ? $sourceItem
                    : $this->findMenuItemByCatalogGroup($groupUuid, $branchId);

                if ($sibling) {
                    $rawKitchens = $kitchensByBranch[$branchId] ?? $kitchensByBranch[(string) $branchId] ?? [];
                    $kitchenIds = $this->validateKitchenIdsForBranch($branchId, array_map('intval', (array) $rawKitchens));
                    $this->syncKitchensForMenuItem($sibling, $kitchenIds);
                }

                continue;
            }

            $this->replicateMenuItemToBranch($sourceItem, $branchId, $kitchensByBranch, $groupUuid);
        }

        $toRemove = array_values(array_diff($existingBranchIds, $selectedBranchIds));

        foreach ($toRemove as $branchId) {
            $branchId = (int) $branchId;

            if ($branchId === (int) $sourceItem->branch_id) {
                continue;
            }

            $sibling = $this->findMenuItemByCatalogGroup($groupUuid, $branchId);

            if ($sibling) {
                $sibling->delete();
            }
        }

        if (in_array((int) $sourceItem->branch_id, $toRemove, true)) {
            $sourceItem->delete();
        }
    }

    /**
     * @return array<int, MenuItem>
     */
    public function siblingsInRestaurant(MenuItem $menuItem): array
    {
        if (! $menuItem->catalog_group_uuid) {
            return [(int) $menuItem->branch_id => $menuItem];
        }

        return MenuItem::withoutGlobalScopes()
            ->where('catalog_group_uuid', $menuItem->catalog_group_uuid)
            ->whereHas('branch', fn ($q) => $q->where('restaurant_id', restaurant()->id))
            ->with('branch:id,name')
            ->get()
            ->keyBy('branch_id')
            ->all();
    }

    protected function findMenuByCatalogGroup(string $groupUuid, int $branchId): ?Menu
    {
        return Menu::withoutGlobalScope(BranchScope::class)
            ->where('catalog_group_uuid', $groupUuid)
            ->where('branch_id', $branchId)
            ->first();
    }

    protected function createOrReuseMenu(string $groupUuid, int $branchId, array $attributes): Menu
    {
        if ($existing = $this->findMenuByCatalogGroup($groupUuid, $branchId)) {
            return $existing;
        }

        try {
            return Menu::withoutEvents(function () use ($branchId, $attributes, $groupUuid) {
                return Menu::withoutGlobalScope(BranchScope::class)->create(array_merge($attributes, [
                    'branch_id' => $branchId,
                    'catalog_group_uuid' => $groupUuid,
                ]));
            });
        } catch (QueryException $e) {
            if ($this->isDuplicateCatalogGroupBranchException($e)) {
                if ($existing = $this->findMenuByCatalogGroup($groupUuid, $branchId)) {
                    return $existing;
                }
            }

            throw $e;
        }
    }

    protected function findCategoryByCatalogGroup(string $groupUuid, int $branchId): ?ItemCategory
    {
        return ItemCategory::withoutGlobalScope(BranchScope::class)
            ->where('catalog_group_uuid', $groupUuid)
            ->where('branch_id', $branchId)
            ->first();
    }

    protected function createOrReuseCategory(string $groupUuid, int $branchId, array $attributes): ItemCategory
    {
        if ($existing = $this->findCategoryByCatalogGroup($groupUuid, $branchId)) {
            return $existing;
        }

        try {
            return ItemCategory::withoutEvents(function () use ($branchId, $attributes, $groupUuid) {
                return ItemCategory::withoutGlobalScope(BranchScope::class)->create(array_merge($attributes, [
                    'branch_id' => $branchId,
                    'catalog_group_uuid' => $groupUuid,
                ]));
            });
        } catch (QueryException $e) {
            if ($this->isDuplicateCatalogGroupBranchException($e)) {
                if ($existing = $this->findCategoryByCatalogGroup($groupUuid, $branchId)) {
                    return $existing;
                }
            }

            throw $e;
        }
    }

    protected function isDuplicateCatalogGroupBranchException(QueryException $e): bool
    {
        $msg = strtolower($e->getMessage());
        $sqlState = $e->errorInfo[0] ?? '';

        if (! in_array($sqlState, ['23000', '23505'], true)) {
            return false;
        }

        $driverCode = isset($e->errorInfo[1]) ? (int) $e->errorInfo[1] : 0;
        $looksDuplicate = in_array($driverCode, [1062, 19], true)
            || str_contains($msg, 'unique')
            || str_contains($msg, 'duplicate');

        if (! $looksDuplicate) {
            return false;
        }

        return str_contains($msg, 'catalog_group_uuid')
            || str_contains($msg, 'branch_catalog_group_unique');
    }

    protected function findMenuItemByCatalogGroup(string $groupUuid, int $branchId): ?MenuItem
    {
        return MenuItem::withoutGlobalScopes()
            ->where('catalog_group_uuid', $groupUuid)
            ->where('branch_id', $branchId)
            ->first();
    }
}
