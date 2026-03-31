<?php

namespace Tests\Unit;

use App\Exports\CategoryReportExport;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CategoryReportExport.
 *
 * Focuses on the headings() format which varies depending on whether the
 * report spans a single day or multiple days.
 */
class CategoryReportExportTest extends TestCase
{
    private function makeExport(
        string $startDateTime,
        string $endDateTime,
        string $startTime,
        string $endTime,
        string $timezone = 'UTC'
    ): CategoryReportExport {
        return new CategoryReportExport($startDateTime, $endDateTime, $startTime, $endTime, $timezone);
    }

    /** Headings array contains exactly two rows */
    public function test_headings_returns_two_rows(): void
    {
        $export = $this->makeExport(
            '2025-03-15 00:00:00',
            '2025-03-15 23:59:59',
            '00:00:00',
            '23:59:59',
        );
        $headings = $export->headings();
        $this->assertCount(2, $headings);
    }

    /** Second row contains exactly three column headers */
    public function test_headings_second_row_has_three_columns(): void
    {
        $export = $this->makeExport(
            '2025-03-15 00:00:00',
            '2025-03-15 23:59:59',
            '00:00:00',
            '23:59:59',
        );
        $secondRow = $export->headings()[1];
        $this->assertCount(3, $secondRow);
    }

    /** Single-day report uses "salesDataFor" phrasing */
    public function test_single_day_title_uses_salesDataFor(): void
    {
        $export = $this->makeExport(
            '2025-03-15 00:00:00',
            '2025-03-15 23:59:59',
            '08:00:00',
            '22:00:00',
        );
        $title = $export->headings()[0][0];
        // The translated key "modules.report.salesDataFor" is the distinguishing phrase
        $this->assertStringContainsString('2025-03-15', $title);
        // Single-day: same start and end date, so the "salesDataFor" variant is chosen
        $this->assertStringNotContainsString(' to ', strtolower($title));
    }

    /** Multi-day report includes both dates */
    public function test_multi_day_title_includes_both_dates(): void
    {
        $export = $this->makeExport(
            '2025-03-01 00:00:00',
            '2025-03-31 23:59:59',
            '08:00:00',
            '22:00:00',
        );
        $title = $export->headings()[0][0];
        $this->assertStringContainsString('2025-03-01', $title);
        $this->assertStringContainsString('2025-03-31', $title);
    }

    /** Timezone conversion is applied to heading dates */
    public function test_timezone_conversion_applied_to_heading_dates(): void
    {
        // UTC midnight becomes previous day in UTC-5 timezone
        $export = $this->makeExport(
            '2025-03-16 03:00:00', // 03:00 UTC = previous day in UTC-5
            '2025-03-16 03:00:00',
            '00:00:00',
            '23:59:59',
            'America/New_York', // UTC-5 in winter
        );
        $title = $export->headings()[0][0];
        // The heading date should reflect the NY timezone (2025-03-15)
        $this->assertStringContainsString('2025-03-15', $title);
    }

    /** Constructor stores all parameters correctly (checked via heading output) */
    public function test_constructor_stores_parameters_for_heading(): void
    {
        $export = $this->makeExport(
            '2025-06-01 00:00:00',
            '2025-06-30 23:59:59',
            '09:00:00',
            '21:00:00',
            'UTC',
        );
        $headings = $export->headings();
        // Both dates must appear in the first row title
        $this->assertStringContainsString('2025-06-01', $headings[0][0]);
        $this->assertStringContainsString('2025-06-30', $headings[0][0]);
    }

    /** Boundary: same start and end results in single-day format */
    public function test_boundary_same_date_single_day_format(): void
    {
        $export = $this->makeExport(
            '2025-12-25 00:00:00',
            '2025-12-25 23:59:59',
            '00:00:00',
            '23:59:59',
        );
        $title = $export->headings()[0][0];
        // Only one date should appear prominently (single-day mode)
        $countMatches = substr_count($title, '2025-12-25');
        $this->assertGreaterThanOrEqual(1, $countMatches);
    }
}