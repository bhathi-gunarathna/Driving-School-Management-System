<?php

namespace App\Livewire\Admin\Payments;

use App\Models\StudentPayment;
use App\Support\Money;
use Livewire\Component;

class Receipt extends Component
{
    public StudentPayment $payment;

    public function mount(StudentPayment $payment): void
    {
        $user = auth()->user();
        abort_unless($user?->hasRole('Owner') && $user->can('payments.view'), 403);
        $this->payment = $payment->load(['student', 'paymentPlan', 'receivedBy']);
    }

    public function render()
    {
        $previousCents = StudentPayment::where('payment_plan_id', $this->payment->payment_plan_id)
            ->where('id', '<', $this->payment->getKey())->get(['amount'])
            ->sum(fn (StudentPayment $p) => Money::toCents((string) $p->amount));
        $currentCents = Money::toCents((string) $this->payment->amount);
        $agreedCents = Money::toCents((string) $this->payment->paymentPlan->total_agreed_fee);

        return view('livewire.admin.payments.receipt', [
            'balanceBefore' => Money::fromCents(max(0, $agreedCents - $previousCents)),
            'balanceAfter' => Money::fromCents(max(0, $agreedCents - $previousCents - $currentCents)),
        ])->layout('layouts.admin');
    }
}
