<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrialCategoryResult extends Model
{
    public const RESULT_PENDING = 'pending';
    public const RESULT_PASSED = 'passed';
    public const RESULT_FAILED = 'failed';

    protected $fillable = ['trial_participant_id', 'student_vehicle_category_id', 'result', 'notes', 'recorded_by', 'recorded_at'];
    protected function casts(): array { return ['recorded_at' => 'datetime']; }
    public function participant(): BelongsTo { return $this->belongsTo(TrialParticipant::class, 'trial_participant_id'); }
    public function enrollment(): BelongsTo { return $this->belongsTo(StudentVehicleCategory::class, 'student_vehicle_category_id'); }
    public function recordedBy(): BelongsTo { return $this->belongsTo(User::class, 'recorded_by'); }
    public function history(): HasMany { return $this->hasMany(TrialResultHistory::class, 'trial_category_result_id')->orderByDesc('id'); }
}
