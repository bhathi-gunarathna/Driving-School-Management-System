<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Student extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'student_number',
        'full_name',
        'address',
        'nic_number',
        'date_of_birth',
        'contact_number',
        'medical_report_path',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
        ];
    }

    public function vehicleCategories(): BelongsToMany
    {
        return $this->belongsToMany(VehicleCategory::class, 'student_vehicle_categories')
            ->withPivot(['id', 'agreed_fee', 'required_training_hours', 'status'])
            ->withTimestamps();
    }

    public function paymentPlan(): HasOne
    {
        return $this->hasOne(StudentPaymentPlan::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(StudentPayment::class)->orderByDesc('id');
    }
}
