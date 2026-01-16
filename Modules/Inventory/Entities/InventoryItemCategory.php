<?php

namespace Modules\Inventory\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// Removed: use App\Traits\HasBranch;

class InventoryItemCategory extends Model
{
    use HasFactory;
    // Removed HasBranch trait - categories are now restaurant-scoped

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = ['id'];

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
