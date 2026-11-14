<?php

namespace App\Livewire\Admin\Payments;

use App\Models\StudentPayment;
use App\Models\StudentPaymentPlan;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public string $methodFilter = '';

    public function mount(): void
    {
        $user = auth()->user();
        abort_unless($user?->hasRole('Owner') && $user->can('payments.view'), 403);
    }

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedStatusFilter(): void { $this->resetPage(); }
    public function updatedMethodFilter(): void { $this->resetPage(); }

    public function render()
    {
        $plans = StudentPaymentPlan::query();
        $payments = StudentPayment::query();
        $search = trim($this->search);
        if ($search !== '') {
            $studentSearch = fn (Builder $q) => $q->where(function (Builder $matches) use ($search): void {
                $matches->where('student_number', 'like', "%{$search}%")
                    ->orWhere('full_name', 'like', "%{$search}%")
                    ->orWhere('nic_number', 'like', "%{$search}%");
            });
            $plans->whereHas('student', $studentSearch);
            $payments->where(function (Builder $matches) use ($studentSearch, $search): void {
                $matches->whereHas('student', $studentSearch)
                    ->orWhere('receipt_number', 'like', "%{$search}%");
            });
        }
        if ($this->statusFilter !== '') $plans->where('payment_status', $this->statusFilter);
        if ($this->statusFilter !== '') $payments->whereHas('paymentPlan', fn (Builder $q) => $q->where('payment_status', $this->statusFilter));
        if ($this->methodFilter !== '') $payments->where('payment_method', $this->methodFilter);

        return view('livewire.admin.payments.index', [
            'plans' => $plans->with('student')->orderByDesc('id')->paginate(10),
            'paymentRows' => $payments->with(['student', 'receivedBy'])->orderByDesc('payment_date')->orderByDesc('id')->paginate(10, ['*'], 'paymentsPage'),
            'totalFees' => StudentPaymentPlan::sum('total_agreed_fee'),
            'totalReceived' => StudentPayment::sum('amount'),
            'totalOutstanding' => StudentPaymentPlan::sum('outstanding_balance'),
            'fullyPaidCount' => StudentPaymentPlan::where('payment_status', StudentPaymentPlan::STATUS_FULLY_PAID)->count(),
        ])->layout('layouts.admin');
    }
}
