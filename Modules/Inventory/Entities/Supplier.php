<?php

namespace Modules\Inventory\Entities;

use App\Scopes\BranchScope;
use App\Traits\HasRestaurant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    /**
     * Purchase orders across all branches (inventory is restaurant-wide).
     */
    public function restaurantOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class)->withoutGlobalScope(BranchScope::class);
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

    /**
     * Purchase returns across all branches (inventory is restaurant-wide).
     */
    public function restaurantReturns(): HasMany
    {
        return $this->hasMany(PurchaseReturn::class)->withoutGlobalScope(BranchScope::class);
    }

    // Calculate total amount purchased from received POs (uses persisted total_amount, fast DB aggregate)
    public function getTotalPurchasedAttribute()
    {
        return (float) $this->restaurantOrders()
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
        return (float) $this->restaurantReturns()->sum('total_amount');
    }

    // Calculate outstanding balance owed to supplier
    public function getBalanceAttribute()
    {
        return $this->total_purchased - $this->total_paid - $this->total_returned - $this->total_refunds;
    }
}
