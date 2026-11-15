<?php

namespace App\Livewire\Coach\Training;

use App\Models\StudentVehicleCategory;
use App\Models\User;
use App\Models\Trial;
use App\Models\TrainingCoachAssignment;
use App\Models\TrainingSession;
use App\Services\TrainingProgress;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class Dashboard extends Component
{
    public function mount(): void { $this->authorizeCoach(); }
    private function authorizeCoach(): User
    {
        $user = auth()->user();
        abort_unless($user?->hasRole('Coach') && $user->is_active && $user->can('training.view_assigned'), 403);
        return $user;
    }

    public function render()
    {
        $coach = $this->authorizeCoach();
        $enrollments = StudentVehicleCategory::query()->where('status', 'active')
            ->whereHas('student', fn (Builder $q) => $q->where('status', 'active'))
            ->whereHas('coachAssignments', fn (Builder $q) => $q->where('coach_id', $coach->id)->where('status', 'active'))
            ->withSum(['trainingSessions as completed_minutes' => fn (Builder $q) => $q->where('status', 'completed')], 'duration_minutes')->get();
        $metrics = $enrollments->map(fn ($enrollment) => TrainingProgress::forEnrollment($enrollment));
        $assignedEnrollmentIds = TrainingCoachAssignment::where('coach_id', $coach->id)->where('status', 'active')->pluck('student_vehicle_category_id');
        $upcomingTrials = $coach->can('trials.view_assigned') && $assignedEnrollmentIds->isNotEmpty() ? Trial::upcoming()
            ->whereHas('participants.categoryResults', fn (Builder $q) => $q->whereIn('student_vehicle_category_id', $assignedEnrollmentIds))
            ->with(['participants.student', 'participants.categoryResults' => fn ($q) => $q->whereIn('student_vehicle_category_id', $assignedEnrollmentIds)->with('enrollment.vehicleCategory')])
            ->orderBy('trial_date')->limit(3)->get() : collect();
        $upcomingTrials->each(function ($trial): void {
            $trial->participants = $trial->participants->filter(fn ($participant) => $participant->categoryResults->isNotEmpty())->values();
        });
        $recentTraining = TrainingSession::query()->where('coach_id', $coach->getKey())->where('status', 'completed')
            ->whereHas('enrollment', fn (Builder $q) => $q->where('status', 'active')->whereHas('student', fn (Builder $student) => $student->where('status', 'active'))
                ->whereHas('coachAssignments', fn (Builder $assignment) => $assignment->where('coach_id', $coach->getKey())->where('status', 'active')))
            ->with(['enrollment.student', 'enrollment.vehicleCategory'])->latest('training_date')->limit(6)->get();
        $sessionCount = TrainingSession::query()->where('coach_id', $coach->getKey())->where('status', 'completed')
            ->whereHas('enrollment', fn (Builder $q) => $q->where('status', 'active')->whereHas('student', fn (Builder $student) => $student->where('status', 'active'))
                ->whereHas('coachAssignments', fn (Builder $assignment) => $assignment->where('coach_id', $coach->getKey())->where('status', 'active')))->count();
        return view('livewire.coach.training.dashboard', [
            'assignedStudents' => $enrollments->pluck('student_id')->unique()->count(),
            'assignedCategories' => $enrollments->pluck('vehicle_category_id')->unique()->count(),
            'sessionCount' => $sessionCount,
            'underway' => $metrics->where('status', 'in_progress')->count(),
            'completedHours' => number_format($metrics->sum('completed_hours'), 2),
            'pendingHours' => number_format($metrics->sum('remaining_hours'), 2),
            'upcomingTrials' => $upcomingTrials,
            'recentTraining' => $recentTraining,
        ])->layout('layouts.coach');
    }
}
