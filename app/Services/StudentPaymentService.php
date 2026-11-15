<?php

namespace App\Services;

use App\Models\Student;
use App\Models\StudentPayment;
use App\Models\StudentPaymentInstallment;
use App\Models\StudentPaymentPlan;
use App\Models\User;
use App\Models\SystemSetting;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StudentPaymentService
{
    public function createPlan(Student $student, int $totalFeeCents, string $planType): StudentPaymentPlan
    {
        $installmentCount = StudentPaymentPlan::installmentCount($planType);

        $plan = StudentPaymentPlan::create([
            'student_id' => $student->getKey(),
            'total_agreed_fee' => Money::fromCents($totalFeeCents),
            'plan_type' => $planType,
            'number_of_installments' => $installmentCount,
            'total_amount_paid' => Money::fromCents(0),
            'outstanding_balance' => Money::fromCents($totalFeeCents),
            'payment_status' => $totalFeeCents === 0
                ? StudentPaymentPlan::STATUS_FULLY_PAID
                : StudentPaymentPlan::STATUS_PENDING,
        ]);

        $installments = Money::splitCents($totalFeeCents, $installmentCount);

        foreach ($installments as $index => $amountCents) {
            StudentPaymentInstallment::create([
                'payment_plan_id' => $plan->getKey(),
                'installment_number' => $index + 1,
                'planned_amount' => Money::fromCents($amountCents),
            ]);
        }

        return $plan;
    }

    /** @param array{amount: string, payment_date: string, payment_method: string, reference_number?: ?string, notes?: ?string} $attributes */
    public function recordPayment(int $paymentPlanId, User $receivedBy, array $attributes): StudentPayment
    {
        return DB::transaction(function () use ($paymentPlanId, $receivedBy, $attributes): StudentPayment {
            $plan = StudentPaymentPlan::query()
                ->lockForUpdate()
                ->findOrFail($paymentPlanId);

            $amountCents = Money::toCents($attributes['amount']);
            $outstandingCents = Money::toCents($plan->outstanding_balance);

            if ($amountCents <= 0) {
                throw ValidationException::withMessages(['amount' => 'Payment amount must be greater than zero.']);
            }

            if ($amountCents > $outstandingCents) {
                throw ValidationException::withMessages(['amount' => 'Payment cannot exceed the outstanding balance.']);
            }

            $newPaidCents = Money::toCents($plan->total_amount_paid) + $amountCents;
            $newOutstandingCents = $outstandingCents - $amountCents;

            $payment = StudentPayment::create([
                'student_id' => $plan->student_id,
                'payment_plan_id' => $plan->getKey(),
                'receipt_number' => 'TEMP-'.Str::uuid(),
                'amount' => Money::fromCents($amountCents),
                'payment_date' => $attributes['payment_date'],
                'payment_method' => $attributes['payment_method'],
                'reference_number' => $attributes['reference_number'] ?? null,
                'notes' => $attributes['notes'] ?? null,
                'received_by' => $receivedBy->getKey(),
            ]);

            $payment->receipt_number = sprintf('%s-%s-%06d', SystemSetting::current()->receipt_prefix ?: 'DS-RCPT', now()->format('Y'), $payment->getKey());
            $payment->save();

            $plan->total_amount_paid = Money::fromCents($newPaidCents);
            $plan->outstanding_balance = Money::fromCents($newOutstandingCents);
            $plan->payment_status = match (true) {
                $newOutstandingCents === 0 => StudentPaymentPlan::STATUS_FULLY_PAID,
                $newPaidCents > 0 => StudentPaymentPlan::STATUS_PARTIALLY_PAID,
                default => StudentPaymentPlan::STATUS_PENDING,
            };
            $plan->save();

            return $payment;
        });
    }
}
