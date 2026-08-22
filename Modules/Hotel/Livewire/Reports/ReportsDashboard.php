<?php

namespace Modules\Hotel\Livewire\Reports;

use Carbon\Carbon;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Modules\Hotel\Entities\Room;
use Modules\Hotel\Entities\RoomType;
use Modules\Hotel\Entities\Reservation;
use Modules\Hotel\Entities\HotelPayment;
use Modules\Hotel\Entities\HousekeepingTask;

class ReportsDashboard extends Component
{
    // ── Filters ────────────────────────────────────────────────────────────
    public string $dateFrom    = '';
    public string $dateTo      = '';
    public string $activeTab   = 'overview';   // overview | reservations | revenue | housekeeping
    public string $statusFilter   = '';        // For reservations tab
    public string $roomTypeFilter = '';        // For reservations + revenue tabs

    // ── Dataset properties (loaded per tab) ────────────────────────────────
    public array $stats               = [];
    public array $reservationsReport  = [];
    public array $revenueReport       = [];
    public array $housekeepingReport  = [];

    // ── Misc ───────────────────────────────────────────────────────────────
    public array $roomTypes = [];

    // ──────────────────────────────────────────────────────────────────────
    public function mount(): void
    {
        abort_unless(user_can('view_hotel_reports'), 403);

        $this->dateFrom   = Carbon::now()->subDays(30)->toDateString();
        $this->dateTo     = Carbon::now()->toDateString();
        $this->roomTypes  = RoomType::select('id', 'name')
            ->get()
            ->toArray();

        $this->loadAll();
    }

    // ── Watchers ───────────────────────────────────────────────────────────
    public function updatedDateFrom():     void { $this->loadAll(); }
    public function updatedDateTo():       void { $this->loadAll(); }
    public function updatedActiveTab():    void { $this->loadAll(); }
    public function updatedStatusFilter(): void { $this->loadReservationsReport(); }
    public function updatedRoomTypeFilter(): void
    {
        if ($this->activeTab === 'reservations') $this->loadReservationsReport();
        if ($this->activeTab === 'revenue')      $this->loadRevenueReport();
    }

    // ── Load orchestrator ─────────────────────────────────────────────────
    public function loadAll(): void
    {
        $this->loadOverview();

        match($this->activeTab) {
            'reservations' => $this->loadReservationsReport(),
            'revenue'      => $this->loadRevenueReport(),
            'housekeeping' => $this->loadHousekeepingReport(),
            default        => null,
        };
    }

    // ── Tab 1: Overview KPIs ──────────────────────────────────────────────
    protected function loadOverview(): void
    {
        $start = Carbon::parse($this->dateFrom)->startOfDay();
        $end   = Carbon::parse($this->dateTo)->endOfDay();

        $totalRooms       = Room::count();
        $occupiedRooms    = Room::where('status', Room::STATUS_OCCUPIED)->count();
        $reservedRooms    = Room::where('status', Room::STATUS_RESERVED)->count();
        $cleaningRooms    = Room::where('status', Room::STATUS_CLEANING)->count();
        $maintenanceRooms = Room::where('status', Room::STATUS_MAINTENANCE)->count();
        $availableRooms   = Room::where('status', Room::STATUS_AVAILABLE)->count();
        $occupancyRate    = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 1) : 0;

        $reservations = Reservation::whereBetween('check_in_date', [$start->toDateString(), $end->toDateString()]);
        $payments     = HotelPayment::whereBetween('created_at', [$start, $end]);

        $reservationsInRange   = (clone $reservations)->count();
        $confirmedReservations = (clone $reservations)->where('status', Reservation::STATUS_CONFIRMED)->count();
        $checkedInCount        = (clone $reservations)->where('status', Reservation::STATUS_CHECKED_IN)->count();
        $checkedOutCount       = (clone $reservations)->where('status', Reservation::STATUS_CHECKED_OUT)->count();
        $cancelledCount        = (clone $reservations)->where('status', Reservation::STATUS_CANCELLED)->count();
        $noShowCount           = (clone $reservations)->where('status', Reservation::STATUS_NO_SHOW ?? 'no_show')->count();

        $checkInsToday  = Reservation::whereDate('check_in_date', Carbon::today())->count();
        $checkOutsToday = Reservation::whereDate('checkout_date', Carbon::today())->count();

        $totalRevenue  = (clone $payments)->where('payment_type', '!=', HotelPayment::TYPE_REFUND)->sum('amount');
        $totalRefunds  = (clone $payments)->where('payment_type', HotelPayment::TYPE_REFUND)->sum('amount');
        $netRevenue    = $totalRevenue - $totalRefunds;

        $avgLengthOfStay = Reservation::whereBetween('check_in_date', [$start->toDateString(), $end->toDateString()])
            ->whereNotNull('checkout_date')
            ->selectRaw('AVG(DATEDIFF(checkout_date, check_in_date)) as avg_los')
            ->value('avg_los');

        $this->stats = [
            'total_rooms'            => $totalRooms,
            'occupied_rooms'         => $occupiedRooms,
            'reserved_rooms'         => $reservedRooms,
            'cleaning_rooms'         => $cleaningRooms,
            'maintenance_rooms'      => $maintenanceRooms,
            'available_rooms'        => $availableRooms,
            'occupancy_rate'         => $occupancyRate,
            'reservations_in_range'  => $reservationsInRange,
            'confirmed_reservations' => $confirmedReservations,
            'checked_in_count'       => $checkedInCount,
            'checked_out_count'      => $checkedOutCount,
            'cancelled_reservations' => $cancelledCount,
            'no_show_count'          => $noShowCount,
            'check_ins_today'        => $checkInsToday,
            'check_outs_today'       => $checkOutsToday,
            'total_revenue'          => $totalRevenue,
            'total_refunds'          => $totalRefunds,
            'net_revenue'            => $netRevenue,
            'avg_length_of_stay'     => round($avgLengthOfStay ?? 0, 1),
        ];
    }

    // ── Tab 2: Reservations Report ─────────────────────────────────────────
    public function loadReservationsReport(): void
    {
        $start = Carbon::parse($this->dateFrom)->startOfDay();
        $end   = Carbon::parse($this->dateTo)->endOfDay();

        $query = Reservation::with(['guest', 'room.roomType'])
            ->whereBetween('check_in_date', [$start->toDateString(), $end->toDateString()]);

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }
        if ($this->roomTypeFilter) {
            $query->whereHas('room', fn($q) => $q->where('room_type_id', $this->roomTypeFilter));
        }

        $rows = $query->orderByDesc('check_in_date')->get();

        $this->reservationsReport = $rows->map(fn($r) => [
            'id'                 => $r->id,
            'reservation_number' => $r->reservation_number,
            'guest_name'         => $r->guest?->full_name ?? '—',
            'room_number'        => $r->room?->room_number ?? '—',
            'room_type'          => $r->room?->roomType?->name ?? '—',
            'check_in_date'      => $r->check_in_date?->format('d M Y'),
            'checkout_date'      => $r->checkout_date?->format('d M Y'),
            'nights'             => $r->check_in_date && $r->checkout_date
                                    ? $r->check_in_date->diffInDays($r->checkout_date) : 0,
            'adults'             => $r->adults,
            'children'           => $r->children,
            'status'             => $r->status,
            'total_amount'       => $r->total_amount,
            'paid_amount'        => $r->paid_amount,
            'balance_due'        => $r->balance_due,
            'booking_source'     => $r->booking_source,
        ])->toArray();
    }

    // ── Tab 3: Revenue Report (per Room Type) ──────────────────────────────
    public function loadRevenueReport(): void
    {
        $start = Carbon::parse($this->dateFrom)->startOfDay();
        $end   = Carbon::parse($this->dateTo)->endOfDay();

        $query = RoomType::withCount(['rooms as room_count'])
            ->withCount(['rooms as rooms_currently_occupied' => fn($q) => $q->where('status', Room::STATUS_OCCUPIED)]);

        if ($this->roomTypeFilter) {
            $query->where('id', $this->roomTypeFilter);
        }

        $roomTypes = $query->get();

        $report = [];
        foreach ($roomTypes as $rt) {
            // All reservations for this room type in date range
            $reservations = Reservation::whereHas('room', fn($q) => $q->where('room_type_id', $rt->id))
                ->whereBetween('check_in_date', [$start->toDateString(), $end->toDateString()])
                ->whereNotIn('status', [Reservation::STATUS_CANCELLED, Reservation::STATUS_NO_SHOW ?? 'no_show'])
                ->get();

            $bookingCount = $reservations->count();
            $totalRevenue = $reservations->sum('total_amount');
            $totalNights  = $reservations->sum(fn($r) => $r->check_in_date && $r->checkout_date
                ? $r->check_in_date->diffInDays($r->checkout_date) : 0);

            $avgRate = ($totalNights > 0) ? ($totalRevenue / $totalNights) : $rt->base_price;

            // Room-night occupancy % = nights occupied / (rooms * days in range)
            $daysInRange = max(1, Carbon::parse($this->dateFrom)->diffInDays(Carbon::parse($this->dateTo)) + 1);
            $maxRoomNights = $rt->room_count * $daysInRange;
            $occupancyPct  = $maxRoomNights > 0 ? round(($totalNights / $maxRoomNights) * 100, 1) : 0;

            $report[] = [
                'room_type'          => $rt->name,
                'total_rooms'        => $rt->room_count,
                'booking_count'      => $bookingCount,
                'total_nights'       => $totalNights,
                'total_revenue'      => $totalRevenue,
                'avg_nightly_rate'   => $avgRate,
                'base_price'         => $rt->base_price,
                'occupancy_pct'      => $occupancyPct,
            ];
        }

        usort($report, fn($a, $b) => $b['total_revenue'] <=> $a['total_revenue']);
        $this->revenueReport = $report;
    }

    // ── Tab 4: Housekeeping Report ─────────────────────────────────────────
    public function loadHousekeepingReport(): void
    {
        $start = Carbon::parse($this->dateFrom)->startOfDay();
        $end   = Carbon::parse($this->dateTo)->endOfDay();

        $rows = HousekeepingTask::whereBetween('created_at', [$start, $end])
            ->select('task_type', 'status', 'priority', DB::raw('COUNT(*) as count'),
                DB::raw('AVG(TIMESTAMPDIFF(MINUTE, started_at, completed_at)) as avg_completion_minutes'))
            ->groupBy('task_type', 'status', 'priority')
            ->get();

        // Summary totals
        $totals = [
            'total'       => $rows->sum('count'),
            'pending'     => $rows->where('status', HousekeepingTask::STATUS_PENDING)->sum('count'),
            'in_progress' => $rows->where('status', HousekeepingTask::STATUS_IN_PROGRESS)->sum('count'),
            'completed'   => $rows->where('status', HousekeepingTask::STATUS_COMPLETED)->sum('count'),
        ];

        // Build per-type breakdown
        $byType = [];
        foreach ($rows as $row) {
            $key = $row->task_type;
            if (!isset($byType[$key])) {
                $byType[$key] = ['task_type' => $key, 'total' => 0, 'pending' => 0, 'in_progress' => 0, 'completed' => 0, 'avg_mins' => 0, '_completed_count' => 0];
            }
            $byType[$key]['total'] += $row->count;
            $byType[$key][$row->status === 'in_progress' ? 'in_progress' : $row->status] += $row->count;
            if ($row->status === HousekeepingTask::STATUS_COMPLETED && $row->avg_completion_minutes) {
                $byType[$key]['avg_mins'] += $row->avg_completion_minutes * $row->count;
                $byType[$key]['_completed_count'] += $row->count;
            }
        }

        // Compute weighted avg completion time
        foreach ($byType as &$t) {
            $t['avg_mins'] = $t['_completed_count'] > 0 ? round($t['avg_mins'] / $t['_completed_count']) : 0;
            unset($t['_completed_count']);
        }

        $this->housekeepingReport = ['totals' => $totals, 'by_type' => array_values($byType)];
    }

    // ── CSV Export ─────────────────────────────────────────────────────────
    public function exportCsv(): mixed
    {
        $tab = $this->activeTab;
        $this->loadAll();

        $filename = "hotel-{$tab}-report-" . now()->format('Y-m-d-H-i-s') . '.csv';
        $headers  = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = match($tab) {
            'reservations' => fn() => $this->writeReservationsCsv(),
            'revenue'      => fn() => $this->writeRevenueCsv(),
            'housekeeping' => fn() => $this->writeHousekeepingCsv(),
            default        => fn() => $this->writeOverviewCsv(),
        };

        return response()->stream($callback, 200, $headers);
    }

    private function writeOverviewCsv(): void
    {
        $f = fopen('php://output', 'w');
        fprintf($f, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($f, ['Metric', 'Value']);
        $labels = [
            'total_rooms'           => 'Total Rooms',
            'occupied_rooms'        => 'Occupied Rooms',
            'available_rooms'       => 'Available Rooms',
            'occupancy_rate'        => 'Occupancy Rate (%)',
            'reservations_in_range' => 'Reservations in Range',
            'check_ins_today'       => 'Check-ins Today',
            'check_outs_today'      => 'Check-outs Today',
            'cancelled_reservations'=> 'Cancelled',
            'total_revenue'         => 'Total Revenue',
            'total_refunds'         => 'Total Refunds',
            'net_revenue'           => 'Net Revenue',
            'avg_length_of_stay'    => 'Avg Length of Stay (nights)',
        ];
        foreach ($labels as $key => $label) {
            fputcsv($f, [$label, $this->stats[$key] ?? 0]);
        }
        fclose($f);
    }

    private function writeReservationsCsv(): void
    {
        $f = fopen('php://output', 'w');
        fprintf($f, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($f, ['Reservation #', 'Guest', 'Room', 'Type', 'Check-in', 'Checkout', 'Nights', 'Adults', 'Children', 'Status', 'Total Amount', 'Paid', 'Balance Due', 'Source']);
        foreach ($this->reservationsReport as $r) {
            fputcsv($f, [
                $r['reservation_number'], $r['guest_name'], $r['room_number'], $r['room_type'],
                $r['check_in_date'], $r['checkout_date'], $r['nights'],
                $r['adults'], $r['children'], ucfirst(str_replace('_', ' ', $r['status'])),
                number_format($r['total_amount'], 2), number_format($r['paid_amount'], 2),
                number_format($r['balance_due'], 2), $r['booking_source'],
            ]);
        }
        fclose($f);
    }

    private function writeRevenueCsv(): void
    {
        $f = fopen('php://output', 'w');
        fprintf($f, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($f, ['Room Type', 'Total Rooms', 'Bookings', 'Total Nights', 'Total Revenue', 'Avg Nightly Rate', 'Base Price', 'Occupancy %']);
        foreach ($this->revenueReport as $r) {
            fputcsv($f, [
                $r['room_type'], $r['total_rooms'], $r['booking_count'], $r['total_nights'],
                number_format($r['total_revenue'], 2), number_format($r['avg_nightly_rate'], 2),
                number_format($r['base_price'], 2), $r['occupancy_pct'] . '%',
            ]);
        }
        fclose($f);
    }

    private function writeHousekeepingCsv(): void
    {
        $f = fopen('php://output', 'w');
        fprintf($f, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($f, ['Task Type', 'Total', 'Pending', 'In Progress', 'Completed', 'Avg Completion (mins)']);
        foreach ($this->housekeepingReport['by_type'] ?? [] as $r) {
            fputcsv($f, [
                ucfirst($r['task_type']), $r['total'],
                $r['pending'], $r['in_progress'], $r['completed'], $r['avg_mins'],
            ]);
        }
        fclose($f);
    }

    public function render()
    {
        return view('hotel::livewire.reports.reports-dashboard')->layout('layouts.app');
    }
}
