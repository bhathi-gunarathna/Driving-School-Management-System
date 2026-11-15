<?php

namespace App\Livewire\Coach\Trials;

use App\Models\Trial;
use App\Models\TrainingCoachAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class Index extends Component
{
    public function mount(): void { $this->authorizeCoach(); }
    private function authorizeCoach(): User
    {
        $user = auth()->user();
        abort_unless($user?->hasRole('Coach') && $user->is_active && $user->can('trials.view_assigned'), 403);
        return $user;
    }

    public function render()
    {
        $coach = $this->authorizeCoach();
        $assignedIds = TrainingCoachAssignment::where('coach_id', $coach->id)->where('status', 'active')->pluck('student_vehicle_category_id');
        $trials = Trial::query()->where(function (Builder $q) use ($coach): void {
            $q->upcoming()->orWhere(function (Builder $past) use ($coach): void {
                $past->where('status', '!=', Trial::STATUS_CANCELLED)->whereDate('trial_date', '<', today())
                    ->whereHas('participants.categoryResults', fn (Builder $result) => $result->where('result', 'pending')
                        ->whereHas('enrollment.coachAssignments', fn (Builder $assignment) => $assignment->where('coach_id', $coach->id)->where('status', 'active')));
            });
        })->whereHas('participants.categoryResults', fn (Builder $q) => $q->whereIn('student_vehicle_category_id', $assignedIds))
            ->with(['participants.student', 'participants.categoryResults' => fn ($q) => $q->whereIn('student_vehicle_category_id', $assignedIds)->with(['enrollment.vehicleCategory', 'recordedBy'])])
            ->orderBy('trial_date')->get()->map(function ($trial) {
                $trial->participants = $trial->participants->filter(fn ($participant) => $participant->categoryResults->isNotEmpty())->values();
                return $trial;
            });
        return view('livewire.coach.trials.index', ['trials' => $trials])->layout('layouts.coach');
    }
}
