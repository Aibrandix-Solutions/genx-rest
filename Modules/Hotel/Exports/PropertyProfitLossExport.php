<?php

namespace Modules\Hotel\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class PropertyProfitLossExport implements FromArray, WithEvents, WithTitle, ShouldAutoSize
{
    private const LAST_COL = 'E';

    /** @var array<int, array<int, string>> */
    private array $rows = [];

    private int $titleRow = 1;

    private int $summaryCardsStartRow = 0;

    private int $breakdownTitleRow = 0;

    private int $breakdownStartRow = 0;

    private int $breakdownEndRow = 0;

    private int $trendSectionRow = 0;

    private int $trendHeaderRow = 0;

    private int $trendEndRow = 0;

    public function __construct(
        protected array $data,
        protected string $startDate,
        protected string $endDate,
        protected int $currencyId,
        protected string $propertyName = '',
    ) {
        $this->buildRows();
    }

    public function title(): string
    {
        return 'Property P&L';
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

                $sheet->mergeCells("A{$this->titleRow}:{$lc}{$this->titleRow}");
                $sheet->getStyle("A{$this->titleRow}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4338CA']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension($this->titleRow)->setRowHeight(30);

                $metaEnd = $this->propertyName !== '' ? 4 : 3;
                $sheet->getStyle("A2:A{$metaEnd}")->getFont()->setBold(true);
                $sheet->mergeCells("B2:{$lc}2");
                $sheet->mergeCells("B3:{$lc}3");
                if ($this->propertyName !== '') {
                    $sheet->mergeCells("B4:{$lc}4");
                }

                if ($this->summaryCardsStartRow > 0) {
                    $r1 = $this->summaryCardsStartRow;
                    $r3 = $r1 + 2;
                    $netProfit = (float) ($this->data['netProfit'] ?? 0);
                    $cardStyles = [
                        'A' => $netProfit >= 0 ? 'DCFCE7' : 'FEE2E2',
                        'B' => 'DCFCE7',
                        'C' => 'FEE2E2',
                    ];
                    foreach ($cardStyles as $col => $color) {
                        $sheet->getStyle("{$col}{$r1}:{$col}{$r3}")->applyFromArray([
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $color]],
                            'borders' => ['allBorders' => $thin],
                            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                        ]);
                        $sheet->getStyle("{$col}" . ($r1 + 1))->getFont()->setBold(true)->setSize(14);
                        $sheet->getStyle("{$col}{$r1}")->applyFromArray(['font' => ['size' => 9, 'color' => ['rgb' => '4B5563']]]);
                        $sheet->getStyle("{$col}{$r3}")->applyFromArray(['font' => ['size' => 8, 'italic' => true, 'color' => ['rgb' => '6B7280']]]);
                    }
                }

                if ($this->breakdownTitleRow > 0) {
                    $sheet->mergeCells("A{$this->breakdownTitleRow}:{$lc}{$this->breakdownTitleRow}");
                    $sheet->getStyle("A{$this->breakdownTitleRow}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '6366F1']],
                    ]);
                }

                if ($this->breakdownStartRow > 0 && $this->breakdownEndRow > 0) {
                    $sheet->getStyle("A{$this->breakdownStartRow}:B{$this->breakdownEndRow}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0FDF4']],
                        'borders' => ['allBorders' => $thin],
                        'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
                    ]);
                    $sheet->getStyle("C{$this->breakdownStartRow}:{$lc}{$this->breakdownEndRow}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEF2F2']],
                        'borders' => ['allBorders' => $thin],
                        'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
                    ]);
                    $sheet->getStyle("A{$this->breakdownStartRow}:{$lc}{$this->breakdownStartRow}")
                        ->getFont()->setBold(true);
                }

                if ($this->trendSectionRow > 0) {
                    $sheet->mergeCells("A{$this->trendSectionRow}:{$lc}{$this->trendSectionRow}");
                    $sheet->getStyle("A{$this->trendSectionRow}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '6366F1']],
                    ]);
                }

                if ($this->trendHeaderRow > 0 && $this->trendEndRow >= $this->trendHeaderRow) {
                    $sheet->getStyle("A{$this->trendHeaderRow}:{$lc}{$this->trendEndRow}")->applyFromArray($borderAll);
                    $sheet->getStyle("A{$this->trendHeaderRow}:{$lc}{$this->trendHeaderRow}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '374151']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E5E7EB']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    ]);
                    $sheet->getStyle("B{$this->trendHeaderRow}:{$lc}{$this->trendEndRow}")
                        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $sheet->getStyle("A{$this->trendHeaderRow}")
                        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    $sheet->freezePane('A' . ($this->trendHeaderRow + 1));
                }

                $sheet->getColumnDimension('A')->setWidth(22);
                $sheet->getColumnDimension('B')->setWidth(18);
                $sheet->getColumnDimension('C')->setWidth(18);
                $sheet->getColumnDimension('D')->setWidth(18);
                $sheet->getColumnDimension('E')->setWidth(14);
            },
        ];
    }

    private function buildRows(): void
    {
        $period = Carbon::parse($this->startDate)->format('d M Y')
            . ' to ' . Carbon::parse($this->endDate)->format('d M Y');

        $this->rows[] = ['Property P&L Report'];
        $this->rows[] = ['Period', $period];
        $this->rows[] = ['Generated', now()->timezone(timezone())->format('d M Y, h:i A')];
        if ($this->propertyName !== '') {
            $this->rows[] = ['Property', $this->propertyName];
        }
        $this->blank();

        $netProfit = (float) ($this->data['netProfit'] ?? 0);
        $profitMargin = (float) ($this->data['profitMargin'] ?? 0);
        $profitLabel = $netProfit >= 0 ? 'Net Profit' : 'Net Loss';
        $profitSub = ($netProfit >= 0 ? '▲ Profit' : '▼ Loss') . ' · ' . $profitMargin . '% margin';

        $this->summaryCardsStartRow = count($this->rows) + 1;
        $this->rows[] = [$profitLabel, 'Total Revenue', 'Total Expenses', '', ''];
        $this->rows[] = [
            $this->money(abs($netProfit)),
            $this->money((float) $this->data['totalRevenue']),
            $this->money((float) $this->data['totalExpenses']),
            '', '',
        ];
        $this->rows[] = [
            $profitSub,
            'Hotel + Restaurant combined',
            'Hotel + Restaurant combined',
            '', '',
        ];
        $this->blank();

        $this->breakdownTitleRow = count($this->rows) + 1;
        $this->rows[] = ['REVENUE & EXPENSE BREAKDOWN', '', '', '', ''];

        $this->breakdownStartRow = count($this->rows) + 1;
        $this->rows[] = ['Revenue Breakdown', '', 'Expense Breakdown', '', ''];

        $revenueLines = [
            'Room Nights: ' . $this->money((float) $this->data['roomNightRevenue']),
            'Room Service: ' . $this->money((float) $this->data['roomServiceSales']),
            'Restaurant: ' . $this->money((float) $this->data['restaurantSales']),
            'Hotel Add-ons: ' . $this->money((float) $this->data['hotelAddOns']),
            'Total Revenue: ' . $this->money((float) $this->data['totalRevenue']),
        ];

        $expenseLines = [];
        $hotelExpByDept = $this->data['hotelExpByDept'] ?? collect();
        if ($hotelExpByDept->isEmpty() && (float) ($this->data['restaurantExpenses'] ?? 0) <= 0) {
            $expenseLines[] = 'No hotel expenses this period.';
        } else {
            foreach ($hotelExpByDept as $exp) {
                $dept = ucwords(str_replace('_', ' ', (string) $exp->department));
                $expenseLines[] = 'Hotel · ' . $dept . ': ' . $this->money((float) $exp->total);
            }
            if ((float) ($this->data['restaurantExpenses'] ?? 0) > 0) {
                $expenseLines[] = 'Restaurant Expenses: ' . $this->money((float) $this->data['restaurantExpenses']);
            }
        }
        $expenseLines[] = 'Total Expenses: ' . $this->money((float) $this->data['totalExpenses']);

        $maxLines = max(count($revenueLines), count($expenseLines));
        for ($i = 0; $i < $maxLines; $i++) {
            $this->rows[] = [
                $revenueLines[$i] ?? '',
                '',
                $expenseLines[$i] ?? '',
                '', '',
            ];
        }
        $this->breakdownEndRow = count($this->rows);
        $this->blank();

        $this->trendSectionRow = count($this->rows) + 1;
        $this->rows[] = ['6-MONTH TREND', '', '', '', ''];

        $this->trendHeaderRow = count($this->rows) + 1;
        $this->rows[] = ['Month', 'Revenue', 'Expenses', 'Net Profit', 'Margin'];

        $trend = $this->data['trend'] ?? [];
        foreach ($trend as $t) {
            $revenue = (float) ($t['revenue'] ?? 0);
            $expenses = (float) ($t['expenses'] ?? 0);
            $profit = (float) ($t['profit'] ?? 0);
            $margin = $revenue > 0 ? round(($profit / $revenue) * 100, 1) : 0;

            $this->rows[] = [
                (string) ($t['label'] ?? ''),
                $this->money($revenue),
                $this->money($expenses),
                $this->money($profit),
                $margin . '%',
            ];
        }
        $this->trendEndRow = count($this->rows);
    }

    private function blank(): void
    {
        $this->rows[] = ['', '', '', '', ''];
    }

    private function money(float $value): string
    {
        return currency_format($value, $this->currencyId);
    }
}
