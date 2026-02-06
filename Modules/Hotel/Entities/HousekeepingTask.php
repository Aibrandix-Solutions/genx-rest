<?php

namespace Modules\Hotel\Entities;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HousekeepingTask extends Model
{
    use HasFactory;

    protected $table = 'hotel_housekeeping_tasks';

    protected $fillable = [
        'branch_id',
        'room_id',
        'assigned_to_user_id',
        'task_type',
        'priority',
        'status',
        'notes',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    const TYPE_CLEANING = 'cleaning';
    const TYPE_MAINTENANCE = 'maintenance';
    const TYPE_INSPECTION = 'inspection';

    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    /**
     * Mark task as started
     */
    public function start()
    {
        $this->update([
            'status' => self::STATUS_IN_PROGRESS,
            'started_at' => now(),
        ]);
    }

    /**
     * Mark task as completed
     */
    public function complete()
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        // Update room status to available if it was cleaning
        if ($this->task_type === self::TYPE_CLEANING) {
            $this->room->update([
                'status' => Room::STATUS_AVAILABLE,
                'last_cleaned_at' => now(),
            ]);
        }
    }
}
