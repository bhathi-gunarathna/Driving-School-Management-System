<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleDocumentRenewal extends Model
{
    public const TYPES = ['insurance', 'revenue_license'];

    public const EVENT_TYPES = ['renewed', 'document_replaced'];

    protected $fillable = [
        'vehicle_id',
        'document_type',
        'event_type',
        'previous_expiry_date',
        'expiry_date',
        'previous_document_path',
        'document_path',
        'recorded_on',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'previous_expiry_date' => 'date',
            'expiry_date' => 'date',
            'recorded_on' => 'date',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
