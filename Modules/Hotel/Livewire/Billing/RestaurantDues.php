<?php

namespace Modules\Hotel\Livewire\Billing;

use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Hotel\Exports\RestaurantDuesExport;
use Modules\Hotel\Services\RestaurantDuesSettlementService;

class RestaurantDues extends Component
{
    public string $activeTab = 'report';

    public ?int $restaurantBranchId = null;
    public string $roomNumber = '';
    public string $guest = '';
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $settlementStatus = '';

    public array $summary = [
        'total_charges' => 0,
        'total_paid' => 0,
        'outstanding_amount' => 0,
    ];

    public array $reportRows = [];
    public array $ledgerRows = [];
    public array $ledgerSummary = [
        'total_debit' => 0,
        'total_credit' => 0,
        'outstanding_balance' => 0,
    ];

    public bool $showDetailsDrawer = false;
    public array $details = [
        'reservation' => null,
        'orders' => [],
        'totals' => ['charges' => 0, 'paid' => 0, 'outstanding' => 0],
    ];

    public bool $showPaymentModal = false;
    public bool $paymentSubmitInProgress = false;
    public ?int $paymentReservationId = null;
    public ?int $paymentRestaurantBranchId = null;
    public string $paymentRoom = '--';
    public string $paymentGuest = '--';
    public float $paymentOutstandingAmount = 0;
    public string $paymentDate = '';
    public float $paymentAmount = 0;
    public string $paymentMethod = 'cash';
    public string $paymentReference = '';
    public string $paymentRemarks = '';

    public function mount(): void
    {
        abort_unless(in_array('Hotel', restaurant_modules()), 403);
        abort_unless(user_can('view_hotel_billing'), 403);

        $this->dateFrom = now()->subDays(30)->toDateString();
        $this->dateTo = now()->toDateString();
        $this->paymentDate = now()->toDateString();

        $this->refreshData();
    }

    public function updatedRestaurantBranchId(): void { $this->refreshData(); }
    public function updatedRoomNumber(): void { $this->refreshData(); }
    public function updatedGuest(): void { $this->refreshData(); }
    public function updatedDateFrom(): void { $this->refreshData(); }
    public function updatedDateTo(): void { $this->refreshData(); }
    public function updatedSettlementStatus(): void { $this->refreshData(); }
    public function updatedActiveTab(): void { $this->refreshData(); }

    public function openDetails(int $reservationId, int $restaurantBranchId): void
    {
        $service = app(RestaurantDuesSettlementService::class);
        $this->details = $service->detailsForReservation($reservationId, $restaurantBranchId);
        $this->showDetailsDrawer = true;
    }

    public function closeDetails(): void
    {
        $this->showDetailsDrawer = false;
    }

    public function openPaymentModal(int $reservationId, int $restaurantBranchId): void
    {
        abort_unless(user_can('process_hotel_payment'), 403);

        $service = app(RestaurantDuesSettlementService::class);
        $details = $service->detailsForReservation($reservationId, $restaurantBranchId);

        $this->paymentReservationId = $reservationId;
        $this->paymentRestaurantBranchId = $restaurantBranchId;
        $this->paymentRoom = $details['reservation']?->room?->room_number ?? '--';
        $this->paymentGuest = $details['reservation']?->guest?->full_name ?? '--';
        $this->paymentOutstandingAmount = (float) ($details['totals']['outstanding'] ?? 0);
        $this->paymentDate = now()->toDateString();
        $this->paymentAmount = $this->paymentOutstandingAmount;
        $this->paymentMethod = 'cash';
        $this->paymentReference = '';
        $this->paymentRemarks = '';
        $this->showPaymentModal = true;
    }

    public function openAddPaymentModal(): void
    {
        abort_unless(user_can('process_hotel_payment'), 403);

        $target = collect($this->reportRows)
            ->first(fn (array $row) => (float) $row['outstanding_amount'] > 0.0001);

        if (! $target) {
            $this->addError('paymentAmount', __('hotel::modules.restaurantDues.noOutstandingRows'));

            return;
        }

        $this->openPaymentModal((int) $target['reservation_id'], (int) $target['restaurant_branch_id']);
    }

    public function closePaymentModal(): void
    {
        $this->showPaymentModal = false;
        $this->resetErrorBag();
    }

    public function savePayment(): void
    {
        abort_unless(user_can('process_hotel_payment'), 403);

        if ($this->paymentSubmitInProgress) {
            return;
        }

        $this->paymentSubmitInProgress = true;

        try {
            $this->validate([
                'paymentRestaurantBranchId' => 'required|integer|min:1',
                'paymentDate' => 'required|date',
                'paymentAmount' => 'required|numeric|min:0.01',
                'paymentMethod' => 'required|string|max:50',
                'paymentReference' => 'nullable|string|max:255',
                'paymentRemarks' => 'nullable|string|max:1000',
            ]);

            if ($this->paymentReservationId === null) {
                $this->addError('paymentAmount', __('hotel::modules.restaurantDues.invalidReservation'));
                return;
            }

            if ((float) $this->paymentAmount > (float) $this->paymentOutstandingAmount) {
                $this->addError('paymentAmount', __('hotel::modules.restaurantDues.paymentExceedsOutstanding'));
                return;
            }

            app(RestaurantDuesSettlementService::class)->recordSettlementPayment([
                'hotel_branch_id' => branch()->id,
                'restaurant_branch_id' => $this->paymentRestaurantBranchId,
                'reservation_id' => $this->paymentReservationId,
                'payment_date' => $this->paymentDate,
                'amount' => (float) $this->paymentAmount,
                'payment_method' => $this->paymentMethod,
                'reference_number' => $this->paymentReference ?: null,
                'remarks' => $this->paymentRemarks ?: null,
            ]);

            $this->showPaymentModal = false;
            $this->refreshData();
            $this->dispatch('refreshOrders');
            $this->dispatch('refreshPayments');

            session()->flash('success', __('hotel::modules.restaurantDues.paymentRecorded'));
        } catch (\Throwable $e) {
            report($e);
            $this->addError('paymentAmount', $e->getMessage());
        } finally {
            $this->paymentSubmitInProgress = false;
        }
    }

    public function exportExcel()
    {
        $filename = 'restaurant-dues-' . $this->activeTab . '-' . now()->format('Ymd_His') . '.xlsx';
        $rows = $this->activeTab === 'ledger' ? $this->ledgerRows : $this->reportRows;

        return Excel::download(
            new RestaurantDuesExport($this->activeTab === 'ledger' ? 'ledger' : 'report', $rows, (int) restaurant()->currency_id),
            $filename
        );
    }

    public function exportPdf()
    {
        $rows = $this->activeTab === 'ledger' ? $this->ledgerRows : $this->reportRows;
        $pdf = Pdf::loadView('hotel::reports.restaurant-dues-export', [
            'activeTab' => $this->activeTab,
            'rows' => $rows,
            'currencyId' => (int) restaurant()->currency_id,
        ]);

        $pdf->setPaper('A4', 'landscape');

        return response()->streamDownload(
            fn () => print($pdf->output()),
            'restaurant-dues-' . $this->activeTab . '-' . now()->format('Ymd_His') . '.pdf'
        );
    }

    private function refreshData(): void
    {
        $service = app(RestaurantDuesSettlementService::class);
        $filters = $this->filters();

        $rows = $service->roomGroupedRows($filters);

        if ($this->settlementStatus !== '') {
            $rows = $rows->filter(fn (array $row) => $row['status'] === $this->settlementStatus)->values();
        }

        $this->reportRows = $rows->all();
        $this->summary = [
            'total_charges' => round((float) $rows->sum('total_charges'), 2),
            'total_paid' => round((float) $rows->sum('total_paid'), 2),
            'outstanding_amount' => round((float) $rows->sum('outstanding_amount'), 2),
        ];

        $ledger = $service->ledgerRows($filters);
        $this->ledgerRows = $ledger['rows']->all();
        $this->ledgerSummary = $ledger['summary'];
    }

    private function filters(): array
    {
        return [
            'restaurant_branch_id' => $this->restaurantBranchId,
            'room_number' => trim($this->roomNumber),
            'guest' => trim($this->guest),
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
        ];
    }

    public function render()
    {
        $branches = \App\Models\Branch::query()
            ->where('restaurant_id', restaurant()->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('hotel::livewire.billing.restaurant-dues', [
            'branches' => $branches,
        ])->layout('layouts.app');
    }
}
