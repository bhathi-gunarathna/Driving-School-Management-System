<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingSession extends Model
{
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = ['student_vehicle_category_id', 'coach_id', 'coach_assignment_id', 'training_date', 'start_time', 'end_time', 'duration_minutes', 'notes', 'status'];

    protected function casts(): array
    {
        return ['training_date' => 'date', 'duration_minutes' => 'integer'];
    }

    public function enrollment(): BelongsTo { return $this->belongsTo(StudentVehicleCategory::class, 'student_vehicle_category_id'); }
    public function coach(): BelongsTo { return $this->belongsTo(User::class, 'coach_id'); }
    public function coachAssignment(): BelongsTo { return $this->belongsTo(TrainingCoachAssignment::class, 'coach_assignment_id'); }
}
