<?php

namespace Modules\Hotel\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class IncomeExpenseReportExport implements FromArray, WithEvents, WithTitle, ShouldAutoSize
{
    private const LAST_COL = 'L'; // Extended to L for reservation details
    private array $rows = [];
    
    private int $titleRow = 1;
    private int $summarySectionRow = 0;
    private int $summaryStartRow = 0;
    private int $summaryEndRow = 0;
    private int $incomeSectionRow = 0;
    private int $incomeHeaderRow = 0;
    private int $incomeEndRow = 0;
    private int $expenseSectionRow = 0;
    private int $expenseHeaderRow = 0;
    private int $expenseEndRow = 0;

    public function __construct(
        protected array $summary,
        protected Collection $detailedIncome,
        protected Collection $detailedExpenses,
        protected string $startDate,
        protected string $endDate,
        protected int $currencyId,
        protected string $propertyName = '',
    ) {
        $this->buildRows();
    }

    public function title(): string
    {
        return 'Income & Expense Report';
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lc = self::LAST_COL;
                $thin = ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']];
                $borderAll = ['borders' => ['allBorders' => $thin]];

                // Title
                $sheet->mergeCells("A{$this->titleRow}:{$lc}{$this->titleRow}");
                $sheet->getStyle("A{$this->titleRow}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4338CA']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension($this->titleRow)->setRowHeight(30);

                // Section Headers
                foreach ([$this->summarySectionRow, $this->incomeSectionRow, $this->expenseSectionRow] as $row) {
                    if ($row > 0) {
                        $sheet->mergeCells("A{$row}:{$lc}{$row}");
                        $sheet->getStyle("A{$row}")->applyFromArray([
                            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '6366F1']],
                        ]);
                    }
                }

                // Summary block styling
                if ($this->summaryStartRow > 0 && $this->summaryEndRow > 0) {
                    $sheet->getStyle("A{$this->summaryStartRow}:D{$this->summaryEndRow}")->applyFromArray($borderAll);
                    $sheet->getStyle("A{$this->summaryStartRow}:A{$this->summaryEndRow}")->getFont()->setBold(true);
                    $sheet->getStyle("B{$this->summaryStartRow}:D{$this->summaryEndRow}")
                        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    // Bold total rows
                    foreach ([$this->summaryStartRow, $this->summaryStartRow + 3, $this->summaryEndRow] as $r) {
                        $sheet->getStyle("A{$r}:D{$r}")->getFont()->setBold(true);
                        $sheet->getStyle("A{$r}:D{$r}")->applyFromArray([
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']],
                        ]);
                    }
                }

                // Income details table styling
                if ($this->incomeHeaderRow > 0 && $this->incomeEndRow >= $this->incomeHeaderRow) {
                    $sheet->getStyle("A{$this->incomeHeaderRow}:L{$this->incomeEndRow}")->applyFromArray($borderAll);
                    $sheet->getStyle("A{$this->incomeHeaderRow}:L{$this->incomeHeaderRow}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '374151']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E5E7EB']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    ]);
                    $sheet->getStyle("E{$this->incomeHeaderRow}:K{$this->incomeEndRow}")
                        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $sheet->getStyle("A{$this->incomeEndRow}:L{$this->incomeEndRow}")->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']],
                    ]);
                }

                // Expense details table styling
                if ($this->expenseHeaderRow > 0 && $this->expenseEndRow >= $this->expenseHeaderRow) {
                    $sheet->getStyle("A{$this->expenseHeaderRow}:F{$this->expenseEndRow}")->applyFromArray($borderAll);
                    $sheet->getStyle("A{$this->expenseHeaderRow}:F{$this->expenseHeaderRow}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '374151']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E5E7EB']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    ]);
                    $sheet->getStyle("F{$this->expenseHeaderRow}:F{$this->expenseEndRow}")
                        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $sheet->getStyle("A{$this->expenseEndRow}:F{$this->expenseEndRow}")->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']],
                    ]);
                }

                // Column widths
                $sheet->getColumnDimension('A')->setWidth(18);
                $sheet->getColumnDimension('B')->setWidth(18);
                $sheet->getColumnDimension('C')->setWidth(18);
                $sheet->getColumnDimension('D')->setWidth(18);
                $sheet->getColumnDimension('E')->setWidth(15);
                $sheet->getColumnDimension('F')->setWidth(15);
                $sheet->getColumnDimension('G')->setWidth(15);
                $sheet->getColumnDimension('H')->setWidth(15);
                $sheet->getColumnDimension('I')->setWidth(15);
                $sheet->getColumnDimension('J')->setWidth(15);
                $sheet->getColumnDimension('K')->setWidth(15);
                $sheet->getColumnDimension('L')->setWidth(15);
            },
        ];
    }

    private function buildRows(): void
    {
        $period = Carbon::parse($this->startDate)->format('d M Y')
            . ' to ' . Carbon::parse($this->endDate)->format('d M Y');

        $this->rows[] = ['Income & Expense Report'];
        $this->rows[] = ['Period', $period];
        $this->rows[] = ['Generated', now()->timezone(timezone())->format('d M Y, h:i A')];
        if ($this->propertyName !== '') {
            $this->rows[] = ['Property', $this->propertyName];
        }
        $this->blank();

        // ── P&L Summary Block ──
        $this->summarySectionRow = count($this->rows) + 1;
        $this->rows[] = ['FINANCIAL SUMMARY (P&L)', '', '', '', '', '', '', '', '', '', '', ''];
        
        $this->summaryStartRow = count($this->rows) + 1;
        $this->rows[] = ['Category', 'Total amount', 'Paid', 'Unpaid'];
        $this->rows[] = [
            'Total Revenue (Sales)',
            $this->money($this->summary['totalRevenue']),
            $this->money($this->summary['totalCollected']),
            $this->money($this->summary['hotelOutstanding']),
        ];
        $this->rows[] = [
            'LESS: Expenses',
            $this->money($this->summary['hotelExpenses']),
            $this->money($this->summary['hotelExpensesPaid']),
            $this->money($this->summary['hotelExpensesUnpaid']),
        ];
        $this->rows[] = [
            'Net Profit / (Loss)',
            $this->money($this->summary['totalRevenue'] - $this->summary['hotelExpenses']),
            $this->money($this->summary['totalCollected'] - $this->summary['hotelExpensesPaid']),
            $this->money($this->summary['hotelOutstanding'] - $this->summary['hotelExpensesUnpaid']),
        ];
        $this->summaryEndRow = count($this->rows);
        $this->blank();

        // ── Income Details Table ──
        $this->incomeSectionRow = count($this->rows) + 1;
        $this->rows[] = ['INCOME DETAILS (RESERVATIONS)', '', '', '', '', '', '', '', '', '', '', ''];

        $this->incomeHeaderRow = count($this->rows) + 1;
        $this->rows[] = [
            'Date', 'Reservation #', 'Guest', 'Room',
            'Room Charge', 'Laundry', 'Minibar', 'Other Services',
            'Total Charges', 'Paid Amount', 'Unpaid Amount', 'Status'
        ];

        if ($this->detailedIncome->isEmpty()) {
            $this->rows[] = ['No reservation income found for this period.', '', '', '', '', '', '', '', '', '', '', ''];
            $this->incomeEndRow = count($this->rows);
        } else {
            $sumRoom = 0; $sumLaundry = 0; $sumMinibar = 0; $sumOther = 0;
            $sumTotal = 0; $sumPaid = 0; $sumUnpaid = 0;

            foreach ($this->detailedIncome as $res) {
                $roomNightAmt = (float)$res->charges->where('charge_type', \Modules\Hotel\Entities\RoomCharge::TYPE_ROOM_NIGHT)->sum('amount');
                $laundryAmt = (float)$res->charges->where('charge_type', \Modules\Hotel\Entities\RoomCharge::TYPE_LAUNDRY)->sum('amount');
                $minibarAmt = (float)$res->charges->where('charge_type', \Modules\Hotel\Entities\RoomCharge::TYPE_MINIBAR)->sum('amount');
                $otherAmt = (float)$res->charges->whereNotIn('charge_type', [
                    \Modules\Hotel\Entities\RoomCharge::TYPE_ROOM_NIGHT,
                    \Modules\Hotel\Entities\RoomCharge::TYPE_LAUNDRY,
                    \Modules\Hotel\Entities\RoomCharge::TYPE_MINIBAR
                ])->sum('amount');

                $totalCharges = $roomNightAmt + $laundryAmt + $minibarAmt + $otherAmt;
                $paid = (float)$res->paid_amount;
                $unpaid = (float)$res->balance_due;

                $sumRoom += $roomNightAmt;
                $sumLaundry += $laundryAmt;
                $sumMinibar += $minibarAmt;
                $sumOther += $otherAmt;
                $sumTotal += $totalCharges;
                $sumPaid += $paid;
                $sumUnpaid += $unpaid;

                $status = 'Unpaid';
                if ($unpaid <= 0) {
                    $status = 'Paid';
                } elseif ($paid > 0) {
                    $status = 'Partially Paid';
                }

                $this->rows[] = [
                    $res->check_in_date ? $res->check_in_date->format('Y-m-d') : '—',
                    $res->reservation_number,
                    $res->guest?->name ?? '—',
                    $res->room?->name ?? '—',
                    $this->moneyOrDash($roomNightAmt),
                    $this->moneyOrDash($laundryAmt),
                    $this->moneyOrDash($minibarAmt),
                    $this->moneyOrDash($otherAmt),
                    $this->money($totalCharges),
                    $this->money($paid),
                    $this->money($unpaid),
                    $status
                ];
            }

            $this->rows[] = [
                'TOTAL', '', '', '',
                $this->money($sumRoom),
                $this->money($sumLaundry),
                $this->money($sumMinibar),
                $this->money($sumOther),
                $this->money($sumTotal),
                $this->money($sumPaid),
                $this->money($sumUnpaid),
                ''
            ];
            $this->incomeEndRow = count($this->rows);
        }
        $this->blank();

        // ── Expense Details Table ──
        $this->expenseSectionRow = count($this->rows) + 1;
        $this->rows[] = ['EXPENSE DETAILS', '', '', '', '', '', '', '', '', '', '', ''];

        $this->expenseHeaderRow = count($this->rows) + 1;
        $this->rows[] = ['Date', 'Description', 'Department', 'Vendor', 'Status', 'Amount'];

        if ($this->detailedExpenses->isEmpty()) {
            $this->rows[] = ['No expenses found for this period.', '', '', '', '', ''];
            $this->expenseEndRow = count($this->rows);
        } else {
            $sumExpenses = 0;
            foreach ($this->detailedExpenses as $expense) {
                $sumExpenses += (float)$expense->amount;
                $this->rows[] = [
                    $expense->expense_date->format('Y-m-d'),
                    $expense->title . ($expense->description ? ' - ' . $expense->description : ''),
                    ucwords(str_replace('_', ' ', $expense->department)),
                    $expense->vendor ?? '—',
                    ucfirst($expense->status),
                    $this->money((float)$expense->amount),
                ];
            }
            $this->rows[] = [
                'TOTAL', '', '', '', '',
                $this->money($sumExpenses)
            ];
            $this->expenseEndRow = count($this->rows);
        }
    }

    private function blank(): void
    {
        $this->rows[] = array_fill(0, 12, '');
    }

    private function money(float $value): string
    {
        return currency_format($value, $this->currencyId);
    }

    private function moneyOrDash(float $value): string
    {
        return abs($value) < 0.005 ? '—' : $this->money($value);
    }
}
