<?php

namespace Modules\Hotel\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Hotel\Entities\Reservation;
use Modules\Hotel\Entities\RestaurantSettlementAllocation;
use Modules\Hotel\Entities\RestaurantSettlementPayment;

class RestaurantDuesSettlementService
{
    private const EPSILON = 0.0001;

    public function summary(array $filters): array
    {
        $rows = $this->roomGroupedRows($filters);

        return [
            'total_charges' => round((float) $rows->sum('total_charges'), 2),
            'total_paid' => round((float) $rows->sum('total_paid'), 2),
            'outstanding_amount' => round((float) $rows->sum('outstanding_amount'), 2),
        ];
    }

    public function roomGroupedRows(array $filters): Collection
    {
        $orders = $this->outstandingOrdersBaseQuery($filters)
            ->with(['hotelReservation.guest', 'hotelReservation.room', 'branch'])
            ->orderBy('date_time')
            ->get();

        $groups = $orders
            ->filter(fn (Order $order) => $order->hotelReservation !== null)
            ->groupBy(fn (Order $order) => (int) $order->hotel_reservation_id);

        return $groups->map(function (Collection $groupedOrders, int $reservationId) {
            $reservation = $groupedOrders->first()->hotelReservation;
            $restaurantBranchName = $groupedOrders->first()->branch?->name ?? '--';

            $totalCharges = (float) $groupedOrders->sum('total');
            $totalPaid = (float) $groupedOrders->sum(fn (Order $order) => $this->collectedForOrder($order));
            $outstanding = max(0, round($totalCharges - $totalPaid, 2));

            $status = $outstanding <= self::EPSILON
                ? 'paid'
                : ($totalPaid > self::EPSILON ? 'partially_paid' : 'outstanding');

            return [
                'reservation_id' => $reservationId,
                'room_number' => $reservation?->room?->room_number ?? '--',
                'guest_name' => $reservation?->guest?->full_name ?? '--',
                'restaurant_branch_name' => $restaurantBranchName,
                'restaurant_branch_id' => (int) ($groupedOrders->first()->branch_id ?? 0),
                'orders_count' => $groupedOrders->count(),
                'total_charges' => round($totalCharges, 2),
                'total_paid' => round($totalPaid, 2),
                'outstanding_amount' => $outstanding,
                'status' => $status,
                'orders' => $groupedOrders,
            ];
        })->values();
    }

    public function detailsForReservation(int $reservationId, ?int $restaurantBranchId = null): array
    {
        $reservation = Reservation::with(['guest', 'room', 'branch'])->findOrFail($reservationId);

        $ordersQuery = Order::query()
            ->where('hotel_reservation_id', $reservationId)
            ->whereNotNull('charged_to_folio_at')
            ->with('branch')
            ->orderBy('date_time');

        if ($restaurantBranchId) {
            $ordersQuery->where('branch_id', $restaurantBranchId);
        }

        $orders = $ordersQuery->get()->map(function (Order $order) {
            $paid = $this->collectedForOrder($order);
            $outstanding = max(0, round((float) $order->total - $paid, 2));

            return [
                'id' => (int) $order->id,
                'date' => $order->date_time,
                'order_number' => $order->show_formatted_order_number,
                'subtotal' => round((float) ($order->sub_total ?? $order->total), 2),
                'discount' => round((float) ($order->discount_amount ?? 0), 2),
                'tax' => round((float) ($order->tax_amount ?? 0), 2),
                'amount' => round((float) $order->total, 2),
                'paid' => $paid,
                'outstanding' => $outstanding,
                'status' => $outstanding <= self::EPSILON ? 'paid' : ($paid > self::EPSILON ? 'partially_paid' : 'outstanding'),
                'restaurant_branch_id' => (int) $order->branch_id,
                'restaurant_branch_name' => $order->branch?->name ?? '--',
            ];
        });

        return [
            'reservation' => $reservation,
            'orders' => $orders,
            'totals' => [
                'charges' => round((float) $orders->sum('amount'), 2),
                'paid' => round((float) $orders->sum('paid'), 2),
                'outstanding' => round((float) $orders->sum('outstanding'), 2),
            ],
        ];
    }

    public function ledgerRows(array $filters): array
    {
        $debits = $this->outstandingOrdersBaseQuery($filters)
            ->get()
            ->map(function (Order $order) {
                return [
                    'date' => $order->date_time,
                    'description' => 'Room Charge - ' . ($order->show_formatted_order_number ?? ('#' . $order->id)),
                    'debit' => round((float) $order->total, 2),
                    'credit' => 0.0,
                    'reservation_id' => (int) $order->hotel_reservation_id,
                    'restaurant_branch_id' => (int) $order->branch_id,
                ];
            });

        $credits = $this->settlementPaymentsQuery($filters)
            ->get()
            ->map(function (RestaurantSettlementPayment $payment) {
                return [
                    'date' => $payment->payment_date,
                    'description' => 'Settlement Payment',
                    'debit' => 0.0,
                    'credit' => round((float) $payment->amount, 2),
                    'reservation_id' => (int) $payment->reservation_id,
                    'restaurant_branch_id' => (int) $payment->restaurant_branch_id,
                ];
            });

        $entries = $debits
            ->concat($credits)
            ->sortBy(fn (array $entry) => ($entry['date']?->timestamp ?? strtotime((string) $entry['date'])) . '|' . $entry['description'])
            ->values();

        $running = 0.0;
        $rows = $entries->map(function (array $entry) use (&$running) {
            $running += (float) $entry['debit'] - (float) $entry['credit'];
            $entry['balance'] = round($running, 2);
            return $entry;
        });

        return [
            'rows' => $rows,
            'summary' => [
                'total_debit' => round((float) $rows->sum('debit'), 2),
                'total_credit' => round((float) $rows->sum('credit'), 2),
                'outstanding_balance' => round((float) ($rows->last()['balance'] ?? 0), 2),
            ],
        ];
    }

    public function recordSettlementPayment(array $data): RestaurantSettlementPayment
    {
        return DB::transaction(function () use ($data) {
            $userId = auth()->id();
            $remaining = round((float) $data['amount'], 2);

            $payment = RestaurantSettlementPayment::create([
                'restaurant_id' => (int) restaurant()->id,
                'hotel_branch_id' => (int) ($data['hotel_branch_id'] ?? branch()->id),
                'restaurant_branch_id' => (int) $data['restaurant_branch_id'],
                'reservation_id' => (int) $data['reservation_id'],
                'payment_date' => $data['payment_date'],
                'amount' => $remaining,
                'payment_method' => (string) $data['payment_method'],
                'reference_number' => $data['reference_number'] ?? null,
                'remarks' => $data['remarks'] ?? null,
                'created_by_user_id' => $userId,
                'updated_by_user_id' => $userId,
            ]);

            $orders = Order::query()
                ->where('hotel_reservation_id', (int) $data['reservation_id'])
                ->where('branch_id', (int) $data['restaurant_branch_id'])
                ->whereNotNull('charged_to_folio_at')
                ->orderBy('date_time')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($orders as $order) {
                if ($remaining <= self::EPSILON) {
                    break;
                }

                $outstandingBefore = $this->outstandingForOrder($order);

                if ($outstandingBefore <= self::EPSILON) {
                    continue;
                }

                $applied = min($remaining, $outstandingBefore);

                Payment::create([
                    'order_id' => $order->id,
                    'payment_method' => 'hotel_settlement',
                    'amount' => $applied,
                    'transaction_id' => (string) $payment->id,
                    'notes' => 'Hotel settlement payment #' . $payment->id,
                    'restaurant_id' => (int) restaurant()->id,
                    'branch_id' => $order->branch_id,
                ]);

                $this->syncOrderFinancialStatus($order->fresh());

                $remainingAfter = $this->outstandingForOrder($order->fresh());

                RestaurantSettlementAllocation::create([
                    'settlement_payment_id' => $payment->id,
                    'restaurant_order_id' => $order->id,
                    'restaurant_branch_id' => $order->branch_id,
                    'reservation_id' => (int) $data['reservation_id'],
                    'applied_amount' => $applied,
                    'remaining_amount' => $remainingAfter,
                    'allocated_at' => now(),
                    'allocated_by_user_id' => $userId,
                ]);

                $remaining = round($remaining - $applied, 2);
            }

            if ($remaining > self::EPSILON) {
                throw new \RuntimeException(__('hotel::modules.restaurantDues.paymentExceedsOutstanding'));
            }

            return $payment->fresh('allocations');
        });
    }

    private function outstandingOrdersBaseQuery(array $filters)
    {
        $query = Order::query()
            ->whereNotNull('charged_to_folio_at')
            ->whereNotNull('hotel_reservation_id')
            ->whereIn('status', ['billed', 'payment_due', 'paid']);

        if (! empty($filters['restaurant_branch_id'])) {
            $query->where('branch_id', (int) $filters['restaurant_branch_id']);
        }

        if (! empty($filters['room_number'])) {
            $roomNumber = (string) $filters['room_number'];
            $query->whereHas('hotelReservation.room', fn ($roomQuery) => $roomQuery->where('room_number', 'like', '%' . $roomNumber . '%'));
        }

        if (! empty($filters['guest'])) {
            $guest = (string) $filters['guest'];
            $query->whereHas('hotelReservation.guest', function ($guestQuery) use ($guest) {
                $guestQuery->where('first_name', 'like', '%' . $guest . '%')
                    ->orWhere('last_name', 'like', '%' . $guest . '%');
            });
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('date_time', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('date_time', '<=', $filters['date_to']);
        }

        return $query;
    }

    private function settlementPaymentsQuery(array $filters)
    {
        $query = RestaurantSettlementPayment::query()->where('restaurant_id', restaurant()->id);

        if (! empty($filters['restaurant_branch_id'])) {
            $query->where('restaurant_branch_id', (int) $filters['restaurant_branch_id']);
        }

        if (! empty($filters['room_number'])) {
            $roomNumber = (string) $filters['room_number'];
            $query->whereHas('reservation.room', fn ($roomQuery) => $roomQuery->where('room_number', 'like', '%' . $roomNumber . '%'));
        }

        if (! empty($filters['guest'])) {
            $guest = (string) $filters['guest'];
            $query->whereHas('reservation.guest', function ($guestQuery) use ($guest) {
                $guestQuery->where('first_name', 'like', '%' . $guest . '%')
                    ->orWhere('last_name', 'like', '%' . $guest . '%');
            });
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('payment_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('payment_date', '<=', $filters['date_to']);
        }

        return $query;
    }

    public function statusLabel(string $status): string
    {
        return match ($status) {
            'paid' => __('hotel::modules.restaurantDues.statusPaid'),
            'partially_paid' => __('hotel::modules.restaurantDues.statusPartiallyPaid'),
            default => __('hotel::modules.restaurantDues.statusOutstanding'),
        };
    }

    public function collectedForOrder(Order $order): float
    {
        return round((float) $order->payments()->where('payment_method', '!=', 'due')->sum('amount'), 2);
    }

    private function outstandingForOrder(Order $order): float
    {
        return max(0, round((float) $order->total - $this->collectedForOrder($order), 2));
    }

    private function syncOrderFinancialStatus(Order $order): void
    {
        $amountPaid = $this->collectedForOrder($order);
        $outstanding = max(0, round((float) $order->total - $amountPaid, 2));

        Payment::query()
            ->where('order_id', $order->id)
            ->where('payment_method', 'due')
            ->delete();

        if ($outstanding > self::EPSILON) {
            Payment::create([
                'order_id' => $order->id,
                'payment_method' => 'due',
                'amount' => $outstanding,
                'restaurant_id' => (int) restaurant()->id,
                'branch_id' => $order->branch_id,
            ]);
        }

        $order->update([
            'amount_paid' => $amountPaid,
            'status' => $outstanding > self::EPSILON ? 'payment_due' : 'paid',
        ]);
    }
}
