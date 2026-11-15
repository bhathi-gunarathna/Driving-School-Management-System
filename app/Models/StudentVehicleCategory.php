<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

class StudentVehicleCategory extends Pivot
{
    protected $table = 'student_vehicle_categories';
    public $incrementing = true;

    protected $fillable = ['student_id', 'vehicle_category_id', 'agreed_fee', 'required_training_hours', 'status'];

    protected function casts(): array
    {
        return ['agreed_fee' => 'decimal:2', 'required_training_hours' => 'decimal:2'];
    }

    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function vehicleCategory(): BelongsTo { return $this->belongsTo(VehicleCategory::class); }
    public function coachAssignments(): HasMany { return $this->hasMany(TrainingCoachAssignment::class, 'student_vehicle_category_id'); }
    public function trainingSessions(): HasMany { return $this->hasMany(TrainingSession::class, 'student_vehicle_category_id'); }
}
