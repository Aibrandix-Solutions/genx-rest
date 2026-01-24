<?php

namespace Modules\Inventory\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// Removed: use App\Traits\HasBranch;
use App\Traits\HasRestaurant;

class InventoryItemCategory extends Model
{
    use HasFactory;
    use HasRestaurant;
    // Removed HasBranch trait - categories are now restaurant-scoped

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = ['id'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $model) {
            if (!$model->restaurant_id && restaurant()) {
                $model->restaurant_id = restaurant()->id;
            }
        });
    }

    const CATEGORIES = [
        'Meat & Poultry',
        'Seafood',
        'Dairy & Eggs',
        'Fresh Produce',
        'Herbs & Spices',
        'Dry Goods',
        'Canned Goods',
        'Beverages',
        'Condiments & Sauces',
        'Baking Supplies',
        'Oils & Vinegars',
        'Frozen Foods',
        'Cleaning Supplies',
        'Kitchen Equipment',
        'Disposables'
    ];
}
