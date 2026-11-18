<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehicleCategory extends Model
{
    protected $fillable = [
        'category_code',
        'category_name',
        'description',
        'training_hours',
        'price',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'training_hours' => 'decimal:2',
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'student_vehicle_categories')
            ->using(StudentVehicleCategory::class)
            ->withPivot(['id', 'agreed_fee', 'required_training_hours', 'status'])
            ->withTimestamps();
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentVehicleCategory::class, 'vehicle_category_id');
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }
}
