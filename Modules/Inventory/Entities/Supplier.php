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

    // Calculate total amount purchased
    public function getTotalPurchasedAttribute()
    {
        return $this->orders()->where('status', 'received')->sum('total_amount'); // Assuming 'total_amount' exists on PurchaseOrder
    }

    // Calculate total amount paid
    public function getTotalPaidAttribute()
    {
        return $this->payments()->sum('amount');
    }

    // Calculate outstanding balance
    public function getBalanceAttribute()
    {
        // This is a simplified logic. A more robust system would check for paid/unpaid status on specific POs.
        // For a general ledger, Purchase Total - Payment Total = Balance to Pay
        return $this->total_purchased - $this->total_paid;
    }
}
