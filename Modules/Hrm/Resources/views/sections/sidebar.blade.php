@if(in_array('HRM', restaurant_modules()))
    @if(
        user_can('Show Employee') ||
        user_can('Show Department') ||
        user_can('Show Designation') ||
        user_can('Show Shift') ||
        user_can('Manage Attendance') ||
        user_can('Manage Leave Types') ||
        user_can('Manage Leave Requests') ||
        user_can('Manage Holidays') ||
        user_can('Manage Payroll')
    )
        <x-sidebar-dropdown-menu name='HRM' isAddon="true" icon='staff' :active='request()->routeIs(["hrm.*"])'>
            @if(user_can('Show Employee'))
                @livewire('sidebar-dropdown-menu', ['name' => 'Employees', 'link' => route('hrm.employees.index'), 'active' => request()->routeIs('hrm.employees.index')])
            @endif
            @if(user_can('Show Department'))
                @livewire('sidebar-dropdown-menu', ['name' => 'Departments', 'link' => route('hrm.departments.index'), 'active' => request()->routeIs('hrm.departments.index')])
            @endif
            @if(user_can('Show Designation'))
                @livewire('sidebar-dropdown-menu', ['name' => 'Designations', 'link' => route('hrm.designations.index'), 'active' => request()->routeIs('hrm.designations.index')])
            @endif
            @if(user_can('Show Shift'))
                @livewire('sidebar-dropdown-menu', ['name' => 'Shifts', 'link' => route('hrm.shifts.index'), 'active' => request()->routeIs('hrm.shifts.index')])
            @endif

            @if(user_can('Manage Attendance'))
                @livewire('sidebar-dropdown-menu', ['name' => 'Attendance', 'link' => route('hrm.attendance.daily'), 'active' => request()->routeIs('hrm.attendance.*')])
            @endif

            @if(user_can('Manage Leave Types'))
                @livewire('sidebar-dropdown-menu', ['name' => 'Leave Types', 'link' => route('hrm.leave-types.index'), 'active' => request()->routeIs('hrm.leave-types.index')])
            @endif

            @if(user_can('Manage Leave Requests'))
                @livewire('sidebar-dropdown-menu', ['name' => 'Leave Requests', 'link' => route('hrm.leave-requests.index'), 'active' => request()->routeIs('hrm.leave-requests.index')])
            @endif

            @if(user_can('Manage Holidays'))
                @livewire('sidebar-dropdown-menu', ['name' => 'Holidays', 'link' => route('hrm.holidays.index'), 'active' => request()->routeIs('hrm.holidays.index')])
            @endif

            @if(user_can('Manage Payroll'))
                @livewire('sidebar-dropdown-menu', ['name' => 'Payroll', 'link' => route('hrm.payroll.index'), 'active' => request()->routeIs('hrm.payroll.*')])
            @endif

            @if(user_can('Manage Payroll'))
                @livewire('sidebar-dropdown-menu', ['name' => 'EPF/ETF Settings', 'link' => route('hrm.settings.epf-etf'), 'active' => request()->routeIs('hrm.settings.epf-etf')])
            @endif

            @if(user_can('Manage Payroll'))
                @livewire('sidebar-dropdown-menu', ['name' => 'Credit Purchases', 'link' => route('hrm.credit-purchases.index'), 'active' => request()->routeIs('hrm.credit-purchases.*')])
            @endif
        </x-sidebar-dropdown-menu>
    @endif
@endif
