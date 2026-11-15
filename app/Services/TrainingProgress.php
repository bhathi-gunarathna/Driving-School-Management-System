<?php

namespace App\Services;

use App\Models\StudentVehicleCategory;

class TrainingProgress
{
    /** @return array{required_hours: float, completed_hours: float, remaining_hours: float, percentage: float, status: string, completed_minutes: int} */
    public static function forEnrollment(StudentVehicleCategory $enrollment): array
    {
        $hours = (string) $enrollment->required_training_hours;
        [$whole, $fraction] = array_pad(explode('.', $hours, 2), 2, '0');
        $requiredHoursHundredths = ((int) $whole * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0');
        $requiredMinutesHundredths = $requiredHoursHundredths * 60;
        $completedMinutes = (int) ($enrollment->completed_minutes ?? $enrollment->training_sessions_sum_duration_minutes ?? 0);
        $completedMinutesHundredths = $completedMinutes * 100;
        $remainingMinutesHundredths = max(0, $requiredMinutesHundredths - $completedMinutesHundredths);
        $percentage = $requiredMinutesHundredths === 0
            ? 100.0
            : round(($completedMinutesHundredths / $requiredMinutesHundredths) * 100, 1);

        return [
            'required_hours' => $requiredHoursHundredths / 100,
            'completed_hours' => $completedMinutes / 60,
            'remaining_hours' => $remainingMinutesHundredths / 6000,
            'percentage' => $percentage,
            'status' => $completedMinutesHundredths > $requiredMinutesHundredths ? 'over_hours'
                : ($remainingMinutesHundredths === 0 ? 'completed' : ($completedMinutes > 0 ? 'in_progress' : 'not_started')),
            'completed_minutes' => $completedMinutes,
        ];
    }
}
