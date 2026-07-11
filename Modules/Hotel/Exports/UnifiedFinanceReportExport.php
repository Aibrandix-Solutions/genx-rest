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

class UnifiedFinanceReportExport implements FromArray, WithEvents, WithTitle, ShouldAutoSize
{
    private const LAST_COL = 'G';

    /** @var array<int, array<int, string>> */
    private array $rows = [];

    private int $titleRow = 1;

    private int $revenueSectionRow = 0;

    private int $revenueCardsStartRow = 0;

    private int $revenueBannerRow = 0;

    private int $middleCardsTitleRow = 0;

    private int $middleCardsStartRow = 0;

    private int $middleCardsEndRow = 0;

    private int $dailySectionRow = 0;

    private int $dailyHeaderRow = 0;

    private int $dailyEndRow = 0;

    public function __construct(
        protected array $summary,
        protected Collection $dailyBreakdown,
        protected string $startDate,
        protected string $endDate,
        protected int $currencyId,
        protected string $propertyName = '',
    ) {
        $this->buildRows();
    }

    public function title(): string
    {
        return 'Finance Report';
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

                // Meta rows
                $metaEnd = $this->propertyName !== '' ? 4 : 3;
                $sheet->getStyle("A2:A{$metaEnd}")->getFont()->setBold(true);
                $sheet->mergeCells("B2:{$lc}2");
                $sheet->mergeCells("B3:{$lc}3");
                if ($this->propertyName !== '') {
                    $sheet->mergeCells("B4:{$lc}4");
                }

                // Section headers
                foreach ([$this->revenueSectionRow, $this->middleCardsTitleRow, $this->dailySectionRow] as $row) {
                    if ($row > 0) {
                        $sheet->mergeCells("A{$row}:{$lc}{$row}");
                        $sheet->getStyle("A{$row}")->applyFromArray([
                            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '6366F1']],
                        ]);
                    }
                }

                // Revenue cards (4 columns A-D)
                if ($this->revenueCardsStartRow > 0) {
                    $r1 = $this->revenueCardsStartRow;
                    $r3 = $r1 + 2;
                    foreach (['A' => 'DBEAFE', 'B' => 'F3E8FF', 'C' => 'DCFCE7', 'D' => 'FFEDD5'] as $col => $color) {
                        $sheet->getStyle("{$col}{$r1}:{$col}{$r3}")->applyFromArray([
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $color]],
                            'borders' => ['allBorders' => $thin],
                            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                        ]);
                        $sheet->getStyle("{$col}" . ($r1 + 1))->getFont()->setBold(true)->setSize(12);
                        $sheet->getStyle("{$col}{$r1}")->applyFromArray(['font' => ['size' => 9, 'color' => ['rgb' => '4B5563']]]);
                        $sheet->getStyle("{$col}{$r3}")->applyFromArray(['font' => ['size' => 8, 'italic' => true, 'color' => ['rgb' => '6B7280']]]);
                    }
                }

                // Total revenue banner
                if ($this->revenueBannerRow > 0) {
                    $sheet->mergeCells("A{$this->revenueBannerRow}:{$lc}{$this->revenueBannerRow}");
                    $sheet->getStyle("A{$this->revenueBannerRow}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => 'FFFFFF']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    ]);
                    $sheet->getRowDimension($this->revenueBannerRow)->setRowHeight(24);
                }

                // Middle cards (Payments | Expenses | Outstanding)
                if ($this->middleCardsStartRow > 0 && $this->middleCardsEndRow > 0) {
                    $ranges = [
                        "A{$this->middleCardsStartRow}:B{$this->middleCardsEndRow}" => 'F9FAFB',
                        "C{$this->middleCardsStartRow}:D{$this->middleCardsEndRow}" => 'F9FAFB',
                        "E{$this->middleCardsStartRow}:{$lc}{$this->middleCardsEndRow}" => 'FFFBEB',
                    ];
                    foreach ($ranges as $range => $bg) {
                        $sheet->getStyle($range)->applyFromArray([
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg]],
                            'borders' => ['allBorders' => $thin],
                            'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
                        ]);
                    }
                    $sheet->getStyle("A{$this->middleCardsStartRow}:{$lc}{$this->middleCardsStartRow}")
                        ->getFont()->setBold(true);
                }

                // Daily breakdown table
                if ($this->dailyHeaderRow > 0 && $this->dailyEndRow >= $this->dailyHeaderRow) {
                    $sheet->getStyle("A{$this->dailyHeaderRow}:{$lc}{$this->dailyEndRow}")->applyFromArray($borderAll);
                    $sheet->getStyle("A{$this->dailyHeaderRow}:{$lc}{$this->dailyHeaderRow}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '374151']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E5E7EB']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    ]);
                    $sheet->getStyle("B{$this->dailyHeaderRow}:{$lc}{$this->dailyEndRow}")
                        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $sheet->getStyle("A{$this->dailyHeaderRow}")
                        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    $sheet->getStyle("A{$this->dailyEndRow}:{$lc}{$this->dailyEndRow}")->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']],
                    ]);
                    $sheet->freezePane('A' . ($this->dailyHeaderRow + 1));
                }

                $sheet->getColumnDimension('A')->setWidth(22);
                $sheet->getColumnDimension('B')->setWidth(18);
                $sheet->getColumnDimension('C')->setWidth(18);
                $sheet->getColumnDimension('D')->setWidth(18);
                $sheet->getColumnDimension('E')->setWidth(16);
                $sheet->getColumnDimension('F')->setWidth(14);
                $sheet->getColumnDimension('G')->setWidth(14);
            },
        ];
    }

    private function buildRows(): void
    {
        $period = Carbon::parse($this->startDate)->format('d M Y')
            . ' to ' . Carbon::parse($this->endDate)->format('d M Y');

        $this->rows[] = ['Unified Finance Report'];
        $this->rows[] = ['Period', $period];
        $this->rows[] = ['Generated', now()->timezone(timezone())->format('d M Y, h:i A')];
        if ($this->propertyName !== '') {
            $this->rows[] = ['Property', $this->propertyName];
        }
        $this->blank();

        // ── Revenue cards ──
        $this->revenueSectionRow = count($this->rows) + 1;
        $this->rows[] = ['REVENUE BREAKDOWN', '', '', '', '', '', ''];

        $this->revenueCardsStartRow = count($this->rows) + 1;
        $this->rows[] = [
            'Restaurant Sales',
            'Room Service',
            'Room Revenue',
            'Hotel Add-ons',
            '', '', '',
        ];
        $this->rows[] = [
            $this->money($this->summary['restaurantSales']),
            $this->money($this->summary['roomServiceSales']),
            $this->money($this->summary['roomNightRevenue']),
            $this->money($this->summary['hotelAddOns']),
            '', '', '',
        ];
        $this->rows[] = [
            'Dine-in, delivery & pickup',
            'In-room F&B orders',
            'Nightly room charges',
            'Minibar, laundry, service',
            '', '', '',
        ];

        $this->revenueBannerRow = count($this->rows) + 1;
        $this->rows[] = [
            'TOTAL PROPERTY REVENUE: ' . $this->money($this->summary['totalRevenue']),
            '', '', '', '', '', '',
        ];
        $this->blank();

        // ── Middle cards row ──
        $this->middleCardsTitleRow = count($this->rows) + 1;
        $this->rows[] = ['PAYMENTS & EXPENSES SUMMARY', '', '', '', '', '', ''];

        $this->middleCardsStartRow = count($this->rows) + 1;
        $this->rows[] = ['Payments Collected', '', 'Hotel Expenses', '', 'Hotel Outstanding', '', ''];

        $payLines = [
            'Hotel Payments: ' . $this->money($this->summary['hotelPaymentsReceived']),
            'Restaurant Payments: ' . $this->money($this->summary['restaurantPaymentsReceived']),
        ];
        if ((float) ($this->summary['hotelRefunds'] ?? 0) > 0) {
            $payLines[] = 'Hotel Refunds: -' . $this->money($this->summary['hotelRefunds']);
        }
        $payLines[] = 'Total Collected: ' . $this->money($this->summary['totalCollected']);

        $expLines = $this->summary['hotelExpensesByDept']->isNotEmpty()
            ? $this->summary['hotelExpensesByDept']
                ->map(fn ($amt, $dept) => ucwords(str_replace('_', ' ', (string) $dept)) . ': ' . $this->money((float) $amt))
                ->values()->all()
            : ['No expenses recorded for this period.'];
        $expLines[] = 'Total Expenses: ' . $this->money($this->summary['hotelExpenses']);

        $outLines = [
            $this->money($this->summary['hotelOutstanding']),
            'Pending balances on active reservations',
        ];

        $maxLines = max(count($payLines), count($expLines), count($outLines));
        for ($i = 0; $i < $maxLines; $i++) {
            $this->rows[] = [
                $payLines[$i] ?? '',
                '',
                $expLines[$i] ?? '',
                '',
                $outLines[$i] ?? '',
                '', '',
            ];
        }
        $this->middleCardsEndRow = count($this->rows);
        $this->blank();

        // ── Daily breakdown table ──
        $this->dailySectionRow = count($this->rows) + 1;
        $this->rows[] = ['DAILY BREAKDOWN', '', '', '', '', '', ''];

        $this->dailyHeaderRow = count($this->rows) + 1;
        $this->rows[] = ['Date', 'Restaurant', 'Room Service', 'Hotel Charges', 'Total Revenue', 'Expenses', 'Net'];

        if ($this->dailyBreakdown->isEmpty()) {
            $this->rows[] = ['No transactions found for this period.', '', '', '', '', '', ''];
            $this->dailyEndRow = count($this->rows);
            return;
        }

        foreach ($this->dailyBreakdown as $row) {
            $this->rows[] = [
                Carbon::parse($row['day'])->format('D, M d'),
                $this->dailyCell((float) $row['restaurant']),
                $this->dailyCell((float) $row['room_service']),
                $this->dailyCell((float) $row['hotel_charges']),
                $this->money((float) $row['total_revenue']),
                $this->dailyCell((float) $row['expenses']),
                $this->money((float) $row['net']),
            ];
        }

        $this->rows[] = [
            'TOTAL',
            $this->money((float) $this->dailyBreakdown->sum('restaurant')),
            $this->money((float) $this->dailyBreakdown->sum('room_service')),
            $this->money((float) $this->dailyBreakdown->sum('hotel_charges')),
            $this->money((float) $this->dailyBreakdown->sum('total_revenue')),
            $this->money((float) $this->dailyBreakdown->sum('expenses')),
            $this->money((float) $this->dailyBreakdown->sum('net')),
        ];
        $this->dailyEndRow = count($this->rows);
    }

    private function blank(): void
    {
        $this->rows[] = ['', '', '', '', '', '', ''];
    }

    private function money(float $value): string
    {
        return currency_format($value, $this->currencyId);
    }

    private function dailyCell(float $value): string
    {
        return abs($value) < 0.005 ? '—' : $this->money($value);
    }
}
