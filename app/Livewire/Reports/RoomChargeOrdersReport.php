<?php

namespace App\Livewire\Reports;

use App\Exports\RoomChargeOrdersReportExport;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class RoomChargeOrdersReport extends Component
{
    use WithPagination;

    public $dateRangeType = 'currentWeek';
    public $startDate;
    public $endDate;
    public $startTime = '00:00';
    public $endTime = '23:59';
    public $search = '';
    public $filterStatus = '';
    public bool $showDetailModal = false;
    public ?Order $selectedOrder = null;

    public function mount(): void
    {
        abort_unless(user_can('Show Reports'), 403);
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
    }

    #[On('setStartDate')]
    public function setStartDate($start): void { $this->startDate = $start; }
    #[On('setEndDate')]
    public function setEndDate($end): void { $this->endDate = $end; }

    public function updatedDateRangeType(): void { $this->setDateRange(); }

    public function showOrder(int $orderId): void
    {
        $this->selectedOrder = Order::with([
            'branch',
            'items.menuItem',
            'items.menuItemVariation',
            'hotelReservation.guest',
            'hotelReservation.room',
            'hotelReservation.branch',
        ])->findOrFail($orderId);

        $this->showDetailModal = true;
    }

    public function closeDetail(): void
    {
        $this->showDetailModal = false;
        $this->selectedOrder = null;
    }

    public function exportQuery(): array
    {
        return array_filter([
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'startTime' => $this->startTime,
            'endTime' => $this->endTime,
            'search' => $this->search,
            'filterStatus' => $this->filterStatus,
        ]);
    }

    public function exportExcel()
    {
        $orders = $this->baseQuery()->get();
        return Excel::download(new RoomChargeOrdersReportExport($orders, (int) restaurant()->currency_id), 'room-charge-orders-' . now()->format('Ymd_His') . '.xlsx');
    }

    public function exportCsv()
    {
        $orders = $this->baseQuery()->get();
        $file = 'room-charge-orders-' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($orders) {
            $h = fopen('php://output', 'w');
            fputcsv($h, [
                __('modules.report.dateAndTime'), __('modules.order.orderNumber'), __('modules.report.room'), __('modules.report.guest'), __('modules.order.amount'), __('app.status'), __('modules.report.settledAmount'), __('modules.report.outstandingAmount'),
            ]);
            foreach ($orders as $order) {
                $status = \App\Services\RoomChargeOrderSettlement::status($order);
                fputcsv($h, [
                    optional($order->date_time)->timezone(timezone())->format('d M Y, h:i A'),
                    $order->show_formatted_order_number,
                    $order->hotelReservation?->room?->room_number ?? '--',
                    $order->hotelReservation?->guest?->full_name ?? '--',
                    number_format((float) $order->total, 2, '.', ''),
                    $status,
                    number_format(\App\Services\RoomChargeOrderSettlement::settledAmount($order), 2, '.', ''),
                    number_format(\App\Services\RoomChargeOrderSettlement::outstandingAmount($order), 2, '.', ''),
                ]);
            }
            fclose($h);
        }, $file);
    }

    public function exportPdf()
    {
        $orders = $this->baseQuery()->get();
        $pdf = Pdf::loadView('reports.room-charge-orders-export', [
            'orders' => $orders,
            'currencyId' => restaurant()->currency_id,
            'printedAt' => now()->timezone(timezone())->format('d M Y, h:i A'),
        ]);
        $pdf->setPaper('A4', 'landscape');
        return response()->streamDownload(fn () => print($pdf->output()), 'room-charge-orders-' . now()->format('Ymd_His') . '.pdf');
    }

    private function baseQuery()
    {
        $query = Order::with(['hotelReservation.guest', 'hotelReservation.room'])
            ->whereNotNull('charged_to_folio_at')
            ->whereBetween('date_time', [
                \Carbon\Carbon::createFromFormat('m/d/Y H:i', $this->startDate . ' ' . $this->startTime, timezone())->toDateTimeString(),
                \Carbon\Carbon::createFromFormat('m/d/Y H:i', $this->endDate . ' ' . $this->endTime, timezone())->toDateTimeString(),
            ]);

        if ($this->search !== '') {
            $term = '%' . $this->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('order_number', 'like', $term)
                    ->orWhere('id', 'like', $term)
                    ->orWhereHas('hotelReservation.guest', fn ($g) => $g->where('first_name', 'like', $term)->orWhere('last_name', 'like', $term))
                    ->orWhereHas('hotelReservation.room', fn ($r) => $r->where('room_number', 'like', $term));
            });
        }

        if ($this->filterStatus !== '') {
            $query->get()->filter(function ($order) {
                return \App\Services\RoomChargeOrderSettlement::status($order) === $this->filterStatus;
            });
        }

        return $query->orderByDesc('date_time');
    }

    public function render()
    {
        $orders = $this->baseQuery()->get();
        if ($this->filterStatus !== '') {
            $orders = $orders->filter(fn ($order) => \App\Services\RoomChargeOrderSettlement::status($order) === $this->filterStatus)->values();
        }

        $paged = new \Illuminate\Pagination\LengthAwarePaginator(
            $orders->forPage(\Illuminate\Pagination\Paginator::resolveCurrentPage(), 15)->values(),
            $orders->count(),
            15,
            \Illuminate\Pagination\Paginator::resolveCurrentPage(),
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('livewire.reports.room-charge-orders-report', [
            'orders' => $paged,
            'currencyId' => restaurant()->currency_id,
        ]);
    }
}
