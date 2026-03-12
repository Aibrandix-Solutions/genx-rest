<?php

namespace App\Models;

use App\Traits\HasBranch;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\BaseModel;

class OrderItem extends BaseModel
{
    use HasFactory;
    use HasBranch;

    protected $guarded = ['id'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }

    public function menuItemVariation(): BelongsTo
    {
        return $this->belongsTo(MenuItemVariation::class);
    }

    public function modifierOptions(): BelongsToMany
    {
        return $this->belongsToMany(ModifierOption::class, 'order_item_modifier_options', 'order_item_id', 'modifier_option_id')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function comboPack(): BelongsTo
    {
        return $this->belongsTo(ComboPack::class, 'combo_pack_id');
    }

    /**
     * Check if this order item is from a combo pack.
     */
    public function getIsComboItemAttribute(): bool
    {
        return isset($this->attributes['is_combo_item']) && $this->attributes['is_combo_item'] && $this->attributes['combo_pack_id'] !== null;
    }

    /**
     * Get original price (before combo discount) or current price.
     */
    public function getOriginalPriceAttribute()
    {
        return $this->attributes['original_price'] ?? $this->attributes['price'];
    }

    /**
     * Scope to filter combo items.
     */
    public function scopeComboItems($query)
    {
        return $query->where('is_combo_item', true)->whereNotNull('combo_pack_id');
    }

    /**
     * Scope to filter individual (non-combo) items.
     */
    public function scopeIndividualItems($query)
    {
        return $query->where('is_combo_item', false)->orWhereNull('combo_pack_id');
    }
}
