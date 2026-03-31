<?php

namespace App\Models;

use App\Traits\HasBranch;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Spatie\Translatable\HasTranslations;

class ComboPack extends BaseModel
{
    use HasFactory, HasBranch, HasTranslations;

    protected $guarded = ['id'];

    public $translatable = ['name', 'description'];

    protected $casts = [
        'regular_price' => 'decimal:2',
        'discounted_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'combo_image_url',
    ];

    /**
     * Get the branch that owns the combo pack.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get all items in this combo pack.
     */
    public function comboPackItems(): HasMany
    {
        return $this->hasMany(ComboPackItem::class)->orderBy('sort_order');
    }

    /**
     * Get menu items through combo pack items.
     */
    public function menuItems(): BelongsToMany
    {
        return $this->belongsToMany(MenuItem::class, 'combo_pack_items', 'combo_pack_id', 'menu_item_id')
            ->withPivot('quantity', 'menu_item_variation_id', 'sort_order')
            ->withTimestamps();
    }

    /**
     * Get order items that came from this combo pack.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get combo image URL.
     */
    protected function comboImageUrl(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) => $attributes['image'] 
                ? asset_url_local_s3('combo_packs/' . $attributes['image']) 
                : null
        );
    }

    /**
     * Calculate regular price from all items in the combo.
     */
    public function calculateRegularPrice(?int $orderTypeId = null, ?int $deliveryAppId = null): float
    {
        $total = 0;
        
        foreach ($this->comboPackItems as $comboItem) {
            $menuItem = $comboItem->menuItem;
            
            // Get price based on context (order type, delivery app, variation)
            $itemPrice = $menuItem->getPriceForContext(
                $orderTypeId,
                $deliveryAppId,
                $comboItem->menu_item_variation_id
            );
            
            $total += $itemPrice * $comboItem->quantity;
        }
        
        return round($total, 2);
    }

    /**
     * Get price for specific order type and delivery app context.
     */
    public function getPriceForContext(?int $orderTypeId = null, ?int $deliveryAppId = null): float
    {
        // For Phase 1, return discounted_price
        // Phase 2: Can implement order type specific pricing here
        return (float) $this->discounted_price;
    }

    /**
     * Check if all items in combo are available (active and in stock).
     */
    public function isAvailable(): bool
    {
        // First check if combo itself is active
        if (!$this->is_active) {
            return false;
        }
        
        // Check if combo has items
        if ($this->comboPackItems->isEmpty()) {
            return false;
        }
        
        foreach ($this->comboPackItems as $comboItem) {
            $menuItem = $comboItem->menuItem;
            
            // Check if menu item exists
            if (!$menuItem) {
                return false;
            }
            
            // Check if menu item is available (using is_available field, not is_enabled)
            if (isset($menuItem->is_available) && !$menuItem->is_available) {
                return false;
            }
            
            // Check if in stock
            if (isset($menuItem->in_stock) && !$menuItem->in_stock) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Validate stock for all items in combo.
     */
    public function validateStock(): array
    {
        if (!module_enabled('Inventory')) {
            // If inventory module not enabled, just check menu item stock flag
            foreach ($this->comboPackItems as $comboItem) {
                $menuItem = $comboItem->menuItem;
                if (!$menuItem->in_stock) {
                    return [
                        'valid' => false,
                        'message' => "{$menuItem->item_name} is out of stock"
                    ];
                }
            }
            return ['valid' => true];
        }

        // Check inventory stock for each item
        foreach ($this->comboPackItems as $comboItem) {
            $menuItem = $comboItem->menuItem;
            
            if (!$menuItem->in_stock) {
                return [
                    'valid' => false,
                    'message' => "{$menuItem->item_name} is out of stock"
                ];
            }
            
            // Check recipe-based inventory
            foreach ($menuItem->recipes as $recipe) {
                $inventoryItem = $recipe->inventoryItem;
                $requiredStock = $recipe->quantity * $comboItem->quantity;
                
                if ($inventoryItem->current_stock < $requiredStock) {
                    return [
                        'valid' => false,
                        'message' => "Insufficient stock for {$menuItem->item_name}"
                    ];
                }
            }
        }
        
        return ['valid' => true];
    }

    /**
     * Calculate individual item prices with combo discount distributed proportionally.
     */
    public function calculateComboItemPrices(?int $orderTypeId = null, ?int $deliveryAppId = null): array
    {
        $regularTotal = 0;
        $itemPrices = [];
        
        // Calculate regular price for each item
        foreach ($this->comboPackItems as $comboItem) {
            $menuItem = $comboItem->menuItem;
            $regularPrice = $menuItem->getPriceForContext(
                $orderTypeId,
                $deliveryAppId,
                $comboItem->menu_item_variation_id
            );
            
            $itemTotal = $regularPrice * $comboItem->quantity;
            $itemPrices[] = [
                'combo_item' => $comboItem,
                'regular_price' => $regularPrice,
                'regular_total' => $itemTotal,
            ];
            $regularTotal += $itemTotal;
        }
        
        // Get combo discounted price
        $comboPrice = $this->getPriceForContext($orderTypeId, $deliveryAppId);
        $totalDiscount = $regularTotal - $comboPrice;
        
        // Distribute discount proportionally
        $finalPrices = [];
        $allocatedTotal = 0;
        
        foreach ($itemPrices as $index => $item) {
            if ($index === count($itemPrices) - 1) {
                // Last item gets remaining to avoid rounding errors
                $finalPrice = $comboPrice - $allocatedTotal;
            } else {
                // Proportional discount
                $discountRatio = $item['regular_total'] / $regularTotal;
                $itemDiscount = $totalDiscount * $discountRatio;
                $finalPrice = $item['regular_total'] - $itemDiscount;
            }
            
            $allocatedTotal += $finalPrice;
            
            $finalPrices[] = [
                'combo_item' => $item['combo_item'],
                'price' => round($finalPrice / $item['combo_item']->quantity, 2),
                'original_price' => $item['regular_price'],
                'combo_discount_amount' => ($item['regular_price'] * $item['combo_item']->quantity) - $finalPrice,
            ];
        }
        
        return $finalPrices;
    }
}
