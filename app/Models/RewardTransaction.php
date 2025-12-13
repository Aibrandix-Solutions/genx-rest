<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class RewardTransaction extends BaseModel
{
    protected $guarded = ['id'];

    protected $casts = [
        'points' => 'integer',
        'amount_value' => 'decimal:2',
        'meta' => 'array',
        'expires_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if transaction is expired
     */
    public function isExpired(): bool
    {
        if (!$this->expires_at || $this->type !== 'earn') {
            return false;
        }

        return $this->expires_at->isPast();
    }

    /**
     * Scope for non-expired earned points
     */
    public function scopeNonExpired($query)
    {
        return $query->where(function ($q) {
            $q->where('type', '!=', 'earn')
              ->orWhere(function ($q2) {
                  $q2->where('type', 'earn')
                     ->where(function ($q3) {
                         $q3->whereNull('expires_at')
                            ->orWhere('expires_at', '>', now());
                     });
              });
        });
    }

    /**
     * Scope for expired points
     */
    public function scopeExpired($query)
    {
        return $query->where('type', 'earn')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());
    }
}

