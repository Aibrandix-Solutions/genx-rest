<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class KotItemAdjustment extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'quantity_before' => 'integer',
        'quantity_after' => 'integer',
        'restaurant_id' => 'integer',
        'branch_id' => 'integer',
        'menu_item_id' => 'integer',
        'menu_item_variation_id' => 'integer',
    ];

    public function scopeForCurrentRestaurant($query)
    {
        $restaurantId = restaurant()->id ?? null;

        if (!$restaurantId) {
            return $query;
        }

        $table = $query->getModel()->getTable();

        if (!Schema::hasColumn($table, 'restaurant_id')) {
            return $query;
        }

        return $query->where($table . '.restaurant_id', $restaurantId);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function kot(): BelongsTo
    {
        return $this->belongsTo(Kot::class);
    }

    public function kotItem(): BelongsTo
    {
        return $this->belongsTo(KotItem::class);
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}


