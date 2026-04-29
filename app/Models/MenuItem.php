<?php

namespace App\Models;

use App\Models\Menu;
use App\Models\OrderItem;
use App\Traits\HasBranch;
use App\Models\ItemCategory;
use App\Models\MenuItemVariation;
use App\Models\MenuItemPrices;
use App\Models\MenuItemTranslation;
use App\Models\DeliveryPlatform;
use Illuminate\Support\Facades\Cache;
use App\Scopes\AvailableMenuItemScope;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\QueryException;
use App\Models\BaseModel;
use App\Traits\HasContextualPricing;

class MenuItem extends BaseModel
{
    use HasFactory, HasBranch, HasTranslations, HasContextualPricing;


    const VEG = 'veg';
    const NONVEG = 'non-veg';
    const EGG = 'egg';

    const FILENAME_TO_EXCLUDE = [
        '.htaccess',
        'butter-chicken.webp',
        'chicken-hyderabadi-biryani.webp',
        'chicken-manchurian.webp',
        'chilli-paneer.webp',
        'dal-makhni.webp',
        'idli-sambar.webp',
        'masala-dosa.webp',
        'medu-vada.webp',
        'naan-recipe.webp',
        'paneer-tikka.webp',
        'spring-rolls.webp',
        'tandoori-roti.webp',
        'uttapam.webp',
        'vegetable-hakka-noodles.webp',
        'vegetable-manchow-soup.webp'
    ];

    protected $guarded = ['id'];

    protected $casts = [
        'show_on_customer_site' => 'boolean',
    ];

    protected $appends = [
        'item_photo_url',
        'contextual_price', // Add contextual_price as computed property
    ];

    protected $with = ['translations'];

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new AvailableMenuItemScope());
    }

    /**
     * Get contextual price for a specific variation
     * Usage: $menuItem->getVariationPrice($variationId)
     * 
     * @param int $variationId
     * @return float
     */
    public function getVariationPrice(int $variationId): float
    {
        if ($this->contextOrderTypeId !== null) {
            return $this->resolvePrice(
                $this->contextOrderTypeId,
                $this->contextDeliveryAppId,
                $variationId
            );
        }

        // Fallback to base variation price
        $variation = $this->variations()->find($variationId);
        return $variation ? (float)$variation->price : (float)($this->attributes['price'] ?? 0);
    }

    /**
     * Implementation of HasContextualPricing trait
     * Resolves contextual price from menu_item_prices table
     * 
     * @param int $orderTypeId
     * @param int|null $deliveryAppId
     * @return float
     */
    protected function resolveContextualPrice(int $orderTypeId, ?int $deliveryAppId = null): float
    {
        return $this->resolvePrice($orderTypeId, $deliveryAppId, null);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(MenuItemTranslation::class, 'menu_item_id');
    }

    public function translation($locale = null): HasOne
    {
        return $this->hasOne(MenuItemTranslation::class)->where('locale', $locale ?? app()->getLocale());
    }

    public function getTranslatedValue(string $attribute, ?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        $cacheKey = "menu_item_{$this->id}_{$attribute}_{$locale}";

        return Cache::remember($cacheKey, 3600, function () use ($locale, $attribute) {
            $translation = $this->translation($locale)->first();
            return $translation?->{$attribute} ?? $this->attributes[$attribute] ?? '';
        });
    }

    public function getItemNameAttribute(): string
    {
        return $this->getTranslatedValue('item_name');
    }

    public function getDescriptionAttribute(): string
    {
        return $this->getTranslatedValue('description');
    }

    public function itemPhotoUrl(): Attribute
    {
        return Attribute::get(function (): string {
            if (in_array($this->image, MenuItem::FILENAME_TO_EXCLUDE)) {
                return asset_url('item/' . $this->image);
            }
            return $this->image ? asset_url_local_s3('item/' . $this->image) : asset('img/food.svg');
        });
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class, 'item_category_id');
    }

    public function variations(): HasMany
    {
        return $this->hasMany(MenuItemVariation::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(MenuItemPrices::class);
    }

    public function recipes(): HasMany
    {
        return $this->hasMany(\Modules\Inventory\Entities\Recipe::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function modifiers(): HasMany
    {
        return $this->hasMany(ItemModifier::class);
    }

    public function modifierGroups(): BelongsToMany
    {
        return $this->belongsToMany(ModifierGroup::class, 'item_modifiers', 'menu_item_id', 'modifier_group_id');
    }

    public function kotPlace()
    {
        return $this->belongsTo(KotPlace::class, 'kot_place_id');
    }

    /**
     * Many-to-many: all kitchen places this item can be prepared in.
     */
    public function kotPlaces(): BelongsToMany
    {
        return $this->belongsToMany(KotPlace::class, 'menu_item_kot_place', 'menu_item_id', 'kot_place_id')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    /**
     * Get all kitchen place IDs (from pivot table, falling back to kot_place_id).
     */
    public function getKitchenPlaceIds(): array
    {
        $pivotIds = $this->kotPlaces()->pluck('kot_places.id')->toArray();

        if (!empty($pivotIds)) {
            return $pivotIds;
        }

        // Fallback to legacy single kot_place_id
        return $this->kot_place_id ? [$this->kot_place_id] : [];
    }

    /**
     * Check if item is assigned to multiple kitchens.
     */
    public function isMultiKitchen(): bool
    {
        return count($this->getKitchenPlaceIds()) > 1;
    }

    public function taxes(): BelongsToMany
    {
        return $this->belongsToMany(Tax::class, 'menu_item_tax', 'menu_item_id', 'tax_id');
    }

    public function comboPacks(): BelongsToMany
    {
        return $this->belongsToMany(ComboPack::class, 'combo_pack_items', 'menu_item_id', 'combo_pack_id')
            ->withPivot('quantity', 'menu_item_variation_id', 'sort_order')
            ->withTimestamps();
    }

    public function getTaxBreakdown($price, $selectedTaxIds = [], $isInclusive = null)
    {
        if (restaurant()->tax_mode !== 'item' || !$price) {
            return null;
        }

        if (empty($selectedTaxIds)) {
            return null;
        }

        $taxes = Tax::whereIn('id', $selectedTaxIds)->get();
        $taxPercent = $taxes->sum('tax_percent');
        $basePrice = floatval($price);

        // Use passed inclusive setting if provided, otherwise use restaurant setting
        // $inclusive = $isInclusive !== null ? $isInclusive : restaurant()->tax_inclusive;
        $inclusive = $isInclusive ?? restaurant()->tax_inclusive;

        $taxBreakdown = [];
        if ($inclusive) {
            $base = $basePrice / (1 + $taxPercent / 100);
            $totalTax = $basePrice - $base;

            // Calculate individual tax amounts
            foreach ($taxes as $tax) {
                $amount = $base * ($tax->tax_percent / 100);
                $taxBreakdown[$tax->tax_name] = $amount;
            }
        } else {
            $base = $basePrice;
            $totalTax = 0;

            // Calculate individual tax amounts
            foreach ($taxes as $tax) {
                $amount = $base * ($tax->tax_percent / 100);
                $taxBreakdown[$tax->tax_name] = $amount;
                $totalTax += $amount;
            }
        }

        return [
            'base' => currency_format($base, restaurant()->currency_id),
            'base_raw' => $base,
            'tax' => currency_format($totalTax, restaurant()->currency_id),
            'tax_raw' => $totalTax,
            'total' => currency_format($base + $totalTax, restaurant()->currency_id),
            'total_raw' => $base + $totalTax,
            'tax_percent' => $taxPercent,
            'inclusive' => $inclusive,
            'tax_breakdown' => $taxBreakdown
        ];
    }

    public static function calculateItemTaxes($itemPrice, $taxes = [], $inclusive)
    {
        // Ensure $taxes is a collection
        if (is_array($taxes)) {
            $taxes = collect($taxes);
        }

        $taxPercent = $taxes->sum('tax_percent');
        $basePrice = floatval($itemPrice);

        $taxBreakdown = [];
        $totalTax = 0;

        if ($inclusive) {
            $base = $basePrice / (1 + $taxPercent / 100);
            $totalTax = $basePrice - $base;

            foreach ($taxes as $tax) {
                $amount = $base * ($tax->tax_percent / 100);
                $taxBreakdown[$tax->tax_name] = [
                    'percent' => $tax->tax_percent,
                    'amount' => round($amount, 2)
                ];
                // No need to add to totalTax here as it's already calculated above
            }
        } else {
            $base = $basePrice;
            $totalTax = 0;

            foreach ($taxes as $tax) {
                $amount = $base * ($tax->tax_percent / 100);
                $taxBreakdown[$tax->tax_name] = [
                    'percent' => $tax->tax_percent,
                    'amount' => round($amount, 2)
                ];
                $totalTax += $amount;
            }
        }

        return [
            'base' => $base,
            'tax_amount' => $totalTax,
            'tax_percentage' => $taxPercent,
            'total_amount' => $base + $totalTax,
            'inclusive' => $inclusive,
            'tax_breakdown' => $taxBreakdown
        ];
    }

    /**
     * Get price for specific order type and delivery platform
     */
    public function getPriceForContext($orderTypeId, $deliveryAppId = null, $variationId = null)
    {
        $price = $this->prices()
            ->where('order_type_id', $orderTypeId)
            ->where('delivery_app_id', $deliveryAppId)
            ->where('menu_item_variation_id', $variationId)
            ->first();

        if ($price) {
            return $price->final_price;
        }

        // Fallback to base price
        if ($variationId) {
            $variation = $this->variations()->find($variationId);
            return $variation ? $variation->price : $this->price;
        }

        return $this->price;
    }

    /**
     * Get all pricing data for this menu item
     */
    public function getAllPricing()
    {
        return $this->prices()->with(['orderType', 'deliveryApp', 'menuItemVariation'])->get();
    }

    /**
     * Resolve price for this item considering order type, delivery app and optional variation.
     * Falls back in order:
     * - exact match (order_type + app + variation)
     * - order_type + variation (no app)
     * - order_type + app (no variation)
     * - order_type only
     * - variation base price
     * - item base price
     */
    public function resolvePrice(?int $orderTypeId, ?int $deliveryAppId = null, ?int $variationId = null)
    {
        // Try exact match
        $query = $this->prices()->where('status', true);

        if ($orderTypeId) {
            $query = $query->where('order_type_id', $orderTypeId);
        }

        if ($variationId) {
            $query = $query->where('menu_item_variation_id', $variationId);
        } else {
            $query = $query->whereNull('menu_item_variation_id');
        }

        if ($deliveryAppId) {
            $query = $query->where('delivery_app_id', $deliveryAppId);
        } else {
            $query = $query->whereNull('delivery_app_id');
        }

        $priceRow = $query->first();
        if ($priceRow) {
            return (float)$priceRow->final_price;
        }

        // Relax delivery app
        if ($deliveryAppId) {
            $relaxed = $this->prices()
                ->where('status', true)
                ->when($orderTypeId, fn($q) => $q->where('order_type_id', $orderTypeId))
                ->when($variationId, fn($q) => $q->where('menu_item_variation_id', $variationId), fn($q) => $q->whereNull('menu_item_variation_id'))
                ->whereNull('delivery_app_id')
                ->first();
            if ($relaxed) {
                $basePrice = (float)$relaxed->final_price;
                // Apply delivery platform commission to the base price
                $deliveryPlatform = DeliveryPlatform::find($deliveryAppId);
                if ($deliveryPlatform && $deliveryPlatform->commission_value > 0) {
                    return $deliveryPlatform->getPriceWithCommission($basePrice);
                }
                return $basePrice;
            }
        }

        // Relax variation
        if ($variationId) {
            $relaxedVar = $this->prices()
                ->where('status', true)
                ->when($orderTypeId, fn($q) => $q->where('order_type_id', $orderTypeId))
                ->whereNull('menu_item_variation_id')
                ->when($deliveryAppId, fn($q) => $q->where('delivery_app_id', $deliveryAppId), fn($q) => $q->whereNull('delivery_app_id'))
                ->first();
            if ($relaxedVar) {
                return (float)$relaxedVar->final_price;
            }
        }

        // Only order type
        if ($orderTypeId) {
            $orderTypeOnly = $this->prices()
                ->where('status', true)
                ->where('order_type_id', $orderTypeId)
                ->whereNull('menu_item_variation_id')
                ->whereNull('delivery_app_id')
                ->first();
            if ($orderTypeOnly) {
                return (float)$orderTypeOnly->final_price;
            }
        }

        // Variation base
        $basePrice = null;
        if ($variationId) {
            $variation = $this->variations()->find($variationId);
            if ($variation && isset($variation->attributes['price'])) {
                $basePrice = (float)$variation->attributes['price'];
            }
        }

        // Fallback to item base price from database (not accessor)
        if ($basePrice === null) {
            $basePrice = (float)($this->attributes['price'] ?? 0);
        }

        // Apply delivery platform commission if we have a delivery app and no specific pricing
        if ($deliveryAppId && $basePrice > 0) {
            $deliveryPlatform = DeliveryPlatform::find($deliveryAppId);
            if ($deliveryPlatform && $deliveryPlatform->commission_value > 0) {
                return $deliveryPlatform->getPriceWithCommission($basePrice);
            }
        }

        return $basePrice;
    }

    /**
     * Largest numeric suffix among strict auto-generated codes (IT + digits only) for a branch.
     * Ignores availability (bypasses AvailableMenuItemScope); branch scope matches DB uniqueness.
     */
    public static function maxAutoGeneratedItemCodeSuffixForBranch(int $branchId): int
    {
        $driver = static::query()->getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            return (int) static::query()
                ->withoutGlobalScope(AvailableMenuItemScope::class)
                ->where('branch_id', $branchId)
                ->whereRaw('item_code REGEXP ?', ['^IT[0-9]+$'])
                ->selectRaw('COALESCE(MAX(CAST(SUBSTRING(item_code, 3) AS UNSIGNED)), 0) as suffix_max')
                ->value('suffix_max');
        }

        if ($driver === 'pgsql') {
            return (int) static::query()
                ->withoutGlobalScope(AvailableMenuItemScope::class)
                ->where('branch_id', $branchId)
                ->whereRaw('item_code ~ ?', ['^IT[0-9]+$'])
                ->selectRaw('COALESCE(MAX(CAST(SUBSTRING(item_code FROM 3) AS INTEGER)), 0) as suffix_max')
                ->value('suffix_max');
        }

        $max = 0;
        static::query()
            ->withoutGlobalScope(AvailableMenuItemScope::class)
            ->where('branch_id', $branchId)
            ->where('item_code', 'like', 'IT%')
            ->pluck('item_code')
            ->each(function ($code) use (&$max) {
                if (preg_match('/^IT(\d+)$/', (string) $code, $m)) {
                    $max = max($max, (int) $m[1]);
                }
            });

        return $max;
    }

    /**
     * Format the auto sequence number as IT + zero-padded digits (minimum width 4).
     */
    public static function formatAutoItemCode(int $numericSuffix): string
    {
        $s = (string) $numericSuffix;
        $padLen = max(4, strlen($s));

        return 'IT'.str_pad($s, $padLen, '0', STR_PAD_LEFT);
    }

    /**
     * True when the exception is a duplicate key on (branch_id, item_code) / item_code.
     */
    public static function isDuplicateBranchItemCodeException(QueryException $e): bool
    {
        $msg = strtolower($e->getMessage());
        $sqlState = $e->errorInfo[0] ?? '';

        $isIntegrity = in_array($sqlState, ['23000', '23505'], true);
        if (! $isIntegrity) {
            return false;
        }

        $driverCode = isset($e->errorInfo[1]) ? (int) $e->errorInfo[1] : 0;
        $looksDuplicate = in_array($driverCode, [1062, 19], true)
            || str_contains($msg, 'unique')
            || str_contains($msg, 'duplicate');

        if (! $looksDuplicate) {
            return false;
        }

        return str_contains($msg, 'item_code')
            || str_contains($msg, 'menu_items_branch_item_code_unique');
    }

    /**
     * Next auto item code for a branch (IT + numeric sequence from max strict IT+digits row + 1).
     * Concurrent callers may get the same candidate until insert; use
     * {@see isDuplicateBranchItemCodeException} and retry on create when using auto codes.
     */
    public static function generateNextItemCodeForBranch(int $branchId): string
    {
        $next = static::maxAutoGeneratedItemCodeSuffixForBranch($branchId) + 1;

        return static::formatAutoItemCode($next);
    }
}
