<?php

namespace App\Livewire\Admin\Expenses;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Vehicle;
use App\Services\ExpenseReports as ExpenseReportService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

class Reports extends Component
{
    public string $groupBy = 'daily';
    public string $startDate = '';
    public string $endDate = '';
    public string $categoryFilter = '';
    public string $vehicleFilter = '';
    public string $methodFilter = '';

    public function mount(): void
    {
        $this->authorizeView();
        $today = CarbonImmutable::now(config('app.timezone', 'Asia/Colombo'));
        $this->startDate = $today->startOfMonth()->toDateString();
        $this->endDate = $today->toDateString();
    }

    public function updatedGroupBy(): void
    {
        if (! in_array($this->groupBy, ['daily', 'monthly', 'yearly', 'category', 'vehicle'], true)) $this->groupBy = 'daily';
    }
    public function updatedStartDate(): void { }
    public function updatedEndDate(): void { }
    public function updatedCategoryFilter(): void { }
    public function updatedVehicleFilter(): void { }
    public function updatedMethodFilter(): void { }

    public function exportFilters(): array
    {
        $this->authorizeView();

        return [
            'group' => $this->groupBy,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'category' => $this->categoryFilter,
            'vehicle' => $this->vehicleFilter,
            'method' => $this->methodFilter,
        ];
    }

    private function authorizeView(): void
    {
        $user = auth()->user();
        abort_unless($user?->hasRole('Owner') && $user->can('expenses.view'), 403);
    }

    public function render()
    {
        $this->authorizeView();
        $filters = $this->exportFilters();
        $reportService = app(ExpenseReportService::class);
        $range = $reportService->dateRange($filters);
        $query = $reportService->expenses($filters);
        if (! $range) $query->whereRaw('1 = 0');

        return view('livewire.admin.expenses.reports', [
            'rows' => $range ? $reportService->grouped($filters, $this->groupBy) : collect(),
            'totalExpenses' => $query->count(),
            'totalAmount' => $query->sum('amount'),
            'rangeLabel' => $range ? $range[0]->format(config('school.date_format', 'd M Y')).' – '.$range[1]->format(config('school.date_format', 'd M Y')) : 'Invalid date range',
            'rangeInvalid' => ! $range,
            'categories' => ExpenseCategory::query()->orderBy('name')->get(),
            'vehicles' => Vehicle::query()->orderBy('registration_number')->get(),
            'paymentMethods' => Expense::PAYMENT_METHODS,
            'groupLabels' => ['daily' => 'Daily Expenses', 'monthly' => 'Monthly Expenses', 'yearly' => 'Yearly Expenses', 'category' => 'Category-wise Expenses', 'vehicle' => 'Vehicle-wise Expenses'],
        ])->layout('layouts.admin');
    }
}
