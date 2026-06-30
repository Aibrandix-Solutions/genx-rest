<?php

namespace Modules\Inventory\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasRestaurant;
use Illuminate\Notifications\Notifiable;
use Modules\Inventory\Entities\PurchaseOrder;
// use Modules\Inventory\Database\Factories\SupplierFactory;

class Supplier extends Model
{
    use HasFactory;
    use HasRestaurant;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = ['id'];

    public function orders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(SupplierDocument::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(PurchaseReturn::class);
    }

    // Calculate total amount purchased from received POs (uses persisted total_amount, fast DB aggregate)
    public function getTotalPurchasedAttribute()
    {
        return (float) $this->orders()
            ->where('status', 'received')
            ->sum('total_amount');
    }

    // Calculate total amount paid to supplier (excludes return refunds received from supplier)
    public function getTotalPaidAttribute()
    {
        return (float) $this->payments()->whereNull('purchase_return_id')->sum('amount');
    }

    // Calculate total refunds received from supplier (cash back on purchase returns)
    public function getTotalRefundsAttribute()
    {
        return (float) $this->payments()->whereNotNull('purchase_return_id')->sum('amount');
    }

    // Total returned goods (reduces amount owed; matches supplier ledger credits)
    public function getTotalReturnedAttribute()
    {
        return (float) $this->returns()->sum('total_amount');
    }

    // Calculate outstanding balance owed to supplier
    public function getBalanceAttribute()
    {
        return $this->total_purchased - $this->total_paid - $this->total_returned - $this->total_refunds;
    }
}
