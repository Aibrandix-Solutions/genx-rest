<?php

namespace Modules\Inventory\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Restaurant;
use App\Models\Branch;

class PurchaseLocation extends Model
{
    use HasFactory;

    protected $table = 'purchase_locations';
    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'location_id');
    }

    /**
     * Scope for branch-type locations
     */
    public function scopeBranchLocations($query)
    {
        return $query->where('type', 'branch')->whereNotNull('branch_id');
    }

    /**
     * Scope for warehouse/supplier locations
     */
    public function scopeWarehouseLocations($query)
    {
        return $query->where(function($q) {
            $q->where('type', 'warehouse')
              ->orWhere('type', 'supplier');
        });
    }

    /**
     * Get display name for the location
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->type === 'branch' && $this->branch) {
            return "{$this->branch->name} (Branch)";
        }
        return $this->name;
    }

    /**
     * Get available locations for a restaurant
     */
    public static function getForRestaurant($restaurantId)
    {
        return self::where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->orderBy('type')
            ->orderBy('name')
            ->get();
    }
}
