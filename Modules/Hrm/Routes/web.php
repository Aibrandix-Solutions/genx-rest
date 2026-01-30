<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\LocaleMiddleware;
use Modules\Hrm\Livewire\Attendance\DailyAttendance;
use Modules\Hrm\Livewire\Departments\DepartmentsList;
use Modules\Hrm\Livewire\Designations\DesignationsList;
use Modules\Hrm\Livewire\Employees\EmployeesList;
use Modules\Hrm\Livewire\Holidays\HolidaysList;
use Modules\Hrm\Livewire\Leave\LeaveRequestsList;
use Modules\Hrm\Livewire\Leave\LeaveTypesList;
use Modules\Hrm\Livewire\Payroll\PayrollMonthly;
use Modules\Hrm\Livewire\Settings\EpfEtfSettings;
use Modules\Hrm\Livewire\CreditPurchases\CreditPurchaseManager;
use Modules\Hrm\Livewire\Shifts\ShiftsList;

// Intentionally minimal for now.
// HRM UI routes (employees, attendance, leave, etc.) will be added after schema+models.
Route::middleware(['auth', config('jetstream.auth_session'), 'verified', LocaleMiddleware::class])
    ->prefix('hrm')
    ->name('hrm.')
    ->group(function () {
        Route::get('employees', EmployeesList::class)
            ->middleware('can:Show Employee')
            ->name('employees.index');

        Route::get('shifts', ShiftsList::class)
            ->middleware('can:Show Shift')
            ->name('shifts.index');

        Route::get('attendance/daily', DailyAttendance::class)
            ->middleware('can:Manage Attendance')
            ->name('attendance.daily');

        Route::get('departments', DepartmentsList::class)
            ->middleware('can:Show Department')
            ->name('departments.index');

        Route::get('designations', DesignationsList::class)
            ->middleware('can:Show Designation')
            ->name('designations.index');

        Route::get('leave/types', LeaveTypesList::class)
            ->middleware('can:Manage Leave Types')
            ->name('leave-types.index');

        Route::get('leave/requests', LeaveRequestsList::class)
            ->middleware('can:Manage Leave Requests')
            ->name('leave-requests.index');

        Route::get('holidays', HolidaysList::class)
            ->middleware('can:Manage Holidays')
            ->name('holidays.index');

        Route::get('payroll', PayrollMonthly::class)
            ->middleware('can:Manage Payroll')
            ->name('payroll.index');

        Route::get('settings/epf-etf', EpfEtfSettings::class)
            ->middleware('can:Manage Payroll')
            ->name('settings.epf-etf');

        Route::get('credit-purchases', CreditPurchaseManager::class)
            ->middleware('can:Manage Payroll')
            ->name('credit-purchases.index');
    });
