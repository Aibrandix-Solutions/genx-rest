<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Makes branch_id nullable on HRM tables to support "Company Level" employees
 * (managers, accountants, warehouse staff, security) who are not tied to any
 * specific branch and receive salary from company net profit.
 *
 * branchId = NULL  → Company Level employee/record
 * branchId = X     → Branch-specific employee/record
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        // Employees: NULL branch_id = company-level staff
        DB::statement('ALTER TABLE hrm_employees MODIFY COLUMN branch_id BIGINT UNSIGNED NULL');

        // Attendance logs: company employees clock in with no branch context
        DB::statement('ALTER TABLE hrm_attendance_logs MODIFY COLUMN branch_id BIGINT UNSIGNED NULL');

        // Leave requests: company employees apply for leave at restaurant level
        DB::statement('ALTER TABLE hrm_leave_requests MODIFY COLUMN branch_id BIGINT UNSIGNED NULL');

        // Payroll adjustments: company employees have their own payroll adjustments
        DB::statement('ALTER TABLE hrm_payroll_adjustments MODIFY COLUMN branch_id BIGINT UNSIGNED NULL');

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // Only safe to reverse if all rows already have a branch_id value.
        // Skip silently if not.
    }
};
