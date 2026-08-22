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
     * Map module-specific payment method keys to settings payment method keys.
     */
    public static function normalizePaymentMethod(string $paymentMethod): string
    {
        return match ($paymentMethod) {
            'credit_card', 'debit_card' => 'card',
            'check', 'cheque' => 'bank_transfer',
            'digital_wallet' => 'upi',
            default => $paymentMethod,
        };
    }

    /**
     * Get default payment account for a payment method in a branch
     */
    public static function getDefaultAccount(int $branchId, string $paymentMethod): ?\Modules\Inventory\Entities\PaymentAccount
    {
        $normalizedMethod = static::normalizePaymentMethod($paymentMethod);

        $setting = static::where('branch_id', $branchId)
            ->where('payment_method', $normalizedMethod)
            ->first();

        if (!$setting && $normalizedMethod !== $paymentMethod) {
            $setting = static::where('branch_id', $branchId)
                ->where('payment_method', $paymentMethod)
                ->first();
        }

        if ($setting && $setting->payment_account_id) {
            return $setting->paymentAccount;
        }

        return null;
    }

    /**
     * Resolve the default payment account ID for a branch and payment method.
     */
    public static function resolveDefaultAccountId(int $branchId, string $paymentMethod): ?int
    {
        if ($paymentMethod === 'due') {
            return null;
        }

        return static::getDefaultAccount($branchId, $paymentMethod)?->id;
    }
}

