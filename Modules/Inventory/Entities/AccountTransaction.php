<?php

namespace Modules\Inventory\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AccountTransaction extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'transaction_date' => 'datetime',
        'amount' => 'decimal:2'
    ];

    public function account()
    {
        return $this->belongsTo(PaymentAccount::class, 'payment_account_id');
    }

    public function reference()
    {
        return $this->morphTo();
    }
}
