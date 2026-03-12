<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasBranch;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TableSession extends BaseModel
{
    use HasBranch;
    protected $guarded = ['id'];

    protected $casts = [
        'locked_at' => 'datetime',
        'last_activity_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (!$model->session_token) {
                $model->session_token = Str::random(32);
            }
        });
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class);
    }

    public function lockedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by_user_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Check if the session is locked
     */
    public function isLocked(): bool
    {
        // Order locks must behave as a lock even if someone cleared the user fields.
        // (Some flows historically called releaseLock() directly.)
        if ($this->isOrderLock()) {
            return true;
        }

        return !is_null($this->locked_by_user_id) && !is_null($this->locked_at);
    }

    /**
     * Check if the session is locked by a specific user
     */
    public function isLockedByUser(int $userId): bool
    {
        return $this->locked_by_user_id === $userId;
    }

    /**
     * Check if the lock has expired
     */
    public function isLockExpired(int $lockTimeoutMinutes = 5): bool
    {
        // Disable time-based expiry when timeout is 0/negative
        if ($lockTimeoutMinutes <= 0) {
            return false;
        }

        // Order locks NEVER expire
        if ($this->isOrderLock()) {
            return false;
        }

        if (!$this->isLocked() || !$this->last_activity_at) {
            return false;
        }

        return $this->last_activity_at->addMinutes($lockTimeoutMinutes)->isPast();
    }

    /**
     * Lock the session for a user
     */
    public function lockForUser(int $userId): bool
    {
        return $this->update([
            'locked_by_user_id' => $userId,
            'locked_at' => now(),
            'last_activity_at' => now(),
            'session_token' => Str::random(32),
        ]);
    }

    /**
     * Update the last activity timestamp
     */
    public function updateActivity(): bool
    {
        if (!$this->isLocked()) {
            return false;
        }

        return $this->update([
            'last_activity_at' => now(),
        ]);
    }

    /**
     * Release the lock
     */
    public function releaseLock(): bool
    {
        // Prevent accidentally clearing an order lock via the generic/manual unlock.
        if ($this->isOrderLock()) {
            return false;
        }

        return $this->update([
            'locked_by_user_id' => null,
            'locked_at' => null,
            'last_activity_at' => null,
            'session_token' => null,
        ]);
    }

    /**
     * Check if a user can access this session
     */
    public function canBeAccessedByUser(int $userId, int $lockTimeoutMinutes = 5): bool
    {
        // Session is not locked
        if (!$this->isLocked()) {
            return true;
        }

        // Session is locked by the same user
        if ($this->isLockedByUser($userId)) {
            return true;
        }

        // Session lock has expired
        if ($this->isLockExpired($lockTimeoutMinutes)) {
            return true;
        }

        return false;
    }

    /**
     * Check if lock is tied to an order
     */
    public function isOrderLock(): bool
    {
        return $this->locked_by_order && !is_null($this->order_id);
    }

    /**
     * Check if this is a manual lock (not order-based)
     */
    public function isManualLock(): bool
    {
        return $this->isLocked() && !$this->locked_by_order;
    }

    /**
     * Lock session for an order (prevents expiration)
     */
    public function lockForOrder(int $userId, int $orderId): bool
    {
        return $this->update([
            'locked_by_user_id' => $userId,
            'locked_at' => now(),
            'last_activity_at' => now(),
            'session_token' => Str::random(32),
            'order_id' => $orderId,
            'locked_by_order' => true,
        ]);
    }

    /**
     * Release order lock
     */
    public function releaseOrderLock(): bool
    {
        if (!$this->isOrderLock()) {
            return false;
        }

        return $this->update([
            'locked_by_user_id' => null,
            'locked_at' => null,
            'last_activity_at' => null,
            'session_token' => null,
            'order_id' => null,
            'locked_by_order' => false,
        ]);
    }
}
