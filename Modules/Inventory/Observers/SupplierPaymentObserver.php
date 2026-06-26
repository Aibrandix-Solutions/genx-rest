<?php

namespace Modules\Inventory\Observers;

use Modules\Inventory\Entities\SupplierPayment;
use Modules\Inventory\Support\PurchasePaymentActivityLogger;

class SupplierPaymentObserver
{
    public function created(SupplierPayment $payment): void
    {
        PurchasePaymentActivityLogger::logCreated($payment);
    }

    public function updated(SupplierPayment $payment): void
    {
        if ($payment->wasChanged([
            'amount',
            'payment_method',
            'payment_account_id',
            'paid_on',
            'purchase_order_id',
            'note',
            'transaction_id',
        ])) {
            PurchasePaymentActivityLogger::logUpdated($payment);
        }
    }

    public function deleting(SupplierPayment $payment): void
    {
        PurchasePaymentActivityLogger::logDeleted($payment);
    }
}
