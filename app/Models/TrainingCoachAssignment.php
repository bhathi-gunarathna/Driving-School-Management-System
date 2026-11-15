<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingCoachAssignment extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = ['student_vehicle_category_id', 'coach_id', 'assigned_by', 'status', 'assigned_at', 'deactivated_at'];

    protected function casts(): array
    {
        return ['assigned_at' => 'datetime', 'deactivated_at' => 'datetime'];
    }

    public function enrollment(): BelongsTo { return $this->belongsTo(StudentVehicleCategory::class, 'student_vehicle_category_id'); }
    public function coach(): BelongsTo { return $this->belongsTo(User::class, 'coach_id'); }
    public function assignedBy(): BelongsTo { return $this->belongsTo(User::class, 'assigned_by'); }
    public function trainingSessions(): HasMany { return $this->hasMany(TrainingSession::class, 'coach_assignment_id'); }
}
