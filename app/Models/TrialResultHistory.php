<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrialResultHistory extends Model
{
    protected $fillable = ['trial_category_result_id', 'previous_result', 'new_result', 'notes', 'changed_by', 'recorded_at'];
    protected function casts(): array { return ['recorded_at' => 'datetime']; }
    public function categoryResult(): BelongsTo { return $this->belongsTo(TrialCategoryResult::class, 'trial_category_result_id'); }
    public function changedBy(): BelongsTo { return $this->belongsTo(User::class, 'changed_by'); }
}
