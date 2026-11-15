<?php

namespace App\Services;

use App\Models\Student;
use App\Models\StudentVehicleCategory;
use App\Models\TrainingCoachAssignment;
use App\Models\Trial;
use App\Models\TrialCategoryResult;
use App\Models\TrialParticipant;
use App\Models\TrialResultHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TrialService
{
    public function create(User $owner, array $attributes): Trial
    {
        return DB::transaction(function () use ($owner, $attributes): Trial {
            $trial = Trial::create([
                ...$attributes,
                'trial_number' => 'TEMP-'.Str::uuid(),
                'status' => Trial::STATUS_SCHEDULED,
                'created_by' => $owner->getKey(),
            ]);
            $trial->trial_number = sprintf('DS-TRIAL-%s-%06d', now()->format('Y'), $trial->getKey());
            $trial->save();
            return $trial;
        });
    }

    /** @param list<int> $enrollmentIds */
    public function assignStudent(Trial $trial, Student $student, array $enrollmentIds, ?string $notes = null): TrialParticipant
    {
        return DB::transaction(function () use ($trial, $student, $enrollmentIds, $notes): TrialParticipant {
            $lockedTrial = Trial::query()->lockForUpdate()->findOrFail($trial->getKey());
            if ($lockedTrial->status !== Trial::STATUS_SCHEDULED || $lockedTrial->trial_date->isPast() && ! $lockedTrial->trial_date->isToday()) {
                throw ValidationException::withMessages(['studentId' => 'Students can only be assigned to a future scheduled trial.']);
            }
            $student = Student::query()->lockForUpdate()->findOrFail($student->getKey());
            if ($student->status !== Student::STATUS_ACTIVE) {
                throw ValidationException::withMessages(['studentId' => 'Only active students can be assigned to a trial.']);
            }
            $ids = collect($enrollmentIds)->map(fn ($id) => (int) $id)->unique()->values();
            $enrollments = StudentVehicleCategory::query()->where('student_id', $student->getKey())
                ->where('status', 'active')->whereIn('id', $ids)->get();
            if ($ids->isEmpty() || $enrollments->count() !== $ids->count()) {
                throw ValidationException::withMessages(['enrollmentIds' => 'Choose one or more active vehicle category enrollments belonging to this student.']);
            }

            $participant = TrialParticipant::query()->where('trial_id', $lockedTrial->getKey())
                ->where('student_id', $student->getKey())->lockForUpdate()->first();
            if (! $participant) {
                $participant = TrialParticipant::create([
                    'trial_id' => $lockedTrial->getKey(), 'student_id' => $student->getKey(),
                    'status' => 'assigned', 'notes' => $notes,
                ]);
            } elseif ($notes !== null) {
                $participant->update(['notes' => $notes]);
            }

            foreach ($enrollments as $enrollment) {
                $exists = TrialCategoryResult::where('trial_participant_id', $participant->getKey())
                    ->where('student_vehicle_category_id', $enrollment->getKey())->exists();
                if ($exists) {
                    throw ValidationException::withMessages(['enrollmentIds' => 'One or more selected category enrollments are already included in this trial.']);
                }
            }

            foreach ($enrollments as $enrollment) {
                TrialCategoryResult::create([
                    'trial_participant_id' => $participant->getKey(),
                    'student_vehicle_category_id' => $enrollment->getKey(),
                    'result' => TrialCategoryResult::RESULT_PENDING,
                ]);
            }

            return $participant;
        });
    }

    public function updateStatus(Trial $trial, string $status): void
    {
        if (! in_array($status, [Trial::STATUS_SCHEDULED, Trial::STATUS_COMPLETED, Trial::STATUS_CANCELLED], true)) {
            throw ValidationException::withMessages(['status' => 'Choose a valid trial status.']);
        }
        DB::transaction(function () use ($trial, $status): void {
            $lockedTrial = Trial::query()->lockForUpdate()->findOrFail($trial->getKey());
            if ($status === Trial::STATUS_COMPLETED && $lockedTrial->trial_date->isFuture()) {
                throw ValidationException::withMessages(['status' => 'A future trial cannot be marked completed.']);
            }
            $lockedTrial->update(['status' => $status]);
        });
    }

    public function recordResult(TrialCategoryResult $categoryResult, User $actor, string $result, ?string $notes = null): void
    {
        DB::transaction(function () use ($categoryResult, $actor, $result, $notes): void {
            $item = TrialCategoryResult::query()->with(['participant.trial', 'enrollment'])
                ->lockForUpdate()->findOrFail($categoryResult->getKey());
            $isOwner = $actor->hasRole('Owner') && $actor->can('trials.results.record');
            $activeAssignment = TrainingCoachAssignment::query()->where('student_vehicle_category_id', $item->student_vehicle_category_id)
                ->where('coach_id', $actor->getKey())->where('status', 'active')->lockForUpdate()->first();
            $isAssignedCoach = $actor->hasRole('Coach') && $actor->is_active && $actor->can('trials.results.record') && $activeAssignment !== null;
            abort_unless($isOwner || $isAssignedCoach, 403);

            $trial = Trial::query()->lockForUpdate()->findOrFail($item->participant->trial_id);
            abort_if($trial->status === Trial::STATUS_CANCELLED, 403, 'Results cannot be recorded for a cancelled trial.');
            abort_if($trial->trial_date->isFuture(), 403, 'Results can only be recorded on or after the trial date.');
            if (! in_array($result, [TrialCategoryResult::RESULT_PASSED, TrialCategoryResult::RESULT_FAILED], true)) {
                throw ValidationException::withMessages(['result' => 'Select Passed or Failed.']);
            }

            $now = now();
            TrialResultHistory::create([
                'trial_category_result_id' => $item->getKey(),
                'previous_result' => $item->result,
                'new_result' => $result,
                'notes' => $notes,
                'changed_by' => $actor->getKey(),
                'recorded_at' => $now,
            ]);
            $item->update(['result' => $result, 'notes' => $notes, 'recorded_by' => $actor->getKey(), 'recorded_at' => $now]);
        });
    }
}
