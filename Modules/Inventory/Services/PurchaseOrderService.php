<?php

namespace Modules\Inventory\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Modules\Inventory\Entities\AccountTransaction;
use Modules\Inventory\Entities\InventoryMovement;
use Modules\Inventory\Entities\InventoryStock;
use Modules\Inventory\Entities\PaymentAccount;
use Modules\Inventory\Entities\PurchaseAttachment;
use Modules\Inventory\Entities\PurchaseOrder;
use Modules\Inventory\Entities\PurchaseOrderAuditLog;
use Modules\Inventory\Entities\PurchaseReturn;
use Modules\Inventory\Entities\SupplierPayment;

class PurchaseOrderService
{
    public function effectiveTotal(PurchaseOrder $purchaseOrder): float
    {
        return (float) ($purchaseOrder->total_amount ?? $purchaseOrder->final_total);
    }

    public function logAmountChange(
        PurchaseOrder $purchaseOrder,
        float $oldAmount,
        float $newAmount,
        ?string $notes = null,
        array $metadata = []
    ): void {
        $difference = round($newAmount - $oldAmount, 2);

        if (abs($difference) < 0.01) {
            return;
        }

        PurchaseOrderAuditLog::create([
            'purchase_order_id' => $purchaseOrder->id,
            'supplier_id' => $purchaseOrder->supplier_id,
            'po_number' => $purchaseOrder->po_number,
            'action' => 'amount_updated',
            'old_amount' => $oldAmount,
            'new_amount' => $newAmount,
            'amount_difference' => $difference,
            'performed_by' => auth()->id(),
            'notes' => $notes,
            'metadata' => $metadata,
        ]);
    }

    public function deletePurchaseOrder(PurchaseOrder $purchaseOrder): void
    {
        DB::transaction(function () use ($purchaseOrder) {
            $purchaseOrder = PurchaseOrder::query()
                ->with(['items.inventoryItem', 'payments', 'attachments', 'location'])
                ->lockForUpdate()
                ->findOrFail($purchaseOrder->id);

            if (PurchaseReturn::where('purchase_order_id', $purchaseOrder->id)->exists()) {
                throw new \RuntimeException(
                    trans('inventory::modules.purchaseOrder.cannot_delete_with_returns')
                );
            }

            $oldAmount = $this->effectiveTotal($purchaseOrder);

            if ($purchaseOrder->status === 'received') {
                $this->reverseReceivedStock($purchaseOrder);
            }

            $this->reversePayments($purchaseOrder);
            $this->deleteAttachments($purchaseOrder);

            PurchaseOrderAuditLog::create([
                'purchase_order_id' => $purchaseOrder->id,
                'supplier_id' => $purchaseOrder->supplier_id,
                'po_number' => $purchaseOrder->po_number,
                'action' => 'deleted',
                'old_amount' => $oldAmount,
                'new_amount' => null,
                'amount_difference' => -$oldAmount,
                'performed_by' => auth()->id(),
                'notes' => trans('inventory::modules.purchaseOrder.audit_deleted_note', [
                    'po_number' => $purchaseOrder->po_number,
                ]),
                'metadata' => [
                    'status' => $purchaseOrder->status,
                    'invoice_no' => $purchaseOrder->invoice_no,
                ],
            ]);

            $purchaseOrder->delete();
        });
    }

    protected function reverseReceivedStock(PurchaseOrder $purchaseOrder): void
    {
        $location = $purchaseOrder->location;

        if (!$location) {
            throw new \RuntimeException(trans('inventory::modules.purchaseOrder.location_not_found'));
        }

        $targetBranchId = $location->type === 'branch' && $location->branch_id
            ? $location->branch_id
            : $purchaseOrder->branch_id;

        foreach ($purchaseOrder->items as $item) {
            $quantity = (float) $item->quantity;
            $unitPrice = (float) $item->unit_price;

            if ($quantity <= 0) {
                continue;
            }

            $stock = InventoryStock::query()
                ->where('inventory_item_id', $item->inventory_item_id)
                ->where('branch_id', $targetBranchId)
                ->where('location_id', $purchaseOrder->location_id)
                ->lockForUpdate()
                ->first();

            if (!$stock || (float) $stock->quantity < $quantity) {
                $itemName = $item->inventoryItem->name ?? trans('inventory::modules.purchaseOrder.item');
                throw new \RuntimeException(
                    trans('inventory::modules.purchaseOrder.cannot_delete_insufficient_stock', [
                        'item' => $itemName,
                    ])
                );
            }

            $stock->decrement('quantity', $quantity);

            InventoryMovement::create([
                'branch_id' => $targetBranchId,
                'location_id' => $purchaseOrder->location_id,
                'inventory_item_id' => $item->inventory_item_id,
                'quantity' => $quantity,
                'transaction_type' => 'out',
                'unit_purchase_price' => $unitPrice,
                'supplier_id' => $purchaseOrder->supplier_id,
                'added_by' => auth()->id(),
            ]);
        }
    }

    protected function reversePayments(PurchaseOrder $purchaseOrder): void
    {
        foreach ($purchaseOrder->payments as $payment) {
            $this->reverseSupplierPayment($payment, false);
        }
    }

    public function reverseSupplierPayment(SupplierPayment $payment, bool $useTransaction = true): void
    {
        $this->reverseSupplierPaymentBatch(collect([$payment]), $useTransaction);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, SupplierPayment>  $payments
     */
    public function reverseSupplierPaymentBatch($payments, bool $useTransaction = true): void
    {
        $callback = function () use ($payments) {
            $paymentIds = $payments->pluck('id')->filter()->values()->all();

            if ($paymentIds === []) {
                return;
            }

            $lockedPayments = SupplierPayment::query()
                ->whereIn('id', $paymentIds)
                ->lockForUpdate()
                ->get();

            $transactions = AccountTransaction::query()
                ->where('reference_type', SupplierPayment::class)
                ->whereIn('reference_id', $paymentIds)
                ->get();

            if ($transactions->isNotEmpty()) {
                foreach ($transactions as $transaction) {
                    $account = PaymentAccount::query()
                        ->lockForUpdate()
                        ->find($transaction->payment_account_id);

                    if (! $account) {
                        throw new \RuntimeException(
                            trans('inventory::modules.purchaseOrder.payment_account_missing_revert')
                        );
                    }

                    // credit = money out (payment to supplier) → restore by incrementing
                    // debit  = money in (refund from supplier) → restore by decrementing
                    if ($transaction->type === 'debit') {
                        $account->decrement('current_balance', (float) $transaction->amount);
                    } else {
                        $account->increment('current_balance', (float) $transaction->amount);
                    }

                    $transaction->delete();
                }
            } else {
                foreach ($lockedPayments as $payment) {
                    if (! $payment->payment_account_id) {
                        continue;
                    }

                    $account = PaymentAccount::query()
                        ->lockForUpdate()
                        ->find($payment->payment_account_id);

                    if (! $account) {
                        throw new \RuntimeException(
                            trans('inventory::modules.purchaseOrder.payment_account_missing_revert')
                        );
                    }

                    $account->increment('current_balance', (float) $payment->amount);
                }
            }

            SupplierPayment::query()->whereIn('id', $paymentIds)->delete();
        };

        if ($useTransaction) {
            DB::transaction($callback);
        } else {
            $callback();
        }
    }

    protected function deleteAttachments(PurchaseOrder $purchaseOrder): void
    {
        foreach ($purchaseOrder->attachments as $attachment) {
            $path = public_path('user-uploads/' . PurchaseAttachment::UPLOAD_DIR . '/' . $attachment->file_path);
            if (File::exists($path)) {
                File::delete($path);
            }

            $attachment->delete();
        }
    }
}
