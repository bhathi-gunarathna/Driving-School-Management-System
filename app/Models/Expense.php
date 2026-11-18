<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    public const PAYMENT_METHODS = ['cash', 'bank_transfer', 'card', 'cheque', 'other'];

    protected $fillable = [
        'expense_category_id', 'vehicle_id', 'title', 'amount', 'expense_date', 'payment_method',
        'paid_to', 'description', 'receipt_path', 'created_by',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'expense_date' => 'date'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
