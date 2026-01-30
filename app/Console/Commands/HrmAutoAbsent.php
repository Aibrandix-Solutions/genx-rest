<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class HrmAutoAbsent extends Command
{
    protected $signature = 'hrm:auto-absent {date? : Date (Y-m-d). Defaults to yesterday.}
                            {--restaurant_id= : Limit to a restaurant id}
                            {--branch_id= : Limit to a branch id}';

    protected $description = 'Mark absent for active employees who have no attendance entry for a given date.';

    public function handle(): int
    {
        $dateInput = $this->argument('date');
        $date = $dateInput
            ? Carbon::parse($dateInput)->toDateString()
            : now()->subDay()->toDateString();

        $restaurantId = $this->option('restaurant_id');
        $branchId = $this->option('branch_id');

        $now = now();

        $missing = DB::table('hrm_employees as e')
            ->select('e.id as employee_id', 'e.restaurant_id', 'e.branch_id')
            ->leftJoin('hrm_attendance_logs as a', function ($join) use ($date) {
                $join->on('a.employee_id', '=', 'e.id')
                    ->where('a.date', '=', $date);
            })
            ->whereNull('a.id')
            ->where('e.status', '=', 'active')
            ->where(function ($q) use ($date) {
                $q->whereNull('e.hire_date')->orWhere('e.hire_date', '<=', $date);
            })
            ->when($restaurantId, fn($q) => $q->where('e.restaurant_id', (int) $restaurantId))
            ->when($branchId, fn($q) => $q->where('e.branch_id', (int) $branchId))
            ->get();

        if ($missing->isEmpty()) {
            $this->info("No missing attendance found for {$date}.");
            return self::SUCCESS;
        }

        $rows = $missing->map(fn($r) => [
            'restaurant_id' => (int) $r->restaurant_id,
            'branch_id' => (int) $r->branch_id,
            'employee_id' => (int) $r->employee_id,
            'shift_id' => null,
            'date' => $date,
            'clock_in_at' => null,
            'clock_out_at' => null,
            'status' => 'absent',
            'late_minutes' => 0,
            'note' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        $inserted = 0;
        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('hrm_attendance_logs')->insert($chunk);
            $inserted += count($chunk);
        }

        $this->info("Marked {$inserted} employees as absent for {$date}.");
        return self::SUCCESS;
    }
}
