<?php

namespace App\Livewire\Admin\Students;

use App\Models\Student;
use App\Models\StudentPaymentPlan;
use App\Services\StudentPaymentService;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class View extends Component
{
    public Student $student;

    public string $newPaymentPlanType = StudentPaymentPlan::TYPE_FULL;

    public function mount(Student $student): void
    {
        $user = auth()->user();

        abort_unless($user?->hasRole('Owner') && $user->can('students.view_all'), 403);

        $this->student = Student::with([
            'vehicleCategories',
            'paymentPlan.installments',
            'payments.receivedBy',
        ])->findOrFail($student->getKey());
    }

    public function render()
    {
        $activeEnrollments = $this->student->vehicleCategories
            ->filter(fn ($category) => $category->pivot->status === 'active');

        $totalFeeCents = $activeEnrollments->sum(fn ($category) => (int) round((float) $category->pivot->agreed_fee * 100));
        $totalTrainingHundredths = $activeEnrollments->sum(fn ($category) => (int) round((float) $category->pivot->required_training_hours * 100));

        return view('livewire.admin.students.view', [
            'activeEnrollments' => $activeEnrollments,
            'totalAgreedFee' => number_format($totalFeeCents / 100, 2),
            'totalTrainingHours' => number_format($totalTrainingHundredths / 100, 2),
        ])->layout('layouts.admin');
    }

    public function createPaymentPlan(): void
    {
        $user = auth()->user();
        abort_unless($user?->hasRole('Owner') && $user->can('payments.manage'), 403);
        $this->validate(['newPaymentPlanType' => ['required', Rule::in([
            StudentPaymentPlan::TYPE_FULL,
            StudentPaymentPlan::TYPE_TWO_INSTALLMENTS,
            StudentPaymentPlan::TYPE_THREE_INSTALLMENTS,
        ])]]);

        DB::transaction(function (): void {
            $student = Student::query()->lockForUpdate()->findOrFail($this->student->getKey());
            if ($student->paymentPlan()->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'paymentPlan' => 'A payment plan already exists for this student.',
                ]);
            }
            $feeCents = $student->vehicleCategories()
                ->wherePivot('status', 'active')
                ->get()->sum(fn ($category) => Money::toCents((string) $category->pivot->agreed_fee));
            app(StudentPaymentService::class)->createPlan($student, $feeCents, $this->newPaymentPlanType);
        });

        $this->student->load(['paymentPlan.installments', 'payments.receivedBy']);
        session()->flash('success', 'Payment plan created from the student’s saved enrollment fees.');
    }
}
