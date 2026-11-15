<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrialParticipant extends Model
{
    protected $fillable = ['trial_id', 'student_id', 'status', 'notes'];
    public function trial(): BelongsTo { return $this->belongsTo(Trial::class); }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function categoryResults(): HasMany { return $this->hasMany(TrialCategoryResult::class); }
}
