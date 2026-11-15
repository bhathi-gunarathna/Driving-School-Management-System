<?php

namespace App\Services;

use App\Models\StudentVehicleCategory;
use App\Models\TrainingCoachAssignment;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TrainingService
{
    public function assignCoach(int $enrollmentId, User $coach, User $owner): TrainingCoachAssignment
    {
        return DB::transaction(function () use ($enrollmentId, $coach, $owner): TrainingCoachAssignment {
            $enrollment = StudentVehicleCategory::query()->with('student')->lockForUpdate()->findOrFail($enrollmentId);
            abort_unless($coach->hasRole('Coach') && $coach->is_active, 422, 'Only active Coach accounts can be assigned.');
            abort_unless($enrollment->status === 'active' && $enrollment->student->status === 'active', 422, 'Only active student enrollments can receive a coach assignment.');

            $existing = TrainingCoachAssignment::query()->where('student_vehicle_category_id', $enrollmentId)
                ->where('coach_id', $coach->getKey())->where('status', TrainingCoachAssignment::STATUS_ACTIVE)
                ->lockForUpdate()->first();
            if ($existing) {
                throw ValidationException::withMessages(['coachId' => 'This coach is already assigned to this category enrollment.']);
            }

            $assignment = TrainingCoachAssignment::create([
                'student_vehicle_category_id' => $enrollmentId,
                'coach_id' => $coach->getKey(),
                'assigned_by' => $owner->getKey(),
                'status' => TrainingCoachAssignment::STATUS_ACTIVE,
                'assigned_at' => now(),
            ]);
            app(SchoolNotificationService::class)->notifyAssignment($assignment);
            return $assignment;
        });
    }

    public function deactivateAssignment(int $assignmentId): void
    {
        DB::transaction(function () use ($assignmentId): void {
            $assignment = TrainingCoachAssignment::query()->lockForUpdate()->findOrFail($assignmentId);
            if ($assignment->status === TrainingCoachAssignment::STATUS_ACTIVE) {
                $assignment->update(['status' => TrainingCoachAssignment::STATUS_INACTIVE, 'deactivated_at' => now()]);
            }
        });
    }

    public function recordSession(int $enrollmentId, User $coach, array $attributes): TrainingSession
    {
        return DB::transaction(function () use ($enrollmentId, $coach, $attributes): TrainingSession {
            $enrollment = StudentVehicleCategory::query()->with('student')->lockForUpdate()->findOrFail($enrollmentId);
            abort_unless($coach->hasRole('Coach') && $coach->is_active, 403);
            abort_unless($enrollment->status === 'active' && $enrollment->student->status === 'active', 403, 'Training is available only for active students and enrollments.');
            $assignment = TrainingCoachAssignment::query()->where('student_vehicle_category_id', $enrollmentId)
                ->where('coach_id', $coach->getKey())->where('status', TrainingCoachAssignment::STATUS_ACTIVE)
                ->lockForUpdate()->firstOrFail();

            $duration = (int) $attributes['duration_minutes'];
            if ($duration < 1) {
                throw ValidationException::withMessages(['duration_minutes' => 'Training duration must be greater than zero.']);
            }

            return TrainingSession::create([
                'student_vehicle_category_id' => $enrollmentId,
                'coach_id' => $coach->getKey(),
                'coach_assignment_id' => $assignment->getKey(),
                'training_date' => $attributes['training_date'],
                'start_time' => $attributes['start_time'] ?: null,
                'end_time' => $attributes['end_time'] ?: null,
                'duration_minutes' => $duration,
                'notes' => $attributes['notes'] ?: null,
                'status' => TrainingSession::STATUS_COMPLETED,
            ]);
        });
    }
}
