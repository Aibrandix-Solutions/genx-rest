<?php

namespace Modules\Hrm\Entities;

use App\Traits\HasRestaurant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrmSetting extends Model
{
    use HasFactory;
    use HasRestaurant;

    protected $table = 'hrm_settings';

    protected $guarded = [];

    protected $casts = [
        'setting_value' => 'string',
    ];

    /**
     * Get setting value with type casting
     */
    public function getValue()
    {
        return match ($this->setting_type) {
            'number' => (float) $this->setting_value,
            'boolean' => in_array(strtolower($this->setting_value), ['true', '1', 'yes'], true),
            'json' => json_decode($this->setting_value, true),
            default => $this->setting_value,
        };
    }

    /**
     * Get a setting by key for a restaurant
     */
    public static function get(string $key, $default = null, $restaurantId = null)
    {
        $resolvedRestaurantId = $restaurantId;
        if ($resolvedRestaurantId === null) {
            $resolvedRestaurantId = restaurant()?->id;
        }
        if (!$resolvedRestaurantId) {
            return $default;
        }

        $setting = self::query()
            ->where('restaurant_id', $resolvedRestaurantId)
            ->where('setting_key', $key)
            ->first();

        return $setting ? $setting->getValue() : $default;
    }

    /**
     * Set a setting value for a restaurant
     */
    public static function set(string $key, $value, string $type = 'text', ?string $description = null): void
    {
        self::query()->updateOrCreate(
            [
                'restaurant_id' => restaurant()->id,
                'setting_key' => $key,
            ],
            [
                'setting_value' => is_array($value) ? json_encode($value) : (string) $value,
                'setting_type' => $type,
                'description' => $description,
            ]
        );
    }
}
