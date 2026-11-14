<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentPaymentInstallment extends Model
{
    protected $fillable = [
        'payment_plan_id',
        'installment_number',
        'planned_amount',
    ];

    protected function casts(): array
    {
        return [
            'planned_amount' => 'decimal:2',
        ];
    }

    public function paymentPlan(): BelongsTo
    {
        return $this->belongsTo(StudentPaymentPlan::class, 'payment_plan_id');
    }
}
