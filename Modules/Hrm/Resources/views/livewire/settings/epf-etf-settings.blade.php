<div class="w-full px-4 sm:px-6 lg:px-8 py-6">
    <div class="flex items-start justify-between mb-6">
        <div class="space-y-1">
            <h2 class="text-2xl font-semibold tracking-tight leading-tight text-gray-900 dark:text-white">EPF/ETF Settings</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Configure standard basic salary and contribution rates for EPF/ETF calculations</p>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Standard Basic Salary</h3>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            Set a uniform basic salary amount for EPF/ETF calculation. This same amount will be used for all employees when auto-calculation is enabled.
        </p>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div>
                <x-label value="EPF Basic Salary (per month)" />
                <x-input type="number" step="0.01" class="w-full" wire:model.live.debounce.500ms="epf_basic_salary" />
                @error('epf_basic_salary') <span class="text-sm text-rose-600">{{ $message }}</span> @enderror
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Base salary amount used for EPF calculation</p>
            </div>

            <div>
                <x-label value="ETF Basic Salary (per month)" />
                <x-input type="number" step="0.01" class="w-full" wire:model.live.debounce.500ms="etf_basic_salary" />
                @error('etf_basic_salary') <span class="text-sm text-rose-600">{{ $message }}</span> @enderror
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Base salary amount used for ETF calculation</p>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Contribution Rates</h3>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            Set the percentage rates for EPF and ETF contributions. Standard rates: Employee EPF 8%, Employer EPF 12%, Employer ETF 3%.
        </p>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div>
                <x-label value="Employee EPF Rate (%)" />
                <x-input type="number" step="0.01" class="w-full" wire:model.live.debounce.500ms="epf_employee_rate" />
                @error('epf_employee_rate') <span class="text-sm text-rose-600">{{ $message }}</span> @enderror
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Deducted from employee salary</p>
            </div>

            <div>
                <x-label value="Employer EPF Rate (%)" />
                <x-input type="number" step="0.01" class="w-full" wire:model.live.debounce.500ms="epf_employer_rate" />
                @error('epf_employer_rate') <span class="text-sm text-rose-600">{{ $message }}</span> @enderror
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Employer contribution</p>
            </div>

            <div>
                <x-label value="Employer ETF Rate (%)" />
                <x-input type="number" step="0.01" class="w-full" wire:model.live.debounce.500ms="etf_employer_rate" />
                @error('etf_employer_rate') <span class="text-sm text-rose-600">{{ $message }}</span> @enderror
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Employer contribution</p>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Auto-Calculation</h3>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="flex items-center gap-3">
                <input type="checkbox" wire:model.live="epf_auto_calculate" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500" />
                <div>
                    <x-label value="Auto-calculate EPF" class="mb-0" />
                    <p class="text-xs text-gray-500 dark:text-gray-400">Automatically calculate EPF in payroll based on standard basic salary</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <input type="checkbox" wire:model.live="etf_auto_calculate" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500" />
                <div>
                    <x-label value="Auto-calculate ETF" class="mb-0" />
                    <p class="text-xs text-gray-500 dark:text-gray-400">Automatically calculate ETF in payroll based on standard basic salary</p>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800 p-6 mb-6">
        <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-4">Calculation Example</h3>
        <p class="text-sm text-blue-800 dark:text-blue-200 mb-4">Based on current settings:</p>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">Employee EPF Deduction</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($epfEmployeeAmount, 2) }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ number_format($epf_basic_salary, 2) }} × {{ $epf_employee_rate }}%</p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">Employer EPF Contribution</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($epfEmployerAmount, 2) }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ number_format($epf_basic_salary, 2) }} × {{ $epf_employer_rate }}%</p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">Employer ETF Contribution</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($etfEmployerAmount, 2) }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ number_format($etf_basic_salary, 2) }} × {{ $etf_employer_rate }}%</p>
            </div>
        </div>

        <div class="mt-4 pt-4 border-t border-blue-200 dark:border-blue-800">
            <div class="flex justify-between items-center">
                <span class="text-sm font-medium text-blue-900 dark:text-blue-100">Total Monthly Contribution:</span>
                <span class="text-xl font-bold text-blue-900 dark:text-blue-100">{{ number_format($epfEmployeeAmount + $epfEmployerAmount + $etfEmployerAmount, 2) }}</span>
            </div>
            <p class="text-xs text-blue-700 dark:text-blue-300 mt-2">Employee pays {{ number_format($epfEmployeeAmount, 2) }}, Employer pays {{ number_format($epfEmployerAmount + $etfEmployerAmount, 2) }}</p>
        </div>
    </div>

    <div class="bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-800 p-4 mb-6">
        <h4 class="text-sm font-semibold text-amber-900 dark:text-amber-100 mb-2">⚠️ Important Notes:</h4>
        <ul class="text-sm text-amber-800 dark:text-amber-200 space-y-1 list-disc list-inside">
            <li>EPF is deducted from employee's total payable salary</li>
            <li>ETF is an employer-only contribution (not deducted from employee)</li>
            <li>Standard basic salary applies to all employees uniformly</li>
            <li>Auto-calculation will override manual EPF/ETF entries in payroll adjustments</li>
            <li>Changes take effect from the next payroll run</li>
        </ul>
    </div>

    <div class="flex justify-end">
        <x-button type="button" wire:click="save">Save Settings</x-button>
    </div>
</div>
