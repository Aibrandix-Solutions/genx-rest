<?php

namespace Modules\Inventory\Support;

use App\Enums\ActivityEvent;
use App\Models\Branch;
use App\Support\ActivityLogger;
use Modules\Inventory\Entities\PurchaseOrder;
use Modules\Inventory\Entities\Supplier;
use Modules\Inventory\Entities\SupplierPayment;

class PurchasePaymentActivityLogger
{
    public static function logCreated(SupplierPayment $payment): void
    {
        self::record($payment, self::eventForPayment($payment, 'created'));
    }

    public static function logCreatedFromBackfill(SupplierPayment $payment): void
    {
        self::record(
            $payment,
            self::eventForPayment($payment, 'created'),
            legacySource: 'supplier_payments',
            legacyId: (int) $payment->id,
        );
    }

    public static function logUpdated(SupplierPayment $payment): void
    {
        self::record($payment, ActivityEvent::PurchasePaymentUpdated);
    }

    public static function logDeleted(SupplierPayment $payment): void
    {
        self::record($payment, ActivityEvent::PurchasePaymentDeleted);
    }

    protected static function eventForPayment(SupplierPayment $payment, string $action): ActivityEvent
    {
        if ($payment->purchase_return_id) {
            return ActivityEvent::PurchaseReturnPaymentCreated;
        }

        return match ($action) {
            'created' => ActivityEvent::PurchasePaymentCreated,
            'updated' => ActivityEvent::PurchasePaymentUpdated,
            'deleted' => ActivityEvent::PurchasePaymentDeleted,
            default => ActivityEvent::PurchasePaymentCreated,
        };
    }

    protected static function record(
        SupplierPayment $payment,
        ActivityEvent $event,
        ?string $legacySource = null,
        ?int $legacyId = null,
    ): void {
        $payment->loadMissing(['purchaseOrder', 'supplier', 'purchaseReturn']);

        [$restaurantId, $branchId] = self::resolveTenantIds($payment);
        $description = self::buildDescription($payment, $event);
        $subject = $payment->purchaseOrder ?? $payment->supplier;

        ActivityLogger::recordEvent(
            activityEvent: $event,
            description: $description,
            subject: $subject,
            properties: self::buildProperties($payment, $event),
            restaurantId: $restaurantId,
            branchId: $branchId,
            causerId: $payment->added_by ? (int) $payment->added_by : null,
            legacySource: $legacySource,
            legacyId: $legacyId,
            createdAt: $payment->paid_on ?? $payment->created_at,
        );
    }

    /**
     * @return array{0: ?int, 1: ?int}
     */
    protected static function resolveTenantIds(SupplierPayment $payment): array
    {
        if ($payment->purchaseOrder?->branch_id) {
            $branchId = (int) $payment->purchaseOrder->branch_id;
            $restaurantId = Branch::query()->where('id', $branchId)->value('restaurant_id');

            return [$restaurantId ? (int) $restaurantId : null, $branchId];
        }

        if ($payment->supplier?->restaurant_id) {
            return [(int) $payment->supplier->restaurant_id, branch()?->id ? (int) branch()->id : null];
        }

        $currentRestaurant = restaurant();
        $currentBranch = branch();

        return [
            $currentRestaurant ? (int) $currentRestaurant->id : null,
            $currentBranch ? (int) $currentBranch->id : null,
        ];
    }

    protected static function buildDescription(SupplierPayment $payment, ActivityEvent $event): string
    {
        $amount = number_format((float) $payment->amount, 2);
        $method = $payment->payment_method ? ucfirst(str_replace('_', ' ', (string) $payment->payment_method)) : 'N/A';

        if ($payment->purchase_return_id) {
            return match ($event) {
                ActivityEvent::PurchasePaymentDeleted => "Purchase return payment deleted: {$amount} ({$method})",
                ActivityEvent::PurchasePaymentUpdated => "Purchase return payment updated: {$amount} ({$method})",
                default => "Purchase return payment recorded: {$amount} ({$method})",
            };
        }

        $poLabel = $payment->purchaseOrder?->po_number
            ?? ($payment->purchase_order_id ? 'PO #' . $payment->purchase_order_id : null);

        if ($poLabel) {
            return match ($event) {
                ActivityEvent::PurchasePaymentDeleted => "Payment deleted for {$poLabel}: {$amount} ({$method})",
                ActivityEvent::PurchasePaymentUpdated => "Payment updated for {$poLabel}: {$amount} ({$method})",
                default => "Payment recorded for {$poLabel}: {$amount} ({$method})",
            };
        }

        $supplierName = $payment->supplier?->name ?? 'supplier';

        return match ($event) {
            ActivityEvent::PurchasePaymentDeleted => "Supplier payment deleted ({$supplierName}): {$amount} ({$method})",
            ActivityEvent::PurchasePaymentUpdated => "Supplier payment updated ({$supplierName}): {$amount} ({$method})",
            default => "Supplier advance payment ({$supplierName}): {$amount} ({$method})",
        };
    }

    protected static function buildProperties(SupplierPayment $payment, ActivityEvent $event): array
    {
        $properties = [
            'supplier_payment_id' => $payment->id,
            'supplier_id' => $payment->supplier_id,
            'supplier_name' => $payment->supplier?->name,
            'purchase_order_id' => $payment->purchase_order_id,
            'po_number' => $payment->purchaseOrder?->po_number,
            'purchase_return_id' => $payment->purchase_return_id,
            'amount' => (float) $payment->amount,
            'payment_method' => $payment->payment_method,
            'payment_account_id' => $payment->payment_account_id,
            'paid_on' => optional($payment->paid_on)->toDateTimeString(),
            'note' => $payment->note,
            'payment_batch_id' => $payment->payment_batch_id ?? null,
            'transaction_id' => $payment->transaction_id ?? null,
        ];

        if ($event === ActivityEvent::PurchasePaymentUpdated) {
            $properties['old_amount'] = $payment->getOriginal('amount');
            $properties['old_payment_method'] = $payment->getOriginal('payment_method');
            $properties['old_paid_on'] = $payment->getOriginal('paid_on');
        }

        return $properties;
    }
}
