<?php

namespace App\Livewire\Reports;

use App\Models\Order;
use App\Services\RoomChargeOrderSettlement;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Hotel\Services\OrderFolioSettlement;

class RoomChargeOrdersReport extends Component
{
    use WithPagination;

    public $dateRangeType = 'currentWeek';

    public $startDate;

    public $endDate;

    public $startTime = '00:00';

    public $endTime = '23:59';

    public $currencyId;

    public $search = '';

    public $filterStatus = '';

    public $perPage = 15;

    public $showDetailModal = false;

    public ?Order $selectedOrder = null;

    public function mount(): void
    {
        abort_unless(in_array('Report', restaurant_modules()), 403);
        abort_unless(in_array('Hotel', restaurant_modules()), 403);
        abort_unless(user_can('Show Reports'), 403);

        $this->currencyId = restaurant()->currency_id;
        $this->dateRangeType = request()->cookie('room_charge_orders_report_date_range_type', 'currentWeek');
        $this->setDateRange();
    }

    public function setDateRange(): void
    {
        $ranges = [
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'yesterday' => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
            'lastWeek' => [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()],
            'last7Days' => [now()->subDays(6)->startOfDay(), now()->endOfDay()],
            'currentMonth' => [now()->startOfMonth(), now()->endOfMonth()],
            'lastMonth' => [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()],
            'currentYear' => [now()->startOfYear(), now()->endOfYear()],
            'lastYear' => [now()->subYear()->startOfYear(), now()->subYear()->endOfYear()],
            'currentWeek' => [now()->startOfWeek(), now()->endOfWeek()],
        ];

        [$start, $end] = $ranges[$this->dateRangeType] ?? $ranges['currentWeek'];
        $this->startDate = $start->format('m/d/Y');
        $this->endDate = $end->format('m/d/Y');
        $this->persistDateCookies();
    }

    private function persistDateCookies(): void
    {
        $ttl = 60 * 24 * 30;
        cookie()->queue(cookie('room_charge_orders_report_date_range_type', $this->dateRangeType, $ttl));
    }

    #[On('setStartDate')]
    public function setStartDate($start): void
    {
        $this->startDate = $start;
        $this->dateRangeType = 'custom';
        $this->persistDateCookies();
    }

    #[On('setEndDate')]
    public function setEndDate($end): void
    {
        $this->endDate = $end;
        $this->dateRangeType = 'custom';
        $this->persistDateCookies();
    }

    public function updatedDateRangeType($value): void
    {
        cookie()->queue(cookie('room_charge_orders_report_date_range_type', $value, 60 * 24 * 30));

        if ($value !== 'custom') {
            $this->setDateRange();
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function showOrder(int $orderId): void
    {
        $this->selectedOrder = Order::query()
            ->with([
                'branch',
                'items.menuItem',
                'items.menuItemVariation',
                'hotelReservation.guest',
                'hotelReservation.room',
                'hotelReservation.branch',
            ])
            ->whereNotNull('charged_to_folio_at')
            ->findOrFail($orderId);

        $this->showDetailModal = true;
    }

    public function closeDetail(): void
    {
        $this->showDetailModal = false;
        $this->selectedOrder = null;
    }

    private function prepareDateTimeData(): array
    {
        $timezone = timezone();

        $startDateTime = Carbon::createFromFormat('m/d/Y H:i', $this->startDate . ' ' . $this->startTime, $timezone)
            ->toDateTimeString();

        $endDateTime = Carbon::createFromFormat('m/d/Y H:i', $this->endDate . ' ' . $this->endTime, $timezone)
            ->toDateTimeString();

        $startTime = Carbon::parse($this->startTime, $timezone)->format('H:i');
        $endTime = Carbon::parse($this->endTime, $timezone)->format('H:i');

        return compact('timezone', 'startDateTime', 'endDateTime', 'startTime', 'endTime');
    }

    private function baseQuery(array $dateTimeData)
    {
        $query = Order::query()
            ->with([
                'hotelReservation.guest',
                'hotelReservation.room',
                'payments',
            ])
            ->whereNotNull('charged_to_folio_at')
            ->whereBetween('date_time', [$dateTimeData['startDateTime'], $dateTimeData['endDateTime']]);

        if ($dateTimeData['startTime'] < $dateTimeData['endTime']) {
            $query->whereRaw('TIME(date_time) BETWEEN ? AND ?', [
                $dateTimeData['startTime'],
                $dateTimeData['endTime'],
            ]);
        } else {
            $query->where(function ($sub) use ($dateTimeData) {
                $sub->whereRaw('TIME(date_time) >= ?', [$dateTimeData['startTime']])
                    ->orWhereRaw('TIME(date_time) <= ?', [$dateTimeData['endTime']]);
            });
        }

        if ($this->search !== '') {
            $term = '%' . $this->search . '%';
            $query->where(function ($sub) use ($term) {
                $sub->where('order_number', 'like', $term)
                    ->orWhere('id', 'like', $term)
                    ->orWhereHas('hotelReservation.guest', function ($guestQuery) use ($term) {
                        $guestQuery->where('first_name', 'like', $term)
                            ->orWhere('last_name', 'like', $term);
                    })
                    ->orWhereHas('hotelReservation.room', function ($roomQuery) use ($term) {
                        $roomQuery->where('room_number', 'like', $term);
                    });
            });
        }

        if ($this->filterStatus === RoomChargeOrderSettlement::STATUS_PAID) {
            $query->where(function ($sub) {
                $sub->where('status', 'paid')
                    ->orWhereNotNull('folio_settled_at')
                    ->orWhere('status', OrderFolioSettlement::STATUS_FOLIO_SETTLED);
            });
        } elseif ($this->filterStatus === RoomChargeOrderSettlement::STATUS_OUTSTANDING) {
            $query->whereNull('folio_settled_at')
                ->whereNotIn('status', ['paid', OrderFolioSettlement::STATUS_FOLIO_SETTLED])
                ->whereDoesntHave('payments', function ($paymentQuery) {
                    $paymentQuery->where('payment_method', '!=', 'due');
                });
        } elseif ($this->filterStatus === RoomChargeOrderSettlement::STATUS_PARTIALLY_PAID) {
            $query->whereNull('folio_settled_at')
                ->whereNotIn('status', ['paid', OrderFolioSettlement::STATUS_FOLIO_SETTLED])
                ->whereHas('payments', function ($paymentQuery) {
                    $paymentQuery->where('payment_method', '!=', 'due');
                })
                ->whereRaw(
                    '(SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payments.order_id = orders.id AND payments.payment_method != ?) < orders.total - 0.01',
                    ['due']
                );
        }

        return $query->orderByDesc('date_time');
    }

    public function render()
    {
        $dateTimeData = $this->prepareDateTimeData();
        $orders = $this->baseQuery($dateTimeData)->paginate($this->perPage);

        return view('livewire.reports.room-charge-orders-report', [
            'orders' => $orders,
            'currencyId' => $this->currencyId,
        ]);
    }
}
