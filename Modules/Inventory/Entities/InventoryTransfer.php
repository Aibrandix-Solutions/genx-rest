<?php

namespace Modules\Inventory\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Branch;
use App\Models\User;
use App\Models\Restaurant;

class InventoryTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'restaurant_id',
        'transfer_number',
        'source_branch_id',
        'destination_branch_id',
        'source_location_id',
        'destination_location_id',
        'status',
        'notes',
        'expected_delivery_date',
        'created_by',
        'confirmed_by',
        'confirmed_at',
    ];

    protected $casts = [
        'expected_delivery_date' => 'date',
        'confirmed_at' => 'datetime',
    ];

    /**
     * Generate unique transfer number
     */
    public static function generateTransferNumber(): string
    {
        $prefix = 'TRF-' . date('Ymd');
        $lastTransfer = self::where('transfer_number', 'like', $prefix . '%')
            ->orderBy('transfer_number', 'desc')
            ->first();
        
        if ($lastTransfer) {
            $lastNumber = (int) substr($lastTransfer->transfer_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $prefix . '-' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function sourceBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'source_branch_id');
    }

    public function destinationBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'destination_branch_id');
    }

    public function sourceLocation(): BelongsTo
    {
        return $this->belongsTo(PurchaseLocation::class, 'source_location_id');
    }

    public function destinationLocation(): BelongsTo
    {
        return $this->belongsTo(PurchaseLocation::class, 'destination_location_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withoutGlobalScopes();
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by')->withoutGlobalScopes();
    }

    public function items(): HasMany
    {
        return $this->hasMany(InventoryTransferItem::class, 'inventory_transfer_id');
    }

    public function getTotalRequestedQuantityAttribute()
    {
        return $this->items->sum('requested_quantity');
    }

    public function getTotalConfirmedQuantityAttribute()
    {
        return $this->items->sum('confirmed_quantity');
    }

    public function getIsPendingAttribute()
    {
        return $this->status === 'pending';
    }

    public function getIsInTransitAttribute()
    {
        return $this->status === 'in_transit';
    }

    public function getIsCompletedAttribute()
    {
        return $this->status === 'completed';
    }

    public function getIsCancelledAttribute()
    {
        return $this->status === 'cancelled';
    }

    public function canBeCancelled()
    {
        return in_array($this->status, ['pending', 'in_transit']);
    }

    public function canBeConfirmed()
    {
        return $this->status === 'in_transit';
    }
}


