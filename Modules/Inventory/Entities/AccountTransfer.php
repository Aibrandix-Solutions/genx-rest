<?php

namespace Modules\Inventory\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AccountTransfer extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'transfer_date' => 'datetime',
        'amount' => 'decimal:2'
    ];

    public function fromAccount()
    {
        return $this->belongsTo(PaymentAccount::class, 'from_account_id');
    }

    public function toAccount()
    {
        return $this->belongsTo(PaymentAccount::class, 'to_account_id');
    }
}
