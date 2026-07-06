<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Schema;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'properties' => 'array',
        'created_at' => 'datetime',
        'restaurant_id' => 'integer',
        'branch_id' => 'integer',
        'causer_id' => 'integer',
        'legacy_id' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (ActivityLog $log) {
            if (!$log->created_at) {
                $log->created_at = now();
            }
        });
    }

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

    public function causer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'causer_id')->withoutGlobalScopes();
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function moduleLabel(): string
    {
        return match ($this->module) {
            'core' => __('app.activityLog.modules.core'),
            'inventory' => __('app.activityLog.modules.inventory'),
            'cash_register' => __('app.activityLog.modules.cashRegister'),
            'hotel' => __('app.activityLog.modules.hotel'),
            default => ucfirst(str_replace('_', ' ', (string) $this->module)),
        };
    }

    public function categoryLabel(): string
    {
        return match ($this->category) {
            'order' => __('app.activityLog.categories.order'),
            'kot' => __('app.activityLog.categories.kot'),
            'payment' => __('app.activityLog.categories.payment'),
            'cash' => __('app.activityLog.categories.cash'),
            'inventory' => __('app.activityLog.categories.inventory'),
            'staff' => __('app.activityLog.categories.staff'),
            'settings' => __('app.activityLog.categories.settings'),
            'expense' => __('app.activityLog.categories.expense'),
            'reservation' => __('app.activityLog.categories.reservation'),
            'delivery' => __('app.activityLog.categories.delivery'),
            'auth' => __('app.activityLog.categories.auth'),
            'folio' => __('app.activityLog.categories.folio'),
            'housekeeping' => __('app.activityLog.categories.housekeeping'),
            'hotel' => __('app.activityLog.categories.hotel'),
            default => ucfirst(str_replace('_', ' ', (string) $this->category)),
        };
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    public function formattedPropertyRows(): array
    {
        return \App\Support\ActivityLogPropertyFormatter::format($this->properties ?? [], $this->event);
    }

    public function orderViewUrl(): ?string
    {
        $orderId = data_get($this->properties, 'order_id');

        if (!$orderId && $this->subject_type === Order::class && $this->subject_id) {
            $orderId = $this->subject_id;
        }

        if (!$orderId) {
            return null;
        }

        if ($this->subject instanceof Order) {
            return $this->subject->staffDetailUrl();
        }

        $order = Order::query()
            ->select('id', 'status')
            ->find($orderId);

        return $order?->staffDetailUrl();
    }

    public function canViewLinkedOrder(): bool
    {
        return user_can('Show Order')
            || user_can('View Order')
            || user_can('Create Order')
            || user_can('Update Order');
    }
}
