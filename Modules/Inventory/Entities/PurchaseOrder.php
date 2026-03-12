<?php

namespace Modules\Inventory\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use App\Traits\HasBranch;
use App\Models\User;

class PurchaseOrder extends Model
{
    use HasFactory;
    use HasBranch;

    protected $guarded = ['id'];

    protected $casts = [
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'total_amount' => 'decimal:2',
        'discount' => 'decimal:2',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(PurchaseLocation::class);
    }

    // New: Relationship to Supplier Payments
    public function payments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class);
    }

    // Relationship to Purchase Attachments
    public function attachments(): HasMany
    {
        return $this->hasMany(PurchaseAttachment::class);
    }

    // Calculate subtotal from items (before discount)
    public function getSubtotalAttribute()
    {
        return $this->items()->sum(\DB::raw('(quantity * unit_price)'));
    }

    // Calculate discount amount based on type
    public function getDiscountAmountAttribute()
    {
        if (!$this->discount) {
            return 0;
        }
        
        if ($this->discount_type === 'percentage') {
            return ($this->subtotal * $this->discount) / 100;
        }
        
        return $this->discount;
    }

    // Calculate final total after discount
    public function getFinalTotalAttribute()
    {
        return max(0, $this->subtotal - $this->discount_amount);
    }

    // Helper to get paid amount
    public function getPaidAmountAttribute()
    {
        return $this->payments()->sum('amount');
    }

    // Helper to get due amount
    public function getDueAmountAttribute()
    {
        return max(0, $this->final_total - $this->paid_amount);
    }

    // Helper to determine payment status
    public function getPaymentStatusAttribute()
    {
        if ($this->paid_amount >= $this->final_total) {
            return 'paid';
        } elseif ($this->paid_amount > 0) {
            return 'partial';
        } else {
            return 'due';
        }
    }

    public function generatePoNumber()
    {
        // Format: PO-BRANCHID-YEAR-SEQUENCE
        // Example: PO-1-2023-0001
        
        $prefix = 'PO-' . ($this->branch_id ?? '0') . '-' . date('Y') . '-';
        
        // Find the last PO number with this prefix to increment
        $lastPo = self::where('po_number', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastPo) {
            // Extract sequence
            $lastSequence = (int) substr($lastPo->po_number, strlen($prefix));
            $sequence = str_pad($lastSequence + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $sequence = '0001';
        }

        $this->po_number = $prefix . $sequence;
    }
}
