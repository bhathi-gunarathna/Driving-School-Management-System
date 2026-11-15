<?php

namespace App\Services;

use App\Models\StudentPayment;
use App\Models\StudentPaymentPlan;
use App\Support\FinancialReportFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class FinancialReports
{
    /** @param array<string, mixed> $filters */
    public function payments(array $filters): Builder
    {
        return app(FinancialReportFilters::class)->payments($filters);
    }

    /** @param array<string, mixed> $filters */
    public function outstanding(array $filters): Builder
    {
        $query = StudentPaymentPlan::query()->with('student')->where('outstanding_balance', '>', 0);
        $status = $filters['status'] ?? '';
        if (in_array($status, [StudentPaymentPlan::STATUS_PENDING, StudentPaymentPlan::STATUS_PARTIALLY_PAID], true)) {
            $query->where('payment_status', $status);
        }
        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->whereHas('student', fn (Builder $student) => $student->where('student_number', 'like', "%{$search}%")
                ->orWhere('full_name', 'like', "%{$search}%")
                ->orWhere('nic_number', 'like', "%{$search}%")
                ->orWhere('contact_number', 'like', "%{$search}%"));
        }

        $direction = strtoupper((string) ($filters['direction'] ?? 'desc')) === 'ASC' ? 'asc' : 'desc';
        return $query->orderBy('outstanding_balance', $direction)->orderBy('id');
    }

    /** @param array<string, mixed> $filters */
    public function dailyIncome(array $filters): \Illuminate\Support\Collection
    {
        return $this->payments($filters)->reorder()
            ->selectRaw('DATE(payment_date) as report_date, COUNT(id) as transaction_count, SUM(amount) as income')
            ->groupByRaw('DATE(payment_date)')->orderBy('report_date')->get();
    }

    /** @param array<string, mixed> $filters */
    public function monthlyIncome(array $filters): \Illuminate\Support\Collection
    {
        $expression = match (DB::connection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m', payment_date)",
            'pgsql' => "TO_CHAR(payment_date, 'YYYY-MM')",
            default => "DATE_FORMAT(payment_date, '%Y-%m')",
        };

        return $this->payments($filters)->reorder()
            ->selectRaw("{$expression} as report_month, COUNT(id) as transaction_count, SUM(amount) as income")
            ->groupByRaw($expression)->orderByRaw("{$expression} asc")->get();
    }
}
