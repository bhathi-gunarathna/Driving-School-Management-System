<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\Student;
use App\Models\StudentPayment;
use App\Models\StudentVehicleCategory;
use App\Models\SystemSetting;
use App\Models\TrainingCoachAssignment;
use App\Models\TrainingSession;
use App\Models\Trial;
use App\Models\TrialCategoryResult;
use App\Models\TrialParticipant;
use App\Models\TrialResultHistory;
use App\Models\User;
use App\Models\VehicleCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Throwable;

class AuditModelObserver
{
    private const MODULES = [
        Student::class => ['students', 'student'],
        User::class => ['coaches', 'coach'],
        VehicleCategory::class => ['vehicle-categories', 'vehicle category'],
        StudentVehicleCategory::class => ['student-enrollments', 'student category enrollment'],
        StudentPayment::class => ['payments', 'payment receipt'],
        TrainingCoachAssignment::class => ['training', 'coach assignment'],
        TrainingSession::class => ['training', 'training session'],
        Trial::class => ['trials', 'trial'],
        TrialParticipant::class => ['trials', 'trial participant'],
        TrialCategoryResult::class => ['trials', 'trial result'],
        TrialResultHistory::class => ['trials', 'trial result history'],
        SystemSetting::class => ['settings', 'system settings'],
    ];

    public function created(Model $model): void
    {
        if (! $this->shouldTrack($model)) {
            return;
        }
        $this->write($model, 'created', null, $this->safeValues($model->getAttributes()));
    }

    public function updated(Model $model): void
    {
        if (! $this->shouldTrack($model)) {
            return;
        }
        $changes = $model->getChanges();
        if ($model instanceof StudentPayment && array_diff(array_keys($changes), ['receipt_number', 'updated_at']) === []) {
            return;
        }
        $before = [];
        $after = [];
        foreach (array_keys($changes) as $key) {
            if ($this->sensitive($key) || in_array($key, ['created_at', 'updated_at'], true)) {
                continue;
            }
            $before[$key] = $model->getRawOriginal($key);
            $after[$key] = $changes[$key];
        }
        if ($after !== []) {
            $this->write($model, 'updated', $before, $after);
        }
    }

    private function shouldTrack(Model $model): bool
    {
        if (! isset(self::MODULES[$model::class])) {
            return false;
        }
        return ! $model instanceof User || $model->hasRole('Coach');
    }

    private function safeValues(array $values): array
    {
        return collect($values)->reject(fn ($value, $key) => $this->sensitive((string) $key)
            || in_array($key, ['created_at', 'updated_at'], true)
            || (is_string($value) && str_starts_with($value, 'TEMP-')))->all();
    }

    private function sensitive(string $key): bool
    {
        return preg_match('/password|secret|token|profile_photo|medical_report|remember|two_factor|passkey/i', $key) === 1;
    }

    private function write(Model $model, string $action, ?array $before, ?array $after): void
    {
        try {
            [$module, $label] = self::MODULES[$model::class];
            $actor = auth()->user();
            AuditLog::create([
                'user_id' => $actor?->getAuthIdentifier(),
                'user_name' => $actor?->name ?? 'System',
                'action' => $action,
                'module' => $module,
                'description' => ucfirst($label).' '.$action,
                'related_type' => class_basename($model),
                'related_id' => $model->getKey(),
                'previous_values' => $before,
                'new_values' => $after,
                'ip_address' => app()->runningInConsole() ? null : request()->ip(),
                'created_at' => now(),
            ]);
        } catch (Throwable $exception) {
            Log::warning('Audit event could not be recorded.', ['model' => $model::class, 'action' => $action, 'exception' => $exception::class]);
        }
    }
}
