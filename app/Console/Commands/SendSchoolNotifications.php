<?php

namespace App\Console\Commands;

use App\Models\StudentPaymentPlan;
use App\Models\SystemSetting;
use App\Models\Trial;
use App\Services\SchoolNotificationService;
use App\Services\TrainingProgress;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class SendSchoolNotifications extends Command
{
    protected $signature = 'school:send-notifications';
    protected $description = 'Send due in-app notifications for trials, training, and payment follow-up.';

    public function handle(SchoolNotificationService $notifications): int
    {
        $configuredTimezone = SystemSetting::query()->value('timezone');
        if (is_string($configuredTimezone) && in_array($configuredTimezone, timezone_identifiers_list(), true)) {
            config(['app.timezone' => $configuredTimezone]);
            date_default_timezone_set($configuredTimezone);
        }
        $now = CarbonImmutable::now(config('app.timezone', 'UTC'));
        if ($now->format('H:i') !== '08:00') {
            return self::SUCCESS;
        }
        $today = $now->startOfDay();
        $through = $today->addDays(7)->toDateString();
        $trials = Trial::upcoming()->whereDate('trial_date', '<=', $through)
            ->with(['participants.student', 'participants.categoryResults.enrollment.vehicleCategory', 'participants.categoryResults.enrollment.trainingSessions' => fn ($query) => $query->where('status', 'completed')])
            ->get();

        foreach ($trials as $trial) {
            foreach ($notifications->owners() as $owner) {
                $notifications->sendOnce($owner, 'trial-upcoming:'.$trial->getKey(), 'Upcoming trial',
                    $trial->trial_number.' is scheduled for '.$trial->trial_date->format('d M Y').'.', route('admin.trials.view', $trial), 'trial');
            }

            foreach ($trial->participants as $participant) {
                foreach ($participant->categoryResults as $result) {
                    $enrollment = $result->enrollment;
                    if (! $enrollment) continue;
                    $enrollment->setAttribute('completed_minutes', $enrollment->trainingSessions->sum('duration_minutes'));
                    $progress = TrainingProgress::forEnrollment($enrollment);
                    if ($progress['remaining_hours'] <= 0) continue;
                    $message = $participant->student->full_name.' has '.number_format($progress['remaining_hours'], 2).' training hours remaining before '.$trial->trial_number.'.';
                    foreach ($notifications->owners() as $owner) {
                        $notifications->sendOnce($owner, 'trial-training:'.$trial->getKey().':'.$enrollment->getKey(), 'Training hours incomplete', $message, route('admin.trials.view', $trial), 'training');
                    }
                    foreach ($notifications->coachRecipientsForEnrollment((int) $enrollment->getKey()) as $coach) {
                        $notifications->sendOnce($coach, 'trial-upcoming:'.$trial->getKey(), 'Upcoming relevant trial',
                            $trial->trial_number.' is scheduled for '.$trial->trial_date->format('d M Y').'.', route('coach.trials.view', $trial), 'trial');
                        $notifications->sendOnce($coach, 'trial-training:'.$trial->getKey().':'.$enrollment->getKey(), 'Training hours incomplete', $message, route('coach.trials.view', $trial), 'training');
                    }
                }
            }
        }

        $weekKey = $today->startOfWeek()->toDateString();
        foreach ($notifications->paymentPlansForFollowUp() as $plan) {
            foreach ($notifications->owners() as $owner) {
                $notifications->sendOnce($owner, 'payment-followup:'.$plan->getKey().':'.$weekKey, 'Payment follow-up',
                    $plan->student->full_name.' has an outstanding course balance that has not been updated in at least seven days.',
                    route('admin.finance.outstanding'), 'payment');
            }
        }

        $this->info('School notifications processed.');
        return self::SUCCESS;
    }
}
