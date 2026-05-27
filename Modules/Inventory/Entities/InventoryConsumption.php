<?php

namespace Modules\Inventory\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Branch;
use App\Models\Restaurant;
use App\Models\MenuItem;
use App\Models\User;

class InventoryConsumption extends Model
{
    use HasFactory;

    protected $table = 'inventory_consumptions';

    protected $fillable = [
        'restaurant_id',
        'branch_id',
        'inventory_item_id',
        'location_id',
        'quantity',
        'stock_before',
        'stock_after',
        'consumption_date',
        'note',
        'added_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'stock_before' => 'decimal:2',
        'stock_after' => 'decimal:2',
        'consumption_date' => 'date',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(PurchaseLocation::class, 'location_id');
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by')->withoutGlobalScopes();
    }

    public function menuItems(): BelongsToMany
    {
        return $this->belongsToMany(
            MenuItem::class,
            'inventory_consumption_menu_item',
            'inventory_consumption_id',
            'menu_item_id'
        )->withTimestamps();
    }
}
