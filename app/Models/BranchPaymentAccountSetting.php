<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchPaymentAccountSetting extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $fillable = [
        'branch_id',
        'payment_method',
        'payment_account_id',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function paymentAccount(): BelongsTo
    {
        return $this->belongsTo(\Modules\Inventory\Entities\PaymentAccount::class, 'payment_account_id');
    }

    /**
     * Get default payment account for a payment method in a branch
     */
    public static function getDefaultAccount(int $branchId, string $paymentMethod): ?\Modules\Inventory\Entities\PaymentAccount
    {
        $setting = static::where('branch_id', $branchId)
            ->where('payment_method', $paymentMethod)
            ->first();

        if ($setting && $setting->payment_account_id) {
            return $setting->paymentAccount;
        }

        return null;
    }
}

