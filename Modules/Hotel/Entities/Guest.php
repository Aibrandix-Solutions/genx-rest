<?php

namespace Modules\Hotel\Entities;

use App\Models\Customer;
use App\Traits\HasBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Guest extends Model
{
    use HasFactory, HasBranch;

    protected $table = 'hotel_guests';

    protected $fillable = [
        'branch_id',
        'restaurant_id',
        'customer_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'id_type',
        'id_number',
        'address',
        'city',
        'country',
        'preferences',
        'notes',
    ];

    protected $casts = [
        'preferences' => 'array',
    ];

    protected $appends = ['full_name'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    /**
     * Get full name attribute
     */
    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * Link guest to customer account if email matches
     */
    public function linkToCustomer()
    {
        if ($this->email && !$this->customer_id) {
            $customer = Customer::where('email', $this->email)->first();
            
            if ($customer) {
                $this->update(['customer_id' => $customer->id]);
            }
        }
    }
}
