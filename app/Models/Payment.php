<?php

namespace App\Models;

use App\Traits\HasBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\BaseModel;

class Payment extends BaseModel
{
    use HasFactory;
    use HasBranch;

    protected $guarded = ['id'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function paymentAccount(): BelongsTo
    {
        return $this->belongsTo(\Modules\Inventory\Entities\PaymentAccount::class, 'payment_account_id');
    }

    public function accountTransaction()
    {
        return $this->morphOne(\Modules\Inventory\Entities\AccountTransaction::class, 'reference');
    }
}
