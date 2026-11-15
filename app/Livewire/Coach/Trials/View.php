<?php

namespace App\Livewire\Coach\Trials;

use App\Models\Trial;
use App\Models\TrialCategoryResult;
use App\Models\TrainingCoachAssignment;
use App\Models\User;
use App\Services\TrialService;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class View extends Component
{
    public Trial $trial;
    public array $resultNotes = [];

    public function mount(Trial $trial): void
    {
        $coach = $this->authorizeCoach();
        $this->trial = $trial;
        abort_if($trial->status === Trial::STATUS_CANCELLED, 404);
        abort_unless($trial->participants()->whereHas('categoryResults.enrollment.coachAssignments', fn (Builder $q) => $q->where('coach_id', $coach->id)->where('status', 'active'))->exists(), 404);
    }

    private function authorizeCoach(): User
    {
        $user = auth()->user();
        abort_unless($user?->hasRole('Coach') && $user->is_active && $user->can('trials.view_assigned'), 403);
        return $user;
    }

    public function recordResult(int $resultId, string $result): void
    {
        $coach = $this->authorizeCoach();
        abort_unless($coach->can('trials.results.record'), 403);
        $assignedIds = TrainingCoachAssignment::where('coach_id', $coach->id)->where('status', 'active')->pluck('student_vehicle_category_id');
        $item = TrialCategoryResult::query()->whereHas('participant', fn (Builder $q) => $q->where('trial_id', $this->trial->getKey()))
            ->whereIn('student_vehicle_category_id', $assignedIds)
            ->findOrFail($resultId);
        app(TrialService::class)->recordResult($item, $coach, $result, $this->resultNotes[$resultId] ?? null);
        session()->flash('success', 'Trial result recorded; result changes are audited.');
    }

    public function render()
    {
        $coach = $this->authorizeCoach();
        $trial = Trial::findOrFail($this->trial->getKey());
        $assignedIds = TrainingCoachAssignment::where('coach_id', $coach->id)->where('status', 'active')->pluck('student_vehicle_category_id');
        $participants = $trial->participants()->whereHas('categoryResults', fn (Builder $q) => $q->whereIn('student_vehicle_category_id', $assignedIds))
            ->with(['student', 'categoryResults' => fn ($q) => $q->whereIn('student_vehicle_category_id', $assignedIds)->with(['enrollment.vehicleCategory', 'recordedBy', 'history.changedBy'])])
            ->get();
        $trial->setRelation('participants', $participants);
        return view('livewire.coach.trials.view', ['trialDetails' => $trial])->layout('layouts.coach');
    }
}
