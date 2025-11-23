<?php

namespace Modules\Inventory\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Branch;
use App\Models\User;

class PurchaseReturn extends Model
{
    use HasFactory;

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
        return $this->belongsTo(User::class, 'added_by');
    }

    public function items()
    {
        return $this->hasMany(PurchaseReturnItem::class);
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

