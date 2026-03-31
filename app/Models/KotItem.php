<?php

namespace App\Models;

use App\Scopes\AvailableMenuItemScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\BaseModel;

/**
 * @property int|null $order_type_id
 * @property string|null $order_type
 */
class KotItem extends BaseModel
{
    use HasFactory;

    protected $guarded = ['id'];

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class)->withoutGlobalScope(AvailableMenuItemScope::class);
    }

    public function menuItemVariation(): BelongsTo
    {
        return $this->belongsTo(MenuItemVariation::class);
    }

    public function modifierOptions(): BelongsToMany
    {
        return $this->belongsToMany(ModifierOption::class, 'kot_item_modifier_options', 'kot_item_id', 'modifier_option_id')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function kot(): BelongsTo
    {
        return $this->belongsTo(Kot::class);
    }

    /**
     * The kitchen that claimed this item for preparation.
     */
    public function claimedByKitchen(): BelongsTo
    {
        return $this->belongsTo(KotPlace::class, 'claimed_by_kitchen_id');
    }

    /**
     * Check if this item has been claimed by any kitchen.
     */
    public function isClaimed(): bool
    {
        return !is_null($this->claimed_by_kitchen_id);
    }

    /**
     * Claim this item for a specific kitchen (first-come-first-served).
     * Uses atomic update to prevent race conditions between kitchens.
     * Returns true if claim was successful, false if already claimed by another.
     */
    public function claimForKitchen(int $kitchenId): bool
    {
        if ($this->isClaimed()) {
            return $this->claimed_by_kitchen_id === $kitchenId;
        }

        // Atomic: only update if still unclaimed (prevents race condition)
        $affected = self::where('id', $this->id)
            ->whereNull('claimed_by_kitchen_id')
            ->update([
                'claimed_by_kitchen_id' => $kitchenId,
                'claimed_at' => now(),
            ]);

        if ($affected > 0) {
            $this->claimed_by_kitchen_id = $kitchenId;
            $this->claimed_at = now();
            return true;
        }

        // Someone else claimed it between our check and update
        $this->refresh();
        return $this->claimed_by_kitchen_id === $kitchenId;
    }
}
