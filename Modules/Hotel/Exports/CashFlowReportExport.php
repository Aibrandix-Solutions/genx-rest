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
 
class CashFlowReportExport implements FromArray, WithEvents, WithTitle, ShouldAutoSize
{
    private const LAST_COL = 'F';
    private array $rows = [];
    
    private int $titleRow = 1;
    private int $summarySectionRow = 0;
    private int $summaryStartRow = 0;
    private int $summaryEndRow = 0;
    private int $inflowSectionRow = 0;
    private int $inflowHeaderRow = 0;
    private int $inflowEndRow = 0;
    private int $outflowSectionRow = 0;
    private int $outflowHeaderRow = 0;
    private int $outflowEndRow = 0;
 
    public function __construct(
        protected array $summary,
        protected Collection $detailedInflow,
        protected Collection $detailedOutflow,
        protected string $startDate,
        protected string $endDate,
        protected int $currencyId,
        protected string $propertyName = '',
    ) {
        $this->buildRows();
    }
 
    public function title(): string
    {
        return 'Cash Flow Report';
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
                    'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '047857']], // Green for cash flow
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension($this->titleRow)->setRowHeight(30);
 
                // Section Headers
                foreach ([$this->summarySectionRow, $this->inflowSectionRow, $this->outflowSectionRow] as $row) {
                    if ($row > 0) {
                        $sheet->mergeCells("A{$row}:{$lc}{$row}");
                        $sheet->getStyle("A{$row}")->applyFromArray([
                            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '10B981']],
                        ]);
                    }
                }
 
                // Summary block styling (Columns A-B)
                if ($this->summaryStartRow > 0 && $this->summaryEndRow > 0) {
                    $sheet->getStyle("A{$this->summaryStartRow}:B{$this->summaryEndRow}")->applyFromArray($borderAll);
                    $sheet->getStyle("A{$this->summaryStartRow}:A{$this->summaryEndRow}")->getFont()->setBold(true);
                    $sheet->getStyle("B{$this->summaryStartRow}:B{$this->summaryEndRow}")
                        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    // Bold total rows
                    foreach ([$this->summaryStartRow, $this->summaryStartRow + 3, $this->summaryEndRow] as $r) {
                        if ($r <= $this->summaryEndRow) {
                            $sheet->getStyle("A{$r}:B{$r}")->getFont()->setBold(true);
                            $sheet->getStyle("A{$r}:B{$r}")->applyFromArray([
                                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F9FAF8']],
                            ]);
                        }
                    }
                }
 
                // Inflow details table styling (Columns A-F)
                if ($this->inflowHeaderRow > 0 && $this->inflowEndRow >= $this->inflowHeaderRow) {
                    $sheet->getStyle("A{$this->inflowHeaderRow}:{$lc}{$this->inflowEndRow}")->applyFromArray($borderAll);
                    $sheet->getStyle("A{$this->inflowHeaderRow}:{$lc}{$this->inflowHeaderRow}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '374151']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E5E7EB']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    ]);
                    $sheet->getStyle("E{$this->inflowHeaderRow}:E{$this->inflowEndRow}")
                        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $sheet->getStyle("F{$this->inflowHeaderRow}:F{$this->inflowEndRow}")
                        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $sheet->getStyle("A{$this->inflowEndRow}:{$lc}{$this->inflowEndRow}")->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']],
                    ]);
                    $sheet->getStyle("B{$this->inflowHeaderRow}:B{$this->inflowEndRow}")->getAlignment()->setWrapText(true);
                    $sheet->getStyle("D{$this->inflowHeaderRow}:D{$this->inflowEndRow}")->getAlignment()->setWrapText(true);
                }
 
                // Outflow details table styling (Columns A-F)
                if ($this->outflowHeaderRow > 0 && $this->outflowEndRow >= $this->outflowHeaderRow) {
                    $sheet->getStyle("A{$this->outflowHeaderRow}:{$lc}{$this->outflowEndRow}")->applyFromArray($borderAll);
                    $sheet->getStyle("A{$this->outflowHeaderRow}:{$lc}{$this->outflowHeaderRow}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '374151']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E5E7EB']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    ]);
                    $sheet->getStyle("D{$this->outflowHeaderRow}:D{$this->outflowEndRow}")
                        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $sheet->getStyle("A{$this->outflowEndRow}:{$lc}{$this->outflowEndRow}")->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']],
                    ]);
                    $sheet->getStyle("B{$this->outflowHeaderRow}:B{$this->outflowEndRow}")->getAlignment()->setWrapText(true);
                }
 
                // Column widths
                $sheet->getColumnDimension('A')->setWidth(18);
                $sheet->getColumnDimension('B')->setWidth(26);
                $sheet->getColumnDimension('C')->setWidth(15);
                $sheet->getColumnDimension('D')->setWidth(26);
                $sheet->getColumnDimension('E')->setWidth(18);
                $sheet->getColumnDimension('F')->setWidth(18);
            },
        ];
    }
 
    private function buildRows(): void
    {
        $period = Carbon::parse($this->startDate)->format('d M Y')
            . ' to ' . Carbon::parse($this->endDate)->format('d M Y');
 
        $this->rows[] = ['Cash Flow Report'];
        $this->rows[] = ['Period', $period];
        $this->rows[] = ['Generated', now()->timezone(timezone())->format('d M Y, h:i A')];
        if ($this->propertyName !== '') {
            $this->rows[] = ['Property', $this->propertyName];
        }
        $this->blank();
 
        // ── Cash Flow Summary ──
        $this->summarySectionRow = count($this->rows) + 1;
        $this->rows[] = ['CASH FLOW SUMMARY', '', '', '', '', ''];
        
        $this->summaryStartRow = count($this->rows) + 1;
        $this->rows[] = ['Category', 'Total Amount', '', '', '', ''];
        $this->rows[] = [
            'Cash Inflow (Payments Received)',
            $this->money($this->summary['totalInflow']),
            '', '', '', ''
        ];
        $this->rows[] = [
            'LESS: Cash Outflow (Paid Expenses)',
            $this->money(-$this->summary['totalOutflow']),
            '', '', '', ''
        ];
        $this->rows[] = [
            'Net Cash Flow',
            $this->money($this->summary['totalInflow'] - $this->summary['totalOutflow']),
            '', '', '', ''
        ];
        $this->summaryEndRow = count($this->rows);
        $this->blank();
 
        // ── Cash Inflow Details ──
        $this->inflowSectionRow = count($this->rows) + 1;
        $this->rows[] = ['DETAILED TRANSACTIONS (DEBITS & CREDITS)', '', '', '', '', ''];
 
        $this->inflowHeaderRow = count($this->rows) + 1;
        $this->rows[] = [
            'Date', 'Reservation / Guest', 'Room', 'Transaction Details', 'Debit (Dr)', 'Credit (Cr)'
        ];
 
        if ($this->detailedInflow->isEmpty()) {
            $this->rows[] = ['No transactions found for this period.', '', '', '', '', ''];
            $this->inflowEndRow = count($this->rows);
        } else {
            $sumDebit = 0;
            $sumCredit = 0;
            foreach ($this->detailedInflow as $row) {
                $sumDebit += (float)$row->debit;
                $sumCredit += (float)$row->credit;
                
                $guestName = $row->guest_name;
                $resNo = $row->reservation_number;
                $desc = $row->description . ' (' . $row->type . ')';
                
                $roomInfo = $row->room_number !== '—' ? 'Room ' . $row->room_number . "\n" . $row->room_type : '—';
 
                $this->rows[] = [
                    $row->date ? Carbon::parse($row->date)->format('Y-m-d h:i A') : '—',
                    $resNo !== '—' ? $resNo . "\n" . $guestName : '—',
                    $roomInfo,
                    $desc,
                    $row->debit > 0 ? $this->money($row->debit) : '—',
                    $row->credit > 0 ? $this->money($row->credit) : '—'
                ];
            }
            $this->rows[] = [
                'TOTAL', '', '', '',
                $this->money($sumDebit),
                $this->money($sumCredit)
            ];
            $this->rows[] = [
                'Net Receivables / Unpaid Balance', '', '', '',
                $this->money($sumDebit - $sumCredit),
                ''
            ];
            $this->inflowEndRow = count($this->rows);
        }
        $this->blank();
 
        // ── Cash Outflow Details ──
        $this->outflowSectionRow = count($this->rows) + 1;
        $this->rows[] = ['DETAILED EXPENSES PAID (CASH OUTFLOW)', '', '', '', '', ''];
 
        $this->outflowHeaderRow = count($this->rows) + 1;
        $this->rows[] = ['Date', 'Expense / Reference', 'Category', 'Amount', 'Paid By', 'Status'];
 
        if ($this->detailedOutflow->isEmpty()) {
            $this->rows[] = ['No paid expenses found for this period.', '', '', '', '', ''];
            $this->outflowEndRow = count($this->rows);
        } else {
            $sumOutflow = 0;
            foreach ($this->detailedOutflow as $expense) {
                $sumOutflow += (float)$expense->amount;
                
                $ref = ($expense->receipt_number ?: 'EXP' . str_pad($expense->id, 6, '0', STR_PAD_LEFT))
                    . "\n" . $expense->title . ($expense->description ? ' - ' . $expense->description : '');
 
                $this->rows[] = [
                    $expense->expense_date->format('Y-m-d'),
                    $ref,
                    ucwords(str_replace('_', ' ', $expense->department)),
                    $this->money((float)$expense->amount),
                    ucwords($expense->payment_method),
                    'Paid',
                ];
            }
            $this->rows[] = [
                'TOTAL', '', '',
                $this->money($sumOutflow),
                '',
                ''
            ];
            $this->outflowEndRow = count($this->rows);
        }
    }
 
    private function blank(): void
    {
        $this->rows[] = array_fill(0, 6, '');
    }
 
    private function money(float $value): string
    {
        return currency_format($value, $this->currencyId);
    }
}
