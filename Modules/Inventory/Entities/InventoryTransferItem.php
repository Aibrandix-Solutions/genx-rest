<?php

namespace Modules\Inventory\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryTransferItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_transfer_id',
        'source_inventory_item_id',
        'destination_inventory_item_id',
        'unit_id',
        'requested_quantity',
        'confirmed_quantity',
        'status',
        'notes',
    ];

    protected $casts = [
        'requested_quantity' => 'decimal:2',
        'confirmed_quantity' => 'decimal:2',
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(\Modules\Inventory\Entities\Unit::class);
    }

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(InventoryTransfer::class, 'inventory_transfer_id');
    }

    public function sourceItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'source_inventory_item_id');
    }

    public function destinationItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'destination_inventory_item_id');
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

    public function getIsPartiallyReceivedAttribute()
    {
        return $this->status === 'partially_received';
    }
}


