<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trial extends Model
{
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = ['trial_number', 'trial_date', 'reporting_time', 'location', 'notes', 'status', 'created_by'];

    protected function casts(): array { return ['trial_date' => 'date']; }
    public function participants(): HasMany { return $this->hasMany(TrialParticipant::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function scopeUpcoming(Builder $query): Builder { return $query->where('status', self::STATUS_SCHEDULED)->whereDate('trial_date', '>=', today()); }
}
