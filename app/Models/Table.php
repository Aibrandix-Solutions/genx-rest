<?php

namespace App\Models;

use App\Helper\Files;
use App\Traits\HasBranch;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Label\Font\NotoSans;
use Endroid\QrCode\Label\LabelAlignment;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Symfony\Component\HttpFoundation\File\File;
use Illuminate\Support\Facades\File as FileFacade;
use Illuminate\Support\Facades\Storage;
use App\Traits\GeneratesQrCode;
use App\Models\BaseModel;

class Table extends BaseModel
{

    use HasFactory;
    use HasBranch;
    use GeneratesQrCode;

    protected $guarded = ['id'];

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function activeOrder(): HasOne
    {
        return $this->hasOne(Order::class)->whereIn('status', ['billed', 'kot'])->orderBy('id', 'desc');
    }

    public function qRCodeUrl(): Attribute
    {
        return Attribute::get(fn(): string => asset_url_local_s3('qrcodes/' . $this->getQrCodeFileName()));
    }

    /**
     * Effective availability derived from the live state of the table.
     *
     * Rules:
     * - `running`  → the table is assigned: it has an active order (kot/billed)
     *               OR its session is currently locked (manual user-lock or order-lock).
     *               "Locked" and "running" are the same concept for display purposes.
     * - `reserved` → no active order / no lock, but the stored column is `reserved`
     *               (separate concern, driven by the reservation flow).
     * - `available` → unlocked and unassigned.
     *
     * Stale stored `running` values are ignored; a table is only running when the
     * live state (order or lock) says so. Paid/cancelled orders are historical and
     * never hold the table (they are not part of `activeOrder`).
     */
    public function effectiveAvailableStatus(): Attribute
    {
        return Attribute::get(function (): string {
            $hasActiveOrder = $this->relationLoaded('activeOrder')
                ? (bool) $this->getRelation('activeOrder')
                : $this->activeOrder()->exists();

            if ($hasActiveOrder) {
                return 'running';
            }

            $session = $this->relationLoaded('tableSession')
                ? $this->getRelation('tableSession')
                : $this->tableSession;

            if ($session && $session->isLocked()) {
                return 'running';
            }

            $stored = (string) ($this->attributes['available_status'] ?? 'available');

            return $stored === 'reserved' ? 'reserved' : 'available';
        });
    }

    public function generateQrCode()
    {
        // Generate a new hash to invalidate old QR code links
        $this->update(['hash' => md5(microtime() . rand(1, 99999999))]);

        $this->createQrCode(route('table_order', [$this->hash]), __('modules.table.table') . ' ' . str()->slug($this->table_code, '-', (auth()->user() ? auth()->user()->locale : 'en')));
    }

    public function getQrCodeFileName(): string
    {
        return 'qrcode-' . $this->branch_id . '-' . str()->slug($this->table_code, '-', (auth()->user() ? auth()->user()->locale : 'en')) . '.png';
    }

    public function getRestaurantId(): int
    {
        return $this->branch?->restaurant_id;
    }

    public function activeWaiterRequest(): HasOne
    {
        return $this->hasOne(WaiterRequest::class)->where('status', 'pending');
    }

    public function waiterRequests(): HasMany
    {
        return $this->hasMany(WaiterRequest::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function activeReservation(): HasOne
    {
        return $this->hasOne(Reservation::class)
            ->where('reservation_date_time', '>=', now())
            ->orderBy('reservation_date_time', 'asc');
    }

    public function currentReservationOrders()
    {
        return $this->hasOne(Order::class)
            ->whereHas('reservation', function ($query) {
                $activeReservation = $this->activeReservation;
                if ($activeReservation) {
                    $query->where('id', $activeReservation->id);
                }
            });
    }

    public function tableSession(): HasOne
    {
        return $this->hasOne(TableSession::class);
    }

    public function getOrCreateSession(): TableSession
    {
        return $this->tableSession()->firstOrCreate(
            ['table_id' => $this->id],
            ['branch_id' => $this->branch_id]
        );
    }

    public function isLocked(): bool
    {
        $session = $this->tableSession;
        return $session ? $session->isLocked() : false;
    }

    public function isLockedByUser(int $userId): bool
    {
        $session = $this->tableSession;
        return $session ? $session->isLockedByUser($userId) : false;
    }

    public function canBeAccessedByUser(int $userId, ?int $lockTimeoutMinutes = null): bool
    {
        $session = $this->getOrCreateSession();
        $timeout = $lockTimeoutMinutes ?? $this->resolveLockTimeoutMinutes();
        return $session->canBeAccessedByUser($userId, $timeout);
    }

    public function lockForUser(int $userId): array
    {
        $session = $this->getOrCreateSession();

        $timeout = $this->resolveLockTimeoutMinutes();

        if (!$session->canBeAccessedByUser($userId, $timeout)) {
            $lockedByUser = $session->lockedByUser;
            return [
                'success' => false,
                'message' => "This table is currently being handled by {$lockedByUser->name}. Please try again later.",
                'locked_by' => $lockedByUser->name ?? 'Unknown User',
                'locked_at' => $session->locked_at?->format('H:i') ?? '',
            ];
        }

        if ($session->lockForUser($userId)) {
            return [
                'success' => true,
                'message' => 'Table locked successfully',
                'session_token' => $session->session_token,
            ];
        }

        return [
            'success' => false,
            'message' => 'Failed to lock table',
        ];
    }

    private function resolveLockTimeoutMinutes(): int
    {
        // Prefer the table's actual restaurant setting (works even when restaurant() helper is null).
        $this->loadMissing('branch.restaurant');

        if (($this->branch?->restaurant?->disable_table_lock_timeout ?? false) === true) {
            return 0;
        }

        $timeout = $this->branch?->restaurant?->table_lock_timeout_minutes;
        if (is_numeric($timeout) && (int) $timeout > 0) {
            return (int) $timeout;
        }

        $timeout = restaurant()->table_lock_timeout_minutes ?? null;
        if (is_numeric($timeout) && (int) $timeout > 0) {
            return (int) $timeout;
        }

        return 10;
    }

    public function updateActivity(int $userId): bool
    {
        $session = $this->tableSession;

        if (!$session || !$session->isLockedByUser($userId)) {
            return false;
        }

        return $session->updateActivity();
    }

    public function unlock(int $userId = null, bool $forceUnlock = false): array
    {
        $session = $this->tableSession;

        if (!$session) {
            return [
                'success' => true,
                'message' => 'Table is not locked',
            ];
        }

        // Order locks are normally released via unlockFromOrder() (OrderObserver on status change).
        // But allow force unlock (admin override) to recover from stuck locks.
        if ($session->isOrderLock()) {
            if ($forceUnlock) {
                return $session->releaseOrderLock()
                    ? ['success' => true, 'message' => 'Table unlocked successfully']
                    : ['success' => false, 'message' => 'Failed to unlock table'];
            }

            return [
                'success' => false,
                'message' => 'This table is locked by an active order and cannot be unlocked manually.',
            ];
        }

        // If not force unlock, check if user can unlock
        if (!$forceUnlock && $userId && !$session->isLockedByUser($userId)) {
            return [
                'success' => false,
                'message' => 'You cannot unlock this table',
            ];
        }

        if ($session->releaseLock()) {
            return [
                'success' => true,
                'message' => 'Table unlocked successfully',
            ];
        }

        return [
            'success' => false,
            'message' => 'Failed to unlock table',
        ];
    }

    /**
     * Clean up expired table locks - centralized static method
     */
    public static function cleanupExpiredLocks(): array
    {
        $restaurant = restaurant();
        if ($restaurant && ($restaurant->disable_table_lock_timeout ?? false)) {
            return [
                'affected_rows' => 0,
                'expired_sessions' => [],
            ];
        }

        $lockTimeoutMinutes = $restaurant?->table_lock_timeout_minutes ?? 10;
        $expiredTime = now()->subMinutes($lockTimeoutMinutes);

        // Only cleanup non-order locks
        $expiredSessions = TableSession::with(['table', 'lockedByUser'])
            ->where('last_activity_at', '<', $expiredTime)
            ->whereNotNull('locked_by_user_id')
            ->where('locked_by_order', false) // Skip order locks
            ->get();

        // Cleanup expired locks - also respecting branch scope
        $affectedRows = TableSession::where('last_activity_at', '<', $expiredTime)
            ->whereNotNull('locked_by_user_id')
            ->where('locked_by_order', false) // Skip order locks
            ->update([
                'locked_by_user_id' => null,
                'locked_at' => null,
                'last_activity_at' => null,
                'session_token' => null,
            ]);

        return [
            'affected_rows' => $affectedRows,
            'expired_sessions' => $expiredSessions->toArray(),
        ];
    }

    /**
     * Get currently locked tables data for display
     */
    public static function getLockedTablesData(): array
    {
        // Auto-cleanup expired locks first
        self::cleanupExpiredLocks();

        $totalLocked = TableSession::whereNotNull('locked_by_user_id')->count();

        $restaurant = restaurant();
        $lockTimeout = $restaurant?->table_lock_timeout_minutes ?? 10;
        $expiredTime = now()->subMinutes($lockTimeout);

        $expiredLocks = TableSession::whereNotNull('locked_by_user_id')
            ->where('last_activity_at', '<', $expiredTime)
            ->where('locked_by_order', false) // Only count manual locks
            ->count();

        $lockedTables = TableSession::with(['table.area', 'lockedByUser'])
            ->whereNotNull('locked_by_user_id')
            ->whereNotNull('locked_at')
            ->orderBy('locked_at', 'desc')
            ->get()
            ->toArray();

        return [
            'total_locked' => $totalLocked,
            'expired_locks' => $expiredLocks,
            'locked_tables' => $lockedTables,
        ];
    }

    /**
     * Lock table for an order
     */
    public function lockForOrder(int $userId, int $orderId): array
    {
        // Check if feature is enabled
        $this->loadMissing('branch.restaurant');
        $enabled = $this->branch?->restaurant?->enable_table_lock_on_order
            ?? (restaurant()->enable_table_lock_on_order ?? false);

        if (!$enabled) {
            return [
                'success' => true,
                'message' => 'Table lock on order is disabled',
            ];
        }

        $session = $this->getOrCreateSession();

        // If already locked by another user (not by order), check if can access
        if ($session->isLocked() && !$session->isOrderLock() && !$session->isLockedByUser($userId)) {
            $lockedByUser = $session->lockedByUser;
            return [
                'success' => false,
                'message' => "This table is currently being handled by {$lockedByUser->name}",
                'locked_by' => $lockedByUser->name ?? 'Unknown User',
            ];
        }

        if ($session->lockForOrder($userId, $orderId)) {
            return [
                'success' => true,
                'message' => 'Table locked for order',
                'session_token' => $session->session_token,
            ];
        }

        return [
            'success' => false,
            'message' => 'Failed to lock table for order',
        ];
    }

    /**
     * Unlock table when order is billed/canceled (and on delete)
     */
    public function unlockFromOrder(int $orderId): array
    {
        $session = $this->tableSession;

        if (!$session) {
            return ['success' => true, 'message' => 'Table is not locked'];
        }

        // Only unlock if this session is locked by the specified order
        if ($session->isOrderLock() && $session->order_id === $orderId) {
            if ($session->releaseOrderLock()) {
                return ['success' => true, 'message' => 'Table unlocked from order'];
            }
        }

        return ['success' => false, 'message' => 'Table is not locked by this order'];
    }
}
