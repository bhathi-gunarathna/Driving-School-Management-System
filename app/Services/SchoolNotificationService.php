<?php

namespace App\Services;

use App\Models\StudentPaymentPlan;
use App\Models\TrainingCoachAssignment;
use App\Models\TrialCategoryResult;
use App\Models\User;
use App\Notifications\SchoolNotification;
use Illuminate\Support\Facades\DB;
use Throwable;

class SchoolNotificationService
{
    public function sendOnce(User $recipient, string $key, string $title, string $message, string $url, string $category): void
    {
        try {
            DB::transaction(function () use ($recipient, $key, $title, $message, $url, $category): void {
                $inserted = DB::table('notification_dispatches')->insertOrIgnore([
                    'notifiable_type' => $recipient->getMorphClass(), 'notifiable_id' => $recipient->getKey(),
                    'event_key' => $key, 'created_at' => now(),
                ]);
                if ($inserted === 1) {
                    $recipient->notify(new SchoolNotification($key, $title, $message, $url, $category));
                }
            });
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    public function owners(): \Illuminate\Support\Collection { return User::role('Owner')->get(); }

    public function coachRecipientsForEnrollment(int $enrollmentId): \Illuminate\Support\Collection
    {
        return TrainingCoachAssignment::query()->where('student_vehicle_category_id', $enrollmentId)->where('status', 'active')
            ->with('coach')->get()->pluck('coach')->filter(fn ($coach) => $coach?->is_active && $coach->hasRole('Coach'))->unique('id')->values();
    }

    public function notifyAssignment(TrainingCoachAssignment $assignment): void
    {
        $assignment->loadMissing('coach', 'enrollment.student', 'enrollment.vehicleCategory');
        $coach = $assignment->coach;
        if (! $coach?->is_active) return;
        $enrollment = $assignment->enrollment;
        $this->sendOnce($coach, 'assignment:'.$assignment->getKey(), 'New student assignment',
            $enrollment->student->full_name.' was assigned in '.$enrollment->vehicleCategory->category_name.'.',
            route('coach.training.index'), 'assignment');
    }

    public function notifyTrialResult(TrialCategoryResult $result, User $actor): void
    {
        $result->loadMissing('participant.trial', 'participant.student', 'enrollment.vehicleCategory');
        foreach ($this->coachRecipientsForEnrollment((int) $result->student_vehicle_category_id) as $coach) {
            if ($coach->is($actor)) continue;
            $this->sendOnce($coach, 'trial-result:'.$result->getKey().':'.($result->updated_at?->timestamp ?? now()->timestamp), 'Trial result updated',
                'A trial result for '.$result->participant->student->full_name.' in '.$result->enrollment->vehicleCategory->category_name.' has been updated.',
                route('coach.trials.view', $result->participant->trial), 'trial-result');
        }
    }

    public function paymentPlansForFollowUp(): \Illuminate\Database\Eloquent\Collection
    {
        return StudentPaymentPlan::query()->where('outstanding_balance', '>', 0)
            ->whereDoesntHave('student.payments', fn ($query) => $query->whereDate('payment_date', '>=', now()->subDays(7)->toDateString()))
            ->with('student')->get();
    }
}
