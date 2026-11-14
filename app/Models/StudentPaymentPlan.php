<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentPaymentPlan extends Model
{
    public const TYPE_FULL = 'full_payment';

    public const TYPE_TWO_INSTALLMENTS = 'two_installments';

    public const TYPE_THREE_INSTALLMENTS = 'three_installments';

    public const STATUS_PENDING = 'pending';

    public const STATUS_PARTIALLY_PAID = 'partially_paid';

    public const STATUS_FULLY_PAID = 'fully_paid';

    protected $fillable = [
        'student_id',
        'total_agreed_fee',
        'plan_type',
        'number_of_installments',
        'total_amount_paid',
        'outstanding_balance',
        'payment_status',
    ];

    protected function casts(): array
    {
        return [
            'total_agreed_fee' => 'decimal:2',
            'total_amount_paid' => 'decimal:2',
            'outstanding_balance' => 'decimal:2',
        ];
    }

    public static function installmentCount(string $planType): int
    {
        return match ($planType) {
            self::TYPE_FULL => 1,
            self::TYPE_TWO_INSTALLMENTS => 2,
            self::TYPE_THREE_INSTALLMENTS => 3,
            default => throw new \InvalidArgumentException('Unsupported payment plan type.'),
        };
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function installments(): HasMany
    {
        return $this->hasMany(StudentPaymentInstallment::class, 'payment_plan_id')->orderBy('installment_number');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(StudentPayment::class, 'payment_plan_id');
    }
}
