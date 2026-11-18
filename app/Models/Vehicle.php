<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    public const STATUSES = ['active', 'maintenance', 'inactive'];

    public const FUEL_TYPES = ['petrol', 'diesel', 'hybrid', 'electric', 'other'];

    public const TRANSMISSION_TYPES = ['manual', 'automatic', 'other'];

    protected $fillable = [
        'registration_number',
        'chassis_number',
        'engine_number',
        'vehicle_category_id',
        'brand',
        'model',
        'manufacturing_year',
        'fuel_type',
        'transmission_type',
        'current_mileage',
        'purchase_date',
        'status',
        'notes',
        'insurance_expiry_date',
        'insurance_document_path',
        'revenue_license_expiry_date',
        'revenue_license_document_path',
    ];

    protected function casts(): array
    {
        return [
            'manufacturing_year' => 'integer',
            'current_mileage' => 'decimal:1',
            'purchase_date' => 'date',
            'insurance_expiry_date' => 'date',
            'revenue_license_expiry_date' => 'date',
        ];
    }

    public function vehicleCategory(): BelongsTo
    {
        return $this->belongsTo(VehicleCategory::class);
    }

    public function documentRenewals(): HasMany
    {
        return $this->hasMany(VehicleDocumentRenewal::class)->latest('recorded_on')->latest('id');
    }
}
