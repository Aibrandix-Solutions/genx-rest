<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\RoomChargeOrderSettlement;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function itemReport()
    {
        return view('reports.items');
    }

    public function categoryReport()
    {
        return view('reports.category');
    }

    public function salesReport()
    {
        return view('reports.sales');
    }

    public function expenseReport()
    {
        return view('reports.expense-reports');
    }

    public function outstandingPaymentReport()
    {
        return view('reports.outstanding-payment');
    }

    public function expenseSummaryReport()
    {
        return view('reports.expense-summary');
    }

    public function printLog()
    {
        return view('reports.print-log');
    }

    public function deliveryReport()
    {
        return view('reports.delivery-app-report');
    }

    public function kotAdjustmentReport()
    {
        return view('reports.kot-adjustments');
    }

    public function activityLog()
    {
        return view('reports.activity-log');
    }

    public function detailedSalesReport()
    {
        return view('reports.detailed-sales');
    }

    public function roomChargeOrdersReport()
    {
        return view('reports.room-charge-orders');
    }

    public function roomChargeOrdersPrint(Request $request)
    {
        abort_unless(in_array('Report', restaurant_modules()), 403);
        abort_unless(in_array('Hotel', restaurant_modules()), 403);
        abort_unless(user_can('Show Reports'), 403);

        $input = $this->roomChargeOrdersFilterInput($request);
        $startDateTime = \Carbon\Carbon::createFromFormat('m/d/Y H:i', $input['startDate'] . ' ' . $input['startTime'], timezone())->toDateTimeString();
        $endDateTime = \Carbon\Carbon::createFromFormat('m/d/Y H:i', $input['endDate'] . ' ' . $input['endTime'], timezone())->toDateTimeString();

        $query = Order::query()
            ->with(['hotelReservation.guest', 'hotelReservation.room'])
            ->whereNotNull('charged_to_folio_at')
            ->whereBetween('date_time', [$startDateTime, $endDateTime]);

        if ($input['search'] !== '') {
            $term = '%' . $input['search'] . '%';
            $query->where(function ($q) use ($term) {
                $q->where('order_number', 'like', $term)
                    ->orWhere('id', 'like', $term)
                    ->orWhereHas('hotelReservation.guest', fn ($g) => $g->where('first_name', 'like', $term)->orWhere('last_name', 'like', $term))
                    ->orWhereHas('hotelReservation.room', fn ($r) => $r->where('room_number', 'like', $term));
            });
        }

        $orders = $query->orderByDesc('date_time')->get();

        if ($input['filterStatus'] !== '') {
            $orders = $orders->filter(fn ($order) => RoomChargeOrderSettlement::status($order) === $input['filterStatus'])->values();
        }

        return view('reports.room-charge-orders-export', [
            'orders' => $orders,
            'currencyId' => restaurant()->currency_id,
            'printedAt' => now()->timezone(timezone())->format('d M Y, h:i A'),
        ]);
    }

    private function roomChargeOrdersFilterInput(Request $request): array
    {
        return [
            'startDate' => $request->query('startDate', now()->startOfWeek()->format('m/d/Y')),
            'endDate' => $request->query('endDate', now()->endOfWeek()->format('m/d/Y')),
            'startTime' => $request->query('startTime', '00:00'),
            'endTime' => $request->query('endTime', '23:59'),
            'search' => (string) $request->query('search', ''),
            'filterStatus' => (string) $request->query('filterStatus', ''),
        ];
    }

    public function menuItemReport()
    {
        return view('reports.menu-item-report');
    }
}
