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
        <div class="grid grid-cols-1 lg:grid-cols-6 gap-3 mb-3">
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

            <x-select class="w-full" wire:model.live="paymentStatusFilter">
                <option value="all">All statuses</option>
                <option value="paid">Paid</option>
                <option value="unpaid">Unpaid</option>
            </x-select>

            <x-input type="text" class="w-full" wire:model.live.debounce.300ms="search" placeholder="Search name/staff code" />
        </div>

        <div class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center">
            <div class="flex flex-col sm:flex-row gap-2 flex-1 items-center">
                <input type="file" wire:model="importFile" class="form-control flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700" />
                <x-button type="button" target="importExcel" wire:click="importExcel" class="inline-flex items-center whitespace-nowrap shrink-0 !px-3 !py-2">Import</x-button>
            </div>
        </div>

        @error('importFile')
            <x-alert type="danger" class="mt-3 mb-0">{{ $message }}</x-alert>
        @enderror
        @if($importMessage)
            <x-alert type="success" class="mt-3 mb-0">{{ $importMessage }}</x-alert>
        @endif
    </div>

    @if(!empty($payrollSummary))
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                <div class="text-xs text-gray-500 dark:text-gray-400">Employees</div>
                <div class="text-lg font-semibold text-gray-900 dark:text-white">{{ $payrollSummary['employee_count'] }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                <div class="text-xs text-gray-500 dark:text-gray-400">Total Payable</div>
                <div class="text-lg font-semibold text-emerald-600">{{ number_format((float) $payrollSummary['total_payable'], 2) }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                <div class="text-xs text-gray-500 dark:text-gray-400">Paid</div>
                <div class="text-lg font-semibold text-emerald-600">{{ $payrollSummary['paid_count'] }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                <div class="text-xs text-gray-500 dark:text-gray-400">Unpaid</div>
                <div class="text-lg font-semibold text-rose-600">{{ $payrollSummary['unpaid_count'] }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                <div class="text-xs text-gray-500 dark:text-gray-400">Paid Amount</div>
                <div class="text-lg font-semibold text-gray-900 dark:text-white">{{ number_format((float) $payrollSummary['paid_amount'], 2) }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                <div class="text-xs text-gray-500 dark:text-gray-400">Holidays (month)</div>
                <div class="text-lg font-semibold text-gray-900 dark:text-white">{{ $payrollSummary['holiday_count'] }}</div>
            </div>
        </div>
    @endif

    @can('Manage Payroll')
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-4 mb-4">
            <div class="flex flex-col lg:flex-row lg:items-end gap-3">
                <div class="flex-1 min-w-0 max-w-xs">
                    <x-label value="Bulk payment date (optional)" />
                    <x-input type="date" class="w-full" wire:model.defer="bulkPaymentDate" />
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Leave empty to use today’s date when marking paid.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2 pb-0.5">
                    <button
                        type="button"
                        wire:click="askBulkMarkPaid"
                        wire:loading.attr="disabled"
                        wire:target="askBulkMarkPaid,executeConfirmedAction"
                        class="inline-flex items-center justify-center px-3 py-2 bg-skin-base hover:bg-skin-base/[.8] border border-transparent rounded-lg font-semibold text-sm text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-50 transition ease-in-out duration-150 whitespace-nowrap shrink-0"
                    >Mark selected paid</button>
                    <x-secondary-button
                        type="button"
                        wire:click="askBulkClearPayment"
                        class="inline-flex items-center whitespace-nowrap shrink-0"
                    >Clear selected payment</x-secondary-button>
                </div>
            </div>
            @error('selectedEmployeeIds')
                <x-alert type="danger" class="mt-3 mb-0">{{ $message }}</x-alert>
            @enderror
            @error('bulkPaymentDate')
                <x-alert type="danger" class="mt-3 mb-0">{{ $message }}</x-alert>
            @enderror
        </div>
    @endcan

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
        <div class="p-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-600 dark:text-gray-300">
                        <th class="py-2 pr-3">
                            <input
                                type="checkbox"
                                class="rounded border-gray-300 dark:border-gray-600"
                                wire:click="toggleSelectAll($event.target.checked)"
                                @checked(count($selectedEmployeeIds) > 0 && count($selectedEmployeeIds) === count($payrollRows) && count($payrollRows) > 0)
                            />
                        </th>
                        <th class="py-2 pr-4">S/N</th>
                        <th class="py-2 pr-4">Name</th>
                        <th class="py-2 pr-4">Status</th>
                        <th class="py-2 pr-4">Working Days</th>
                        <th class="py-2 pr-4">Leave</th>
                        <th class="py-2 pr-4">Worked</th>
                        <th class="py-2 pr-4">Basic/Day</th>
                        <th class="py-2 pr-4">Basic Salary</th>
                        <th class="py-2 pr-4">Additional</th>
                        <th class="py-2 pr-4">Total Earn</th>
                        <th class="py-2 pr-4 text-rose-600">Advance</th>
                        <th class="py-2 pr-4 text-rose-600">EPF (8%)</th>
                        <th class="py-2 pr-4 text-indigo-600">ETF (Emp.)</th>
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
                            <td class="py-2 pr-3">
                                <input
                                    type="checkbox"
                                    class="rounded border-gray-300 dark:border-gray-600"
                                    value="{{ (int) $r['employee_id'] }}"
                                    wire:model.live="selectedEmployeeIds"
                                />
                            </td>
                            <td class="py-2 pr-4">{{ $r['sn'] }}</td>
                            <td class="py-2 pr-4 font-medium">
                                {{ $r['name'] }}
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $r['staff_code'] ?? '—' }}</div>
                                @if(!empty($r['workplace']))
                                    <div class="text-xs mt-0.5">
                                        <span @class([
                                            'inline-flex items-center px-1.5 py-0.5 rounded font-medium',
                                            'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300' => ($r['workplace'] ?? '') === 'Hotel',
                                            'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' => ($r['workplace'] ?? '') !== 'Hotel',
                                        ])>{{ $r['workplace'] }}</span>
                                    </div>
                                @endif
                            </td>
                            <td class="py-2 pr-4">
                                @if(!empty($r['is_paid']))
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">Paid</span>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $r['payment_date'] }}</div>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300">Unpaid</span>
                                @endif
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
                            <td class="py-2 pr-4 text-indigo-600">{{ number_format((float)($r['etf'] ?? 0), 2) }}</td>
                            <td class="py-2 pr-4 text-rose-600">{{ number_format((float)$r['time_deduction'], 2) }}</td>
                            <td class="py-2 pr-4 text-rose-600">{{ number_format((float)$r['credit_purchase'], 2) }}</td>
                            <td class="py-2 pr-4 text-rose-600">{{ number_format((float)($r['other_deduction'] ?? 0), 2) }}</td>
                            <td class="py-2 pr-4 font-medium text-rose-600">{{ number_format((float)$r['total_of_deduction'], 2) }}</td>
                            <td class="py-2 pr-4 font-bold text-emerald-600">{{ number_format((float)$r['payable_salary'], 2) }}</td>
                            <td class="py-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <x-secondary-button type="button" wire:click="openAdjustModal({{ (int) $r['employee_id'] }})">Adjust</x-secondary-button>
                                    <x-secondary-button type="button" wire:click="downloadEmployeePayslip({{ (int) $r['employee_id'] }})">Payslip</x-secondary-button>
                                    @if(!empty($r['is_paid']))
                                        <x-secondary-button type="button" wire:click="askClearPayment({{ (int) $r['employee_id'] }})">Mark Unpaid</x-secondary-button>
                                    @else
                                        <button
                                            type="button"
                                            wire:click="askMarkPaid({{ (int) $r['employee_id'] }})"
                                            wire:loading.attr="disabled"
                                            wire:target="askMarkPaid({{ (int) $r['employee_id'] }}),executeConfirmedAction"
                                            class="inline-flex items-center justify-center px-3 py-2 bg-skin-base hover:bg-skin-base/[.8] border border-transparent rounded-lg font-semibold text-sm text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-50 transition ease-in-out duration-150 whitespace-nowrap"
                                        >Mark paid</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="20" class="py-6 text-center text-gray-500 dark:text-gray-400">No employees found</td>
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
                    <x-label value="ETF (Employer)" />
                    <x-input type="number" step="0.01" class="w-full" wire:model.defer="etf" />
                    @error('etf') <span class="text-sm text-rose-600">{{ $message }}</span> @enderror
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Employer contribution — not deducted from payable</p>
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
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Set a date to mark as paid; clear to mark unpaid</p>
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

    <x-confirmation-modal wire:model.live="showConfirmModal">
        <x-slot name="title">
            {{ $confirmTitle }}
        </x-slot>

        <x-slot name="content">
            {{ $confirmMessage }}
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button type="button" wire:click="closeConfirmModal" wire:loading.attr="disabled">
                Cancel
            </x-secondary-button>

            @if(in_array($confirmAction, ['bulk_clear_payment', 'clear_payment'], true))
                <x-danger-button class="ms-3" type="button" wire:click="executeConfirmedAction" wire:loading.attr="disabled" wire:target="executeConfirmedAction">
                    Clear payment
                </x-danger-button>
            @else
                <button
                    type="button"
                    wire:click="executeConfirmedAction"
                    wire:loading.attr="disabled"
                    wire:target="executeConfirmedAction"
                    class="ms-3 inline-flex items-center justify-center px-3 py-2 bg-skin-base hover:bg-skin-base/[.8] border border-transparent rounded-lg font-semibold text-sm text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-50 transition ease-in-out duration-150"
                >Mark paid</button>
            @endif
        </x-slot>
    </x-confirmation-modal>
</div>
