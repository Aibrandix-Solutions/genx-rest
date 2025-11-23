<?php

namespace Modules\Inventory\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentAccount extends Model
{
    protected $guarded = ['id'];

    public function payments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class);
    }
}


