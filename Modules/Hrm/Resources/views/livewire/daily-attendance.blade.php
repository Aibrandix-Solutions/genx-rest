<div class="w-full px-4 sm:px-6 lg:px-8 py-6">
    <div class="flex items-start justify-between mb-6">
        <div class="space-y-1">
            <h2 class="text-2xl font-semibold tracking-tight leading-tight text-gray-900 dark:text-white">HRM - Attendance</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Mark daily attendance and view summaries</p>
        </div>

        @can('Manage Attendance')
            <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                <input type="file" wire:model="importFile" class="block w-full sm:w-auto text-sm text-gray-600 dark:text-gray-300" />
                <x-button type="button" wire:click="importExcel" wire:loading.attr="disabled" wire:target="importFile">Import</x-button>
                <x-secondary-button type="button" wire:click="downloadImportTemplate">Template</x-secondary-button>
                @if($tab === 'daily')
                    <x-secondary-button type="button" wire:click="exportExcel">Export Daily</x-secondary-button>
                @elseif($tab === 'all')
                    <x-secondary-button type="button" wire:click="exportRangeExcel">Export Range</x-secondary-button>
                    <x-secondary-button type="button" wire:click="exportMonthlyExcel">Export Monthly</x-secondary-button>
                @endif
                <span class="text-sm text-gray-500 dark:text-gray-400" wire:loading wire:target="importFile">Uploading…</span>
            </div>
        @endcan
    </div>

    @can('Manage Attendance')
        @error('importFile')
            <div class="mb-4 text-sm text-rose-600">{{ $message }}</div>
        @enderror

        @if($importMessage)
            <div class="mb-4 text-sm text-gray-600 dark:text-gray-300">{{ $importMessage }}</div>
        @endif
    @endcan

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-4 mb-6">
        <div class="flex flex-col gap-4">
            <div class="flex items-center gap-2">
                <button type="button" wire:click="$set('tab','daily')" class="px-3 py-1.5 rounded-md text-sm border {{ $tab === 'daily' ? 'bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600' : 'border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300' }}">Daily</button>
                <button type="button" wire:click="$set('tab','all')" class="px-3 py-1.5 rounded-md text-sm border {{ $tab === 'all' ? 'bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600' : 'border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300' }}">All</button>
                <button type="button" wire:click="$set('tab','byShift')" class="px-3 py-1.5 rounded-md text-sm border {{ $tab === 'byShift' ? 'bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600' : 'border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300' }}">By Shift</button>
                <button type="button" wire:click="$set('tab','byDate')" class="px-3 py-1.5 rounded-md text-sm border {{ $tab === 'byDate' ? 'bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600' : 'border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300' }}">By Date</button>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-3">
                <x-select class="w-full" wire:model.live="branchId">
                    <option value="">Select branch</option>
                    <option value="0">— Company Level —</option>
                    @foreach($branches as $b)
                        <option value="{{ $b['id'] }}">{{ $b['name'] }}</option>
                    @endforeach
                </x-select>

                @if($tab === 'daily' || $tab === 'byShift')
                    <x-input type="date" class="w-full" wire:model.live="date" />
                    <x-select class="w-full" wire:model.live="shiftId">
                        <option value="">Default shift (optional)</option>
                        @foreach($shifts as $s)
                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                        @endforeach
                    </x-select>
                @else
                    <x-select class="w-full" wire:model.live="dateRangeType">
                        <option value="today">Today</option>
                        <option value="yesterday">Yesterday</option>
                        <option value="currentWeek">Current Week</option>
                        <option value="lastWeek">Last Week</option>
                        <option value="last7Days">Last 7 Days</option>
                        <option value="currentMonth">Current Month</option>
                        <option value="lastMonth">Last Month</option>
                        <option value="custom">Custom</option>
                    </x-select>
                    <x-input type="date" class="w-full" wire:model.live="fromDate" />
                    <x-input type="date" class="w-full" wire:model.live="toDate" />
                @endif

                @if($tab === 'daily')
                    <x-input type="text" class="w-full lg:col-span-2" wire:model.live.debounce.300ms="search" placeholder="Search employee name" />
                @elseif($tab === 'all')
                    <x-input type="text" class="w-full lg:col-span-2" wire:model.live.debounce.300ms="search" placeholder="Search employee name or staff code" />
                @endif
            </div>

        </div>
    </div>

    @if($branchId === null)
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-4">
            <div class="text-sm text-gray-600 dark:text-gray-300">Select a branch to continue.</div>
        </div>
    @else
        @if($tab === 'daily')
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
                <div class="p-4 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                        <tr class="text-left text-gray-600 dark:text-gray-300">
                            <th class="py-2 pr-4">Employee</th>
                            <th class="py-2 pr-4">Status</th>
                            <th class="py-2 pr-4">Shift</th>
                            <th class="py-2 pr-4">Clock In</th>
                            <th class="py-2 pr-4">Clock Out</th>
                            <th class="py-2 pr-4">Work Duration</th>
                            <th class="py-2">Actions</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($employees as $emp)
                            @php($log = $attendanceByEmployee[$emp->id] ?? null)
                            <tr class="text-gray-900 dark:text-gray-100">
                                <td class="py-2 pr-4 font-medium">{{ $emp->name }}</td>
                                <td class="py-2 pr-4">
                                    @if($log)
                                        {{ $log->status ?? '—' }}
                                    @else
                                        {{ \Carbon\Carbon::parse($date)->startOfDay()->lt(now()->startOfDay()) ? 'absent' : '—' }}
                                    @endif
                                </td>
                                <td class="py-2 pr-4">{{ $log?->shift?->name ?? '—' }}</td>
                                <td class="py-2 pr-4">{{ $log?->clock_in_at?->format('H:i') ?? '—' }}</td>
                                <td class="py-2 pr-4">{{ $log?->clock_out_at?->format('H:i') ?? '—' }}</td>
                                <td class="py-2 pr-4">{{ $workDurationByEmployee[$emp->id] ?? '—' }}</td>
                                <td class="py-2">
                                    <div class="flex items-center gap-2">
                                        @can('Manage Attendance')
                                            <x-secondary-button type="button" wire:click="open({{ $emp->id }})">Mark</x-secondary-button>
                                            @if($log)
                                                <x-danger-button type="button" wire:click="confirmClear({{ $emp->id }})">Clear</x-danger-button>
                                            @endif
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-6 text-center text-gray-500 dark:text-gray-400">No employees found</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-4">
                    {{ $employees->links() }}
                </div>
            </div>
        @elseif($tab === 'all')
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
                @if(!empty($allMatrixDates))
                    <div class="p-4 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                            <tr class="text-left text-gray-600 dark:text-gray-300">
                                <th class="py-2 pr-4">Employee</th>
                                <th class="py-2 pr-4">Staff Code</th>
                                @foreach($allMatrixDates as $d)
                                    <th class="py-2 pr-3 whitespace-nowrap">{{ \Carbon\Carbon::parse($d)->format('m-d') }}</th>
                                @endforeach
                                <th class="py-2 pr-4 whitespace-nowrap">Total P</th>
                                <th class="py-2 pr-4 whitespace-nowrap">Total A/L</th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($allMatrixRows as $row)
                                <tr class="text-gray-900 dark:text-gray-100">
                                    <td class="py-2 pr-4 font-medium">{{ $row['employee_name'] ?? '—' }}</td>
                                    <td class="py-2 pr-4">{{ $row['staff_code'] ?? '—' }}</td>
                                    @foreach($allMatrixDates as $d)
                                        <td class="py-2 pr-3">{{ $row[$d] ?? '' }}</td>
                                    @endforeach
                                    <td class="py-2 pr-4">{{ $row['total_present'] ?? 0 }}</td>
                                    <td class="py-2 pr-4">{{ $row['total_absent_leave'] ?? 0 }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ 2 + count($allMatrixDates) + 2 }}" class="py-6 text-center text-gray-500 dark:text-gray-400">No employees found</td>
                                </tr>
                            @endforelse

                            @if(!empty($allMatrixRows))
                                <tr class="text-gray-900 dark:text-gray-100 font-medium">
                                    <td class="py-2 pr-4">TOTAL PRESENT</td>
                                    <td class="py-2 pr-4"></td>
                                    @foreach($allMatrixDates as $d)
                                        <td class="py-2 pr-3">{{ $allTotalsPerDay[$d] ?? 0 }}</td>
                                    @endforeach
                                    <td class="py-2 pr-4">{{ array_sum($allTotalsPerDay ?? []) }}</td>
                                    <td class="py-2 pr-4"></td>
                                </tr>
                            @endif
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-4 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                            <tr class="text-left text-gray-600 dark:text-gray-300">
                                <th class="py-2 pr-4">Date</th>
                                <th class="py-2 pr-4">Employee</th>
                                <th class="py-2 pr-4">Staff Code</th>
                                <th class="py-2 pr-4">Status</th>
                                <th class="py-2 pr-4">Shift</th>
                                <th class="py-2 pr-4">Clock In</th>
                                <th class="py-2 pr-4">Clock Out</th>
                                <th class="py-2 pr-4">Late (min)</th>
                                <th class="py-2 pr-4">Note</th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse(($rangeLogs ?? []) as $log)
                                <tr class="text-gray-900 dark:text-gray-100">
                                    <td class="py-2 pr-4 font-medium">{{ $log->date?->format('Y-m-d') ?? $log->getRawOriginal('date') }}</td>
                                    <td class="py-2 pr-4">{{ $log->employee?->name ?? '—' }}</td>
                                    <td class="py-2 pr-4">{{ $log->employee?->staff_code ?? '—' }}</td>
                                    <td class="py-2 pr-4">{{ $log->status ?? '—' }}</td>
                                    <td class="py-2 pr-4">{{ $log->shift?->name ?? '—' }}</td>
                                    <td class="py-2 pr-4">{{ $log->clock_in_at?->format('H:i') ?? '—' }}</td>
                                    <td class="py-2 pr-4">{{ $log->clock_out_at?->format('H:i') ?? '—' }}</td>
                                    <td class="py-2 pr-4">{{ $log->late_minutes ?? 0 }}</td>
                                    <td class="py-2 pr-4">{{ $log->note ?: '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="py-6 text-center text-gray-500 dark:text-gray-400">No attendance logs found</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($rangeLogs)
                        <div class="p-4">
                            {{ $rangeLogs->links() }}
                        </div>
                    @endif
                @endif
            </div>
        @elseif($tab === 'byShift')
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-4">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-3 mb-4">
                    <x-select class="w-full" wire:model.live="summaryShiftId">
                        <option value="">Select shift</option>
                        @foreach($shifts as $s)
                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                        @endforeach
                    </x-select>
                    <div class="text-sm text-gray-500 dark:text-gray-400 flex items-center">Date: {{ $date }}</div>
                </div>

                @if(!$summaryShiftId)
                    <div class="text-sm text-gray-600 dark:text-gray-300">Select a shift to view present/absent employees.</div>
                @else
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        <div>
                            <div class="font-medium text-gray-900 dark:text-gray-100 mb-2">Present ({{ $summaryShift['present']->count() }})</div>
                            <div class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $summaryShift['present']->implode(', ') ?: '—' }}</div>
                        </div>
                        <div>
                            <div class="font-medium text-gray-900 dark:text-gray-100 mb-2">Absent/Leave ({{ $summaryShift['absent']->count() }})</div>
                            <div class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $summaryShift['absent']->implode(', ') ?: '—' }}</div>
                        </div>
                    </div>
                @endif
            </div>
        @else
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
                <div class="p-4 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                        <tr class="text-left text-gray-600 dark:text-gray-300">
                            <th class="py-2 pr-4">Date</th>
                            <th class="py-2 pr-4">Present</th>
                            <th class="py-2 pr-4">Absent/Leave</th>
                            <th class="py-2 pr-4">Present Names</th>
                            <th class="py-2 pr-4">Absent Names</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($byDateSummary as $d => $row)
                            <tr class="text-gray-900 dark:text-gray-100">
                                <td class="py-2 pr-4 font-medium">{{ $d }}</td>
                                <td class="py-2 pr-4">{{ $row['present_count'] }}</td>
                                <td class="py-2 pr-4">{{ $row['absent_count'] }}</td>
                                <td class="py-2 pr-4 whitespace-pre-wrap">{{ collect($row['present_names'])->implode(', ') ?: '—' }}</td>
                                <td class="py-2 pr-4 whitespace-pre-wrap">{{ collect($row['absent_names'])->implode(', ') ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-6 text-center text-gray-500 dark:text-gray-400">No attendance logs found</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @endif

    <x-dialog-modal wire:model.live="showModal">
        <x-slot name="title">
            {{ __('Mark Attendance') }}
        </x-slot>

        <x-slot name="content">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-label value="{{ __('Status') }}" />
                    <x-select class="mt-1 block w-full" wire:model.defer="status">
                        <option value="present">{{ __('Present') }}</option>
                        <option value="absent">{{ __('Absent') }}</option>
                        <option value="leave">{{ __('Leave') }}</option>
                        <option value="half_day">{{ __('Half Day') }}</option>
                        <option value="late">{{ __('Late') }}</option>
                    </x-select>
                    <x-input-error for="status" class="mt-2" />
                </div>

                <div>
                    <x-label value="{{ __('Shift') }}" />
                    <x-select class="mt-1 block w-full" wire:model.defer="shift_id">
                        <option value="">{{ __('None') }}</option>
                        @foreach($shifts as $s)
                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                        @endforeach
                    </x-select>
                    <x-input-error for="shift_id" class="mt-2" />
                </div>

                <div>
                    <x-label value="{{ __('Clock In') }}" />
                    <x-input type="datetime-local" class="mt-1 block w-full" wire:model.defer="clock_in_at" />
                    <x-input-error for="clock_in_at" class="mt-2" />
                </div>

                <div>
                    <x-label value="{{ __('Clock Out') }}" />
                    <x-input type="datetime-local" class="mt-1 block w-full" wire:model.defer="clock_out_at" />
                    <x-input-error for="clock_out_at" class="mt-2" />
                </div>

                <div>
                    <x-label value="{{ __('Late Minutes') }}" />
                    <x-input type="number" min="0" class="mt-1 block w-full" wire:model.defer="late_minutes" />
                    <x-input-error for="late_minutes" class="mt-2" />
                </div>

                <div class="sm:col-span-2">
                    <x-label value="{{ __('Note') }}" />
                    <textarea class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" rows="3" wire:model.defer="note"></textarea>
                    <x-input-error for="note" class="mt-2" />
                </div>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showModal', false)">{{ __('Cancel') }}</x-secondary-button>
            <x-button class="ml-2" wire:click="save">{{ __('Save') }}</x-button>
        </x-slot>
    </x-dialog-modal>

    <x-confirmation-modal wire:model.live="showClearModal">
        <x-slot name="title">{{ __('Clear Attendance') }}</x-slot>
        <x-slot name="content">{{ __('Are you sure you want to clear this employee\'s attendance for the selected date?') }}</x-slot>
        <x-slot name="footer">
            <x-secondary-button wire:click="cancelClear">{{ __('Cancel') }}</x-secondary-button>
            <x-danger-button class="ml-2" wire:click="clear">{{ __('Clear') }}</x-danger-button>
        </x-slot>
    </x-confirmation-modal>
</div>
