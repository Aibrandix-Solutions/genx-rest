<?php

namespace Modules\Inventory\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasBranch;
use App\Models\Branch;
use App\Models\User;

class PurchaseReturn extends Model
{
    use HasFactory, HasBranch;

    protected $guarded = ['id'];

    protected $casts = [
        'return_date' => 'date',
        'total_amount' => 'decimal:2'
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by')->withoutGlobalScopes();
    }

    public function items()
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }

    // Relationship to Supplier Payments (for receiving refunds from supplier)
    public function payments()
    {
        return $this->hasMany(SupplierPayment::class, 'purchase_return_id');
    }

    // Helper to get paid amount (refund received)
    public function getPaidAmountAttribute()
    {
        return $this->payments()->sum('amount');
    }

    // Helper to get due amount (refund still due)
    public function getDueAmountAttribute()
    {
        return max(0, $this->total_amount - $this->paid_amount);
    }

    // Helper to determine payment status
    public function getPaymentStatusAttribute()
    {
        if ($this->paid_amount >= $this->total_amount) {
            return 'paid';
        } elseif ($this->paid_amount > 0) {
            return 'partial';
        } else {
            return 'due';
        }
    }
    
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->reference_no)) {
                $model->reference_no = 'PR-' . strtoupper(uniqid());
            }
        });
    }
}

