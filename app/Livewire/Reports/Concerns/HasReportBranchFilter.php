<?php

namespace App\Livewire\Reports\Concerns;

use App\Models\Branch;
use App\Services\ReportBranchScope;
use Illuminate\Support\Collection;

trait HasReportBranchFilter
{
    public string $branchFilter = ReportBranchScope::FILTER_CURRENT;

    protected function mountReportBranchFilter(): void
    {
        $restaurantId = (int) restaurant()->id;
        $cookie = request()->cookie('report_branch_filter', ReportBranchScope::FILTER_CURRENT);
        $this->branchFilter = ReportBranchScope::validateFilter($cookie, $restaurantId);
    }

    public function updatedBranchFilter(): void
    {
        $restaurantId = (int) restaurant()->id;
        $this->branchFilter = ReportBranchScope::validateFilter($this->branchFilter, $restaurantId);
        cookie()->queue(cookie('report_branch_filter', $this->branchFilter, 60 * 24 * 30));

        if (method_exists($this, 'loadReportWaiters')) {
            $this->loadReportWaiters();
        }

        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }

    protected function reportBranches(): Collection
    {
        return Branch::query()
            ->where('restaurant_id', restaurant()->id)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    protected function showBranchFilter(): bool
    {
        return $this->reportBranches()->count() > 1;
    }

    protected function showBranchColumn(): bool
    {
        return $this->branchFilter === ReportBranchScope::FILTER_ALL;
    }
}
