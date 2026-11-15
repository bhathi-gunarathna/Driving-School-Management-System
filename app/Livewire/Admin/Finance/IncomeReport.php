<?php

namespace App\Livewire\Admin\Finance;

use App\Services\FinancialReports;
use App\Support\FinancialReportFilters;
use App\Support\Lkr;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class IncomeReport extends Component
{
    use WithPagination;

    public string $period = 'this_month';
    public string $startDate = '';
    public string $endDate = '';
    public string $methodFilter = '';
    public string $search = '';
    public string $sortField = 'payment_date';
    public string $sortDirection = 'desc';

    public function mount(): void { $this->authorizeOwner(); }
    private function authorizeOwner(): void { $user = auth()->user(); abort_unless($user?->hasRole('Owner') && $user->can('financial-reports.view'), 403); }

    public function updatedPeriod(): void { $this->resetPage(); }
    public function updatedStartDate(): void { $this->resetPage(); }
    public function updatedEndDate(): void { $this->resetPage(); }
    public function updatedMethodFilter(): void { $this->resetPage(); }
    public function updatedSearch(): void { $this->resetPage(); }

    public function sortBy(string $field): void
    {
        $this->authorizeOwner();
        abort_unless(in_array($field, ['payment_date', 'amount'], true), 422);
        $this->sortDirection = $this->sortField === $field && $this->sortDirection === 'desc' ? 'asc' : 'desc';
        $this->sortField = $field;
        $this->resetPage();
    }

    public function showDate(string $date): void
    {
        $this->authorizeOwner();
        abort_unless(preg_match('/^\d{4}-\d{2}-\d{2}$/', $date), 422);
        $this->period = 'custom';
        $this->startDate = $this->endDate = $date;
        $this->resetPage();
    }

    public function showMonth(string $month): void
    {
        $this->authorizeOwner();
        abort_unless(preg_match('/^\d{4}-\d{2}$/', $month), 422);
        $start = CarbonImmutable::createFromFormat('!Y-m', $month, config('app.timezone', 'UTC'));
        abort_unless($start && $start->format('Y-m') === $month, 422);
        $this->period = 'custom';
        $this->startDate = $start->startOfMonth()->toDateString();
        $this->endDate = $start->endOfMonth()->toDateString();
        $this->resetPage();
    }

    public function exportFilters(): array
    {
        $this->authorizeOwner();
        return [
            'period' => $this->period,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'method' => $this->methodFilter,
            'search' => $this->search,
            'sort' => $this->sortField,
            'direction' => $this->sortDirection,
        ];
    }

    public function render()
    {
        $this->authorizeOwner();
        $filters = $this->exportFilters();
        $reports = app(FinancialReports::class);
        $filterHelper = app(FinancialReportFilters::class);
        $range = $filterHelper->dateRange($filters);
        $query = $reports->payments($filters);
        $rollup = (clone $query)->reorder()->selectRaw('COUNT(*) as transaction_count, COALESCE(SUM(amount), 0) as income_total, COALESCE(AVG(amount), 0) as average_amount')->first();
        $methods = (clone $query)->reorder()->selectRaw('payment_method, COUNT(*) as transactions, COALESCE(SUM(amount), 0) as income')
            ->groupBy('payment_method')->orderBy('payment_method')->get();
        $daily = $range ? $reports->dailyIncome($filters) : collect();
        $monthly = $range ? $reports->monthlyIncome($filters) : collect();
        $chartRows = $range && $range[0]->diffInDays($range[1]) <= 31 ? $daily : $monthly;
        $chartField = $range && $range[0]->diffInDays($range[1]) <= 31 ? 'report_date' : 'report_month';
        $chartMax = max(1, (float) $chartRows->max('income'));

        return view('livewire.admin.finance.income-report', [
            'range' => $range,
            'rangeLabel' => $range ? $range[0]->format(config('school.date_format', 'd M Y')).' – '.$range[1]->format(config('school.date_format', 'd M Y')) : null,
            'incomeTotal' => $rollup->income_total ?? 0,
            'transactionCount' => $rollup->transaction_count ?? 0,
            'averageAmount' => $rollup->average_amount ?? 0,
            'methods' => $methods,
            'methodTotal' => max(1, (float) $methods->sum('income')),
            'dailyRows' => $daily,
            'monthlyRows' => $monthly,
            'transactions' => $query->paginate(15),
            'chartRows' => $chartRows,
            'chartField' => $chartField,
            'chartMax' => $chartMax,
            'customRangeInvalid' => $this->period === 'custom' && $range === null,
        ])->layout('layouts.admin');
    }
}
