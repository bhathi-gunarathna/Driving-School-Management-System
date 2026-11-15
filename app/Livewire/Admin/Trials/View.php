<?php

namespace App\Livewire\Admin\Trials;

use App\Models\Student;
use App\Models\StudentVehicleCategory;
use App\Models\Trial;
use App\Models\TrialCategoryResult;
use App\Models\TrialParticipant;
use App\Models\User;
use App\Services\TrainingProgress;
use App\Services\TrialService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class View extends Component
{
    use WithPagination;

    public Trial $trial;
    public string $studentId = '';
    public array $enrollmentIds = [];
    public string $participantSearch = '';
    public string $categoryFilter = '';
    public array $resultNotes = [];

    public function mount(Trial $trial): void
    {
        $user = auth()->user();
        abort_unless($user?->hasRole('Owner') && $user->can('trials.view'), 403);
        $this->trial = $trial;
    }

    public function updatedStudentId(): void { $this->enrollmentIds = []; }
    public function updatedParticipantSearch(): void { $this->resetPage('participantsPage'); }
    public function updatedCategoryFilter(): void { $this->resetPage('participantsPage'); }

    public function addParticipant(): void
    {
        $owner = auth()->user();
        abort_unless($owner?->hasRole('Owner') && $owner->can('trials.manage'), 403);
        $data = $this->validate([
            'studentId' => ['required', 'integer', Rule::exists('students', 'id')->where('status', Student::STATUS_ACTIVE)],
            'enrollmentIds' => ['required', 'array', 'min:1'],
            'enrollmentIds.*' => ['required', 'integer', 'distinct'],
        ]);
        $student = Student::findOrFail($data['studentId']);
        app(TrialService::class)->assignStudent($this->trial, $student, $data['enrollmentIds']);
        $this->trial->refresh();
        $this->studentId = '';
        $this->enrollmentIds = [];
        session()->flash('success', 'Student and selected vehicle categories added to the trial.');
    }

    public function updateTrialStatus(string $status): void
    {
        $owner = auth()->user();
        abort_unless($owner?->hasRole('Owner') && $owner->can('trials.manage'), 403);
        app(TrialService::class)->updateStatus($this->trial, $status);
        $this->trial->refresh();
        session()->flash('success', 'Trial status updated.');
    }

    public function recordResult(int $resultId, string $result): void
    {
        $owner = auth()->user();
        abort_unless($owner?->hasRole('Owner') && $owner->can('trials.results.record'), 403);
        $item = TrialCategoryResult::query()->whereHas('participant', fn (Builder $q) => $q->where('trial_id', $this->trial->getKey()))->findOrFail($resultId);
        app(TrialService::class)->recordResult($item, $owner, $result, $this->resultNotes[$resultId] ?? null);
        session()->flash('success', 'Trial result recorded. Previous result states are retained in the audit history.');
    }

    public function render()
    {
        $user = auth()->user();
        abort_unless($user?->hasRole('Owner') && $user->can('trials.view'), 403);
        $participantsQuery = TrialParticipant::query()->where('trial_id', $this->trial->getKey())
            ->with(['student', 'categoryResults' => fn ($q) => $q
                ->when($this->categoryFilter !== '', fn (Builder $results) => $results->whereHas('enrollment', fn (Builder $enrollment) => $enrollment->where('vehicle_category_id', $this->categoryFilter)))
                ->with(['enrollment.vehicleCategory', 'recordedBy', 'history.changedBy'])])
            ->when(trim($this->participantSearch) !== '', fn (Builder $q) => $q->whereHas('student', fn (Builder $s) => $s->where('full_name', 'like', '%'.trim($this->participantSearch).'%')->orWhere('student_number', 'like', '%'.trim($this->participantSearch).'%')))
            ->when($this->categoryFilter !== '', fn (Builder $q) => $q->whereHas('categoryResults.enrollment', fn (Builder $r) => $r->where('vehicle_category_id', $this->categoryFilter)))
            ->orderBy('id');
        $participants = $participantsQuery->paginate(15, ['*'], 'participantsPage');

        $passedEnrollmentIds = $this->passedEnrollmentIds();
        $selectedEnrollments = $this->studentId === '' ? collect() : StudentVehicleCategory::query()
            ->with(['vehicleCategory', 'trainingSessions' => fn ($q) => $q->where('status', 'completed')])
            ->where('student_id', $this->studentId)->where('status', 'active')->whereNotIn('id', $passedEnrollmentIds)->get()
            ->map(function ($enrollment) {
                $enrollment->setAttribute('completed_minutes', $enrollment->trainingSessions->sum('duration_minutes'));
                $enrollment->progress = TrainingProgress::forEnrollment($enrollment);
                return $enrollment;
            });
        $assignedEnrollmentIds = TrialCategoryResult::query()->whereHas('participant', fn (Builder $q) => $q->where('trial_id', $this->trial->getKey()))
            ->pluck('student_vehicle_category_id')->all();

        return view('livewire.admin.trials.view', [
            'participants' => $participants,
            'students' => Student::query()->where('status', Student::STATUS_ACTIVE)->whereHas('enrollments', fn (Builder $q) => $q->where('status', 'active')->whereNotIn('id', $passedEnrollmentIds))->orderBy('full_name')->get(),
            'selectedEnrollments' => $selectedEnrollments,
            'categories' => \App\Models\VehicleCategory::orderBy('category_code')->get(),
            'assignedEnrollmentIds' => $assignedEnrollmentIds,
        ])->layout('layouts.admin');
    }

    /** @return list<int> Active enrollments whose current result in a prior trial is Passed. */
    private function passedEnrollmentIds(): array
    {
        return TrialCategoryResult::query()->where('result', TrialCategoryResult::RESULT_PASSED)
            ->whereHas('participant', fn (Builder $participant) => $participant->whereHas('trial', fn (Builder $trial) => $trial
                ->where('id', '!=', $this->trial->getKey())
                ->whereDate('trial_date', '<=', $this->trial->trial_date->toDateString())))
            ->pluck('student_vehicle_category_id')->map(fn ($id) => (int) $id)->unique()->values()->all();
    }
}
