<?php

namespace App\Livewire\Admin\Finance;

use App\Services\FinancialReports;
use App\Support\Lkr;
use Livewire\Component;
use Livewire\WithPagination;

class OutstandingReport extends Component
{
    use WithPagination;
    public string $search = '';
    public string $statusFilter = '';
    public string $direction = 'desc';

    public function mount(): void { $this->authorizeOwner(); }
    private function authorizeOwner(): void { $user = auth()->user(); abort_unless($user?->hasRole('Owner') && $user->can('financial-reports.view'), 403); }
    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedStatusFilter(): void { $this->resetPage(); }

    public function toggleSort(): void
    {
        $this->authorizeOwner();
        $this->direction = $this->direction === 'desc' ? 'asc' : 'desc';
        $this->resetPage();
    }

    public function exportFilters(): array
    {
        $this->authorizeOwner();
        return ['search' => $this->search, 'status' => $this->statusFilter, 'direction' => $this->direction];
    }

    public function render()
    {
        $this->authorizeOwner();
        $query = app(FinancialReports::class)->outstanding($this->exportFilters());
        $topBalances = (clone $query)->reorder()->orderByDesc('outstanding_balance')->limit(5)->with('student')->get();

        return view('livewire.admin.finance.outstanding-report', [
            'plans' => $query->paginate(15),
            'totalOutstanding' => (clone $query)->reorder()->sum('outstanding_balance'),
            'topBalances' => $topBalances,
        ])->layout('layouts.admin');
    }
}
