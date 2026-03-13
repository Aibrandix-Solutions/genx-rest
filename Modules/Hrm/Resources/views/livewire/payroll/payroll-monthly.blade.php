<div class="w-full px-4 sm:px-6 lg:px-8 py-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between mb-6">
        <div class="space-y-1">
            <h2 class="text-2xl font-semibold tracking-tight leading-tight text-gray-900 dark:text-white">HRM - Payroll</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Monthly payroll based on attendance + adjustments</p>
        </div>

        @can('Manage Payroll')
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:flex lg:flex-wrap items-stretch gap-2 w-full lg:w-auto">
                <x-secondary-button type="button" wire:click="downloadImportTemplate">Download Template</x-secondary-button>
                <x-secondary-button type="button" wire:click="exportExcel">Export Excel</x-secondary-button>
                <x-secondary-button type="button" wire:click="exportPdf">Export PDF</x-secondary-button>
                <x-secondary-button type="button" wire:click="exportPayslips">Export Payslips</x-secondary-button>
                <a href="{{ route('hrm.settings.epf-etf') }}" class="inline-flex items-center justify-center text-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-50 transition ease-in-out duration-150">
                    EPF/ETF Settings
                </a>
            </div>
        @endcan
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-4 mb-6">
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-3 mb-3">
            <x-select class="w-full" wire:model.live="branchId">
                <option value="">Select branch</option>
                <option value="0">— Company Level —</option>
                @foreach($branches as $b)
                    <option value="{{ $b['id'] }}">{{ $b['name'] }}</option>
                @endforeach
            </x-select>

            <x-select class="w-full" wire:model.live="departmentId">
                <option value="">All departments</option>
                @foreach($departments as $d)
                    <option value="{{ $d['id'] }}">{{ $d['name'] }}</option>
                @endforeach
            </x-select>

            <x-select class="w-full" wire:model.live="designationId">
                <option value="">All designations</option>
                @foreach($designations as $d)
                    <option value="{{ $d['id'] }}">{{ $d['name'] }}</option>
                @endforeach
            </x-select>

            <x-input type="month" class="w-full" wire:model.live="month" />

            <x-input type="text" class="w-full" wire:model.live.debounce.300ms="search" placeholder="Search name/staff code" />
        </div>

        <div class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center">
            <div class="flex flex-col sm:flex-row gap-2 flex-1">
                <input type="file" wire:model="importFile" class="form-control flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700" />
                <x-button type="button" wire:click="importExcel" class="whitespace-nowrap">Import</x-button>
            </div>
        </div>

        @error('importFile')
            <div class="mt-2 text-sm text-rose-600">{{ $message }}</div>
        @enderror
        @if($importMessage)
            <div class="mt-2 text-sm text-gray-600 dark:text-gray-300">{{ $importMessage }}</div>
        @endif
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
        <div class="p-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-600 dark:text-gray-300">
                        <th class="py-2 pr-4">S/N</th>
                        <th class="py-2 pr-4">Name</th>
                        <th class="py-2 pr-4">Working Days</th>
                        <th class="py-2 pr-4">Leave</th>
                        <th class="py-2 pr-4">Worked</th>
                        <th class="py-2 pr-4">Basic/Day</th>
                        <th class="py-2 pr-4">Basic Salary</th>
                        <th class="py-2 pr-4">Additional</th>
                        <th class="py-2 pr-4">Total Earn</th>
                        <th class="py-2 pr-4 text-rose-600">Advance</th>
                        <th class="py-2 pr-4 text-rose-600">EPF (8%)</th>
                        <th class="py-2 pr-4 text-rose-600">Time Ded.</th>
                        <th class="py-2 pr-4 text-rose-600">Credit Purch.</th>
                        <th class="py-2 pr-4 text-rose-600">Other Ded.</th>
                        <th class="py-2 pr-4 text-rose-600">Total Ded.</th>
                        <th class="py-2 pr-4 font-semibold text-emerald-600">Payable</th>
                        <th class="py-2">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($payrollRows as $r)
                        <tr class="text-gray-900 dark:text-gray-100">
                            <td class="py-2 pr-4">{{ $r['sn'] }}</td>
                            <td class="py-2 pr-4 font-medium">
                                {{ $r['name'] }}
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $r['staff_code'] ?? '—' }}</div>
                            </td>
                            <td class="py-2 pr-4">{{ $r['total_of_working_days'] }}</td>
                            <td class="py-2 pr-4">{{ $r['total_leave'] }}</td>
                            <td class="py-2 pr-4">{{ $r['total_working_days'] }}</td>
                            <td class="py-2 pr-4">{{ number_format((float)$r['monthly_basic_salary_per_day'], 2) }}</td>
                            <td class="py-2 pr-4">{{ number_format((float)$r['monthly_basic_salary'], 2) }}</td>
                            <td class="py-2 pr-4 text-emerald-600">{{ number_format((float)$r['additional_pay'], 2) }}</td>
                            <td class="py-2 pr-4 font-medium">{{ number_format((float)$r['total_earning'], 2) }}</td>
                            <td class="py-2 pr-4 text-rose-600">{{ number_format((float)$r['advance'], 2) }}</td>
                            <td class="py-2 pr-4 text-rose-600">{{ number_format((float)$r['epf'], 2) }}</td>
                            <td class="py-2 pr-4 text-rose-600">{{ number_format((float)$r['time_deduction'], 2) }}</td>
                            <td class="py-2 pr-4 text-rose-600">{{ number_format((float)$r['credit_purchase'], 2) }}</td>
                            <td class="py-2 pr-4 text-rose-600">{{ number_format((float)($r['other_deduction'] ?? 0), 2) }}</td>
                            <td class="py-2 pr-4 font-medium text-rose-600">{{ number_format((float)$r['total_of_deduction'], 2) }}</td>
                            <td class="py-2 pr-4 font-bold text-emerald-600">{{ number_format((float)$r['payable_salary'], 2) }}</td>
                            <td class="py-2">
                                <div class="flex items-center gap-2">
                                    <x-secondary-button type="button" wire:click="openAdjustModal({{ (int) $r['employee_id'] }})">Adjust</x-secondary-button>
                                    <x-secondary-button type="button" wire:click="downloadEmployeePayslip({{ (int) $r['employee_id'] }})">Payslip</x-secondary-button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="17" class="py-6 text-center text-gray-500 dark:text-gray-400">No employees found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <x-dialog-modal wire:model.live="showAdjustModal" maxWidth="xl">
        <x-slot name="title">Payroll Adjustments</x-slot>
        <x-slot name="content">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <div>
                    <x-label value="Additional Pay" />
                    <x-input type="number" step="0.01" class="w-full" wire:model.defer="additional_pay" />
                    @error('additional_pay') <span class="text-sm text-rose-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <x-label value="Advance" />
                    <x-input type="number" step="0.01" class="w-full" wire:model.defer="advance" />
                    @error('advance') <span class="text-sm text-rose-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <x-label value="EPF" />
                    <x-input type="number" step="0.01" class="w-full" wire:model.defer="epf" />
                    @error('epf') <span class="text-sm text-rose-600">{{ $message }}</span> @enderror
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Auto-calculated if enabled in settings</p>
                </div>
                <div>
                    <x-label value="Time Deduction" />
                    <x-input type="number" step="0.01" class="w-full" wire:model.defer="time_deduction" />
                    @error('time_deduction') <span class="text-sm text-rose-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <x-label value="Credit Purchase" />
                    <x-input type="number" step="0.01" class="w-full" wire:model.defer="credit_purchase" />
                    @error('credit_purchase') <span class="text-sm text-rose-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <x-label value="Other Deduction" />
                    <x-input type="number" step="0.01" class="w-full" wire:model.defer="other_deduction" />
                    @error('other_deduction') <span class="text-sm text-rose-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <x-label value="Payment Date" />
                    <x-input type="date" class="w-full" wire:model.defer="payment_date" />
                    @error('payment_date') <span class="text-sm text-rose-600">{{ $message }}</span> @enderror
                </div>
                <div class="lg:col-span-3">
                    <x-label value="Note" />
                    <textarea class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900" rows="2" wire:model.defer="note"></textarea>
                    @error('note') <span class="text-sm text-rose-600">{{ $message }}</span> @enderror
                </div>
            </div>
        </x-slot>
        <x-slot name="footer">
            <x-secondary-button type="button" wire:click="closeAdjustModal">Cancel</x-secondary-button>
            <x-button type="button" wire:click="saveAdjustment">Save</x-button>
        </x-slot>
    </x-dialog-modal>
</div>
