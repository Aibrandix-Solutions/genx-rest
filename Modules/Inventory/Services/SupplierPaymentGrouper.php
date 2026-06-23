<?php

namespace Modules\Inventory\Services;

use Illuminate\Support\Collection;
use Modules\Inventory\Entities\SupplierPayment;

class SupplierPaymentGrouper
{
    public static function batchKey(SupplierPayment $payment): string
    {
        if ($payment->payment_batch_id) {
            return 'batch:' . $payment->payment_batch_id;
        }

        return 'legacy:' . md5(implode('|', [
            (string) $payment->supplier_id,
            $payment->paid_on?->format('Y-m-d H:i:s') ?? '',
            (string) $payment->payment_method,
            (string) ($payment->payment_account_id ?? ''),
            (string) ($payment->added_by ?? ''),
            (string) ($payment->document_path ?? ''),
        ]));
    }

    /**
     * @param  Collection<int, SupplierPayment>  $payments
     * @return Collection<int, array{
     *     key: string,
     *     representative_id: int,
     *     paid_on: \Illuminate\Support\Carbon|null,
     *     payment_method: string,
     *     account: mixed,
     *     note: string|null,
     *     document_path: string|null,
     *     amount: float,
     *     payments: Collection<int, SupplierPayment>,
     *     payment_ids: list<int>,
     * }>
     */
    public static function group(Collection $payments): Collection
    {
        return $payments
            ->groupBy(fn (SupplierPayment $payment) => self::batchKey($payment))
            ->map(function (Collection $group) {
                $first = $group->sortBy('id')->first();

                $note = $group
                    ->pluck('note')
                    ->filter(fn (?string $value) => $value && ! str_contains($value, 'Advance / unallocated credit'))
                    ->unique()
                    ->implode(' | ');

                if ($note === '') {
                    $note = $group->first()->note;
                }

                return [
                    'key' => self::batchKey($first),
                    'representative_id' => $first->id,
                    'paid_on' => $first->paid_on,
                    'payment_method' => $first->payment_method,
                    'account' => $first->account,
                    'note' => $note ?: null,
                    'document_path' => $first->document_path,
                    'amount' => round((float) $group->sum('amount'), 2),
                    'payments' => $group->sortBy('id')->values(),
                    'payment_ids' => $group->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
                ];
            })
            ->sortByDesc(fn (array $group) => $group['paid_on']?->timestamp ?? 0)
            ->values();
    }

    /**
     * @param  Collection<int, SupplierPayment>  $allPayments
     * @return Collection<int, SupplierPayment>
     */
    public static function batchPaymentsFor(Collection $allPayments, int $paymentId): Collection
    {
        $payment = $allPayments->firstWhere('id', $paymentId);

        if (! $payment) {
            return collect();
        }

        $group = self::group($allPayments)->first(
            fn (array $group) => in_array($paymentId, $group['payment_ids'], true),
        );

        return $group['payments'] ?? collect([$payment]);
    }
}
