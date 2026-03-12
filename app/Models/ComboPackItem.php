<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComboPackItem extends BaseModel
{
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * Get the combo pack that owns this item.
     */
    public function comboPack(): BelongsTo
    {
        return $this->belongsTo(ComboPack::class);
    }

    /**
     * Get the menu item.
     */
    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }

    /**
     * Get the menu item variation (if specified).
     */
    public function menuItemVariation(): BelongsTo
    {
        return $this->belongsTo(MenuItemVariation::class, 'menu_item_variation_id');
    }
}
