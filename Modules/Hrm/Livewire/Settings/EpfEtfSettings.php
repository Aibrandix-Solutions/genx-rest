<?php

namespace Modules\Hrm\Livewire\Settings;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Modules\Hrm\Entities\HrmSetting;

class EpfEtfSettings extends Component
{
    use AuthorizesRequests;

    public float $epf_basic_salary = 0;
    public float $etf_basic_salary = 0;
    public float $epf_employee_rate = 8;
    public float $epf_employer_rate = 12;
    public float $etf_employer_rate = 3;
    public bool $epf_auto_calculate = true;
    public bool $etf_auto_calculate = true;

    public function mount(): void
    {
        $this->authorize('Manage Payroll');

        $this->loadSettings();
    }

    private function loadSettings(): void
    {
        $this->epf_basic_salary = HrmSetting::get('epf_basic_salary', 0);
        $this->etf_basic_salary = HrmSetting::get('etf_basic_salary', 0);
        $this->epf_employee_rate = HrmSetting::get('epf_employee_rate', 8);
        $this->epf_employer_rate = HrmSetting::get('epf_employer_rate', 12);
        $this->etf_employer_rate = HrmSetting::get('etf_employer_rate', 3);
        $this->epf_auto_calculate = HrmSetting::get('epf_auto_calculate', true);
        $this->etf_auto_calculate = HrmSetting::get('etf_auto_calculate', true);
    }

    public function save(): void
    {
        $this->authorize('Manage Payroll');

        $this->validate([
            'epf_basic_salary' => ['required', 'numeric', 'min:0'],
            'etf_basic_salary' => ['required', 'numeric', 'min:0'],
            'epf_employee_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'epf_employer_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'etf_employer_rate' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        HrmSetting::set('epf_basic_salary', $this->epf_basic_salary, 'number', 'Standard basic salary for EPF calculation');
        HrmSetting::set('etf_basic_salary', $this->etf_basic_salary, 'number', 'Standard basic salary for ETF calculation');
        HrmSetting::set('epf_employee_rate', $this->epf_employee_rate, 'number', 'Employee EPF contribution rate (%)');
        HrmSetting::set('epf_employer_rate', $this->epf_employer_rate, 'number', 'Employer EPF contribution rate (%)');
        HrmSetting::set('etf_employer_rate', $this->etf_employer_rate, 'number', 'Employer ETF contribution rate (%)');
        HrmSetting::set('epf_auto_calculate', $this->epf_auto_calculate ? 'true' : 'false', 'boolean', 'Auto-calculate EPF');
        HrmSetting::set('etf_auto_calculate', $this->etf_auto_calculate ? 'true' : 'false', 'boolean', 'Auto-calculate ETF');

        $this->dispatch('alert', type: 'success', message: 'EPF/ETF settings updated successfully');
    }

    public function render()
    {
        $this->authorize('Manage Payroll');

        // Calculate examples based on current settings
        $epfEmployeeAmount = ($this->epf_basic_salary * $this->epf_employee_rate) / 100;
        $epfEmployerAmount = ($this->epf_basic_salary * $this->epf_employer_rate) / 100;
        $etfEmployerAmount = ($this->etf_basic_salary * $this->etf_employer_rate) / 100;

        return view('hrm::livewire.settings.epf-etf-settings', [
            'epfEmployeeAmount' => $epfEmployeeAmount,
            'epfEmployerAmount' => $epfEmployerAmount,
            'etfEmployerAmount' => $etfEmployerAmount,
        ])->layout('layouts.app');
    }
}
