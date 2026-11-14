<?php

namespace App\Livewire\Admin\Students;

use App\Models\Student;
use Livewire\Component;

class View extends Component
{
    public Student $student;

    public function mount(Student $student): void
    {
        $user = auth()->user();

        abort_unless($user?->hasRole('Owner') && $user->can('students.view_all'), 403);

        $this->student = Student::with('vehicleCategories')->findOrFail($student->getKey());
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
}
