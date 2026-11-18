<?php

namespace App\Livewire\Admin\Expenses;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $categoryFilter = '';
    public string $vehicleFilter = '';
    public string $methodFilter = '';
    public string $dateFrom = '';
    public string $dateTo = '';

    public function mount(): void { $this->authorizeView(); }
    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedCategoryFilter(): void { $this->resetPage(); }
    public function updatedVehicleFilter(): void { $this->resetPage(); }
    public function updatedMethodFilter(): void { $this->resetPage(); }
    public function updatedDateFrom(): void { $this->resetPage(); }
    public function updatedDateTo(): void { $this->resetPage(); }

    public function deleteExpense(int $expenseId): void
    {
        $user = auth()->user();
        abort_unless($user?->hasRole('Owner') && $user->can('expenses.delete'), 403);

        $expense = Expense::query()->findOrFail($expenseId);
        $path = $expense->receipt_path;
        DB::transaction(fn () => $expense->delete());
        if ($path) {
            Storage::disk('local')->delete($path);
        }

        session()->flash('success', 'Expense deleted. Its audit entry has been retained.');
    }

    private function authorizeView(): void
    {
        $user = auth()->user();
        abort_unless($user?->hasRole('Owner') && $user->can('expenses.view'), 403);
    }

    public function render()
    {
        $this->authorizeView();
        $filters = [
            'startDate' => $this->dateFrom,
            'endDate' => $this->dateTo,
            'category' => $this->categoryFilter,
            'vehicle' => $this->vehicleFilter,
            'method' => $this->methodFilter,
            'search' => $this->search,
        ];
        $query = app(\App\Services\ExpenseReports::class)->expenses($filters);
        $rangeValid = app(\App\Services\ExpenseReports::class)->dateRange($filters) !== null;
        if (($this->dateFrom !== '' || $this->dateTo !== '') && ! $rangeValid) {
            $query->whereRaw('1 = 0');
        }

        return view('livewire.admin.expenses.index', [
            'expenses' => $query->orderByDesc('expense_date')->orderByDesc('id')->paginate(15),
            'categories' => ExpenseCategory::query()->orderBy('name')->get(),
            'vehicles' => Vehicle::query()->orderBy('registration_number')->get(),
            'paymentMethods' => Expense::PAYMENT_METHODS,
        ])->layout('layouts.admin');
    }
}
