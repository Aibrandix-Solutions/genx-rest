<?php

namespace Modules\Inventory\Services;

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Inventory\Entities\InventoryMovement;
use Modules\Inventory\Entities\InventoryStock;
use Modules\Inventory\Entities\PurchaseLocation;
use Modules\Inventory\Entities\Recipe;

class InventoryOrderStockService
{
    /**
     * Deduct recipe stock for an order (or a subset of order item IDs).
     *
     * @param  array<int>|null  $orderItemIds
     */
    public function deductForOrder(Order $order, ?array $orderItemIds = null): void
    {
        $this->applyForOrder($order, 'out', $orderItemIds);
    }

    /**
     * Restore recipe stock for an order (or a subset of order item IDs).
     *
     * @param  array<int>|null  $orderItemIds
     */
    public function restoreForOrder(Order $order, ?array $orderItemIds = null): void
    {
        $this->applyForOrder($order, 'in', $orderItemIds);
    }

    /**
     * @param  array<int>|null  $orderItemIds
     */
    private function applyForOrder(Order $order, string $transactionType, ?array $orderItemIds = null): void
    {
        $order->loadMissing(['items.modifierOptions']);

        $items = $order->items;
        if ($orderItemIds !== null) {
            $idSet = array_map('intval', $orderItemIds);
            $items = $items->filter(fn (OrderItem $item) => in_array((int) $item->id, $idSet, true));
        }

        foreach ($items as $orderItem) {
            foreach ($this->recipesForOrderItem($orderItem) as $recipe) {
                $quantityNeeded = (float) $recipe->quantity * (float) $orderItem->quantity;

                if ($quantityNeeded <= 0 || ! $recipe->inventory_item_id) {
                    continue;
                }

                try {
                    DB::transaction(function () use ($order, $recipe, $quantityNeeded, $transactionType) {
                        if ($transactionType === 'out') {
                            $moved = $this->deductBranchStock(
                                (int) $order->branch_id,
                                (int) $recipe->inventory_item_id,
                                $quantityNeeded
                            );

                            if ($moved <= 0) {
                                Log::warning('Inventory not deducted: no stock row for branch', [
                                    'order_id' => $order->id,
                                    'branch_id' => $order->branch_id,
                                    'inventory_item_id' => $recipe->inventory_item_id,
                                    'quantity_needed' => $quantityNeeded,
                                ]);

                                return;
                            }

                            if ($moved + 1e-6 < $quantityNeeded) {
                                Log::warning('Inventory partially deducted: insufficient stock', [
                                    'order_id' => $order->id,
                                    'branch_id' => $order->branch_id,
                                    'inventory_item_id' => $recipe->inventory_item_id,
                                    'quantity_needed' => $quantityNeeded,
                                    'quantity_deducted' => $moved,
                                ]);
                            }

                            InventoryMovement::create([
                                'branch_id' => $order->branch_id,
                                'inventory_item_id' => $recipe->inventory_item_id,
                                'quantity' => $moved,
                                'transaction_type' => 'out',
                                'added_by' => auth()->check() ? auth()->id() : null,
                            ]);

                            $this->syncMenuItemStockFlags($recipe);
                        } else {
                            $moved = $this->restoreBranchStock(
                                (int) $order->branch_id,
                                (int) $recipe->inventory_item_id,
                                $quantityNeeded
                            );

                            InventoryMovement::create([
                                'branch_id' => $order->branch_id,
                                'inventory_item_id' => $recipe->inventory_item_id,
                                'quantity' => $moved,
                                'transaction_type' => 'in',
                                'added_by' => auth()->check() ? auth()->id() : null,
                            ]);

                            $this->syncMenuItemStockFlags($recipe, true);
                        }
                    });
                } catch (\Exception $e) {
                    Log::error('Error updating inventory for order: '.$order->id.' - '.$e->getMessage());
                }
            }
        }
    }

    /**
     * Resolve recipes for a sold line:
     * - variation recipes when present, else base (null variation) recipes
     * - plus modifier-option recipes scaled by pivot quantity
     *
     * @return Collection<int, Recipe>
     */
    public function recipesForOrderItem(OrderItem $orderItem): Collection
    {
        $recipes = collect();

        if ($orderItem->menu_item_id) {
            $baseQuery = Recipe::query()->where('menu_item_id', $orderItem->menu_item_id);

            if ($orderItem->menu_item_variation_id) {
                $variationRecipes = (clone $baseQuery)
                    ->where('menu_item_variation_id', $orderItem->menu_item_variation_id)
                    ->get();

                if ($variationRecipes->isNotEmpty()) {
                    $recipes = $recipes->merge($variationRecipes);
                } else {
                    // Fallback: shared base recipe when no variation-specific recipe exists
                    $recipes = $recipes->merge(
                        (clone $baseQuery)->whereNull('menu_item_variation_id')->get()
                    );
                }
            } else {
                $recipes = $recipes->merge(
                    (clone $baseQuery)->whereNull('menu_item_variation_id')->get()
                );
            }
        }

        foreach ($orderItem->modifierOptions ?? [] as $modifierOption) {
            $modifierQty = (int) ($modifierOption->pivot->quantity ?? 1);
            $modifierRecipes = Recipe::query()
                ->where('modifier_option_id', $modifierOption->id)
                ->get();

            foreach ($modifierRecipes as $recipe) {
                $clone = clone $recipe;
                $clone->quantity = (float) $recipe->quantity * max(1, $modifierQty);
                $recipes->push($clone);
            }
        }

        return $recipes;
    }

    /**
     * @return float Quantity actually deducted
     */
    public function deductBranchStock(int $branchId, int $inventoryItemId, float $quantityNeeded): float
    {
        $remaining = $quantityNeeded;
        $deducted = 0.0;

        foreach ($this->lockStockRows($branchId, $inventoryItemId, true) as $stock) {
            if ($remaining <= 1e-6) {
                break;
            }

            $take = min((float) $stock->quantity, $remaining);
            if ($take <= 0) {
                continue;
            }

            $stock->quantity = (float) $stock->quantity - $take;
            $stock->save();

            $remaining -= $take;
            $deducted += $take;
        }

        return $deducted;
    }

    /**
     * Restore stock onto the preferred branch location row (or create one).
     *
     * @return float Quantity restored
     */
    public function restoreBranchStock(int $branchId, int $inventoryItemId, float $quantityNeeded): float
    {
        if ($quantityNeeded <= 0) {
            return 0.0;
        }

        $location = PurchaseLocation::query()
            ->where('branch_id', $branchId)
            ->where('type', 'branch')
            ->where('is_active', true)
            ->first();

        $stock = null;

        if ($location) {
            $stock = InventoryStock::query()
                ->where('branch_id', $branchId)
                ->where('inventory_item_id', $inventoryItemId)
                ->where('location_id', $location->id)
                ->lockForUpdate()
                ->first();
        }

        if (! $stock) {
            $stock = InventoryStock::query()
                ->where('branch_id', $branchId)
                ->where('inventory_item_id', $inventoryItemId)
                ->orderByDesc('quantity')
                ->lockForUpdate()
                ->first();
        }

        if (! $stock) {
            $stock = InventoryStock::create([
                'inventory_item_id' => $inventoryItemId,
                'branch_id' => $branchId,
                'location_id' => $location?->id,
                'quantity' => 0,
            ]);
            $stock = InventoryStock::query()->whereKey($stock->id)->lockForUpdate()->first();
        }

        $stock->quantity = (float) $stock->quantity + $quantityNeeded;
        $stock->save();

        return $quantityNeeded;
    }

    /**
     * @return Collection<int, InventoryStock>
     */
    private function lockStockRows(int $branchId, int $inventoryItemId, bool $positiveOnly = false): Collection
    {
        $location = PurchaseLocation::query()
            ->where('branch_id', $branchId)
            ->where('type', 'branch')
            ->where('is_active', true)
            ->first();

        $query = InventoryStock::query()
            ->where('branch_id', $branchId)
            ->where('inventory_item_id', $inventoryItemId);

        if ($positiveOnly) {
            $query->where('quantity', '>', 0);
        }

        if ($location) {
            return $query
                ->orderByRaw('CASE WHEN location_id = ? THEN 0 ELSE 1 END', [$location->id])
                ->orderByDesc('quantity')
                ->lockForUpdate()
                ->get();
        }

        return $query
            ->orderByDesc('quantity')
            ->lockForUpdate()
            ->get();
    }

    private function syncMenuItemStockFlags(Recipe $recipe, bool $forceInStock = false): void
    {
        $recipe->loadMissing('inventoryItem');

        if (! $recipe->inventoryItem) {
            return;
        }

        if ($forceInStock) {
            foreach ($recipe->inventoryItem->menuItems as $menuItem) {
                if ($menuItem->in_stock) {
                    continue;
                }

                // Only mark available when every recipe ingredient has stock.
                if ($this->menuItemHasAllIngredientsInStock($menuItem)) {
                    $menuItem->update(['in_stock' => 1]);
                }
            }

            return;
        }

        if ($recipe->inventoryItem->current_stock > 0) {
            return;
        }

        foreach ($recipe->inventoryItem->menuItems as $menuItem) {
            $menuItem->update(['in_stock' => 0]);
        }
    }

    private function menuItemHasAllIngredientsInStock(MenuItem $menuItem): bool
    {
        $recipes = Recipe::query()
            ->where('menu_item_id', $menuItem->id)
            ->with('inventoryItem')
            ->get();

        if ($recipes->isEmpty()) {
            return true;
        }

        foreach ($recipes as $ingredientRecipe) {
            $stock = $ingredientRecipe->inventoryItem?->current_stock ?? 0;
            if ($stock <= 0) {
                return false;
            }
        }

        return true;
    }
}
