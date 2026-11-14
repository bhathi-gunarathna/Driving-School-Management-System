<?php

namespace App\Livewire\Admin\Payments;

use App\Models\Student;
use App\Models\StudentPaymentPlan;
use App\Services\StudentPaymentService;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Create extends Component
{
    public Student $student;
    public string $amount = '';
    public string $payment_date = '';
    public string $payment_method = 'cash';
    public string $reference_number = '';
    public string $notes = '';

    public function mount(Student $student): void
    {
        $user = auth()->user();
        abort_unless($user?->hasRole('Owner') && $user->can('payments.manage'), 403);
        $this->student = $student->load('paymentPlan');
        abort_unless($this->student->paymentPlan, 404);
        $this->payment_date = now()->toDateString();
    }

    protected function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:9999999999.99'],
            'payment_date' => ['required', 'date', 'before_or_equal:today'],
            'payment_method' => ['required', Rule::in(['cash', 'bank_transfer', 'card', 'other'])],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function record()
    {
        $user = auth()->user();
        abort_unless($user?->hasRole('Owner') && $user->can('payments.manage'), 403);
        $data = $this->validate();
        $payment = app(StudentPaymentService::class)->recordPayment(
            $this->student->paymentPlan->getKey(), $user, $data,
        );

        session()->flash('success', 'Payment recorded. Receipt '.$payment->receipt_number.' is ready.');
        return $this->redirectRoute('admin.payments.receipt', ['payment' => $payment->getKey()]);
    }

    public function render()
    {
        return view('livewire.admin.payments.create', [
            'plan' => $this->student->paymentPlan,
        ])->layout('layouts.admin');
    }
}
