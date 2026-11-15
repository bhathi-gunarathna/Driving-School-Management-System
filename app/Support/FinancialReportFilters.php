<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class FinancialReportFilters
{
    public const PERIODS = ['today', 'yesterday', 'this_week', 'this_month', 'last_month', 'this_year', 'custom'];

    /** @param array<string, mixed> $filters @return array{0: CarbonImmutable, 1: CarbonImmutable}|null */
    public function dateRange(array $filters): ?array
    {
        $timezone = config('app.timezone', 'UTC');
        $now = CarbonImmutable::now($timezone);
        $period = in_array($filters['period'] ?? null, self::PERIODS, true) ? $filters['period'] : 'this_month';

        return match ($period) {
            'today' => [$now->startOfDay(), $now->endOfDay()],
            'yesterday' => [$now->subDay()->startOfDay(), $now->subDay()->endOfDay()],
            'this_week' => [$now->startOfWeek()->startOfDay(), $now->endOfWeek()->endOfDay()],
            'this_month' => [$now->startOfMonth()->startOfDay(), $now->endOfMonth()->endOfDay()],
            'last_month' => [$now->subMonthNoOverflow()->startOfMonth()->startOfDay(), $now->subMonthNoOverflow()->endOfMonth()->endOfDay()],
            'this_year' => [$now->startOfYear()->startOfDay(), $now->endOfYear()->endOfDay()],
            'custom' => $this->customRange($filters['startDate'] ?? null, $filters['endDate'] ?? null, $timezone),
        };
    }

    /** @param array<string, mixed> $filters */
    public function payments(array $filters): Builder
    {
        $query = \App\Models\StudentPayment::query()->with(['student', 'receivedBy']);
        if (($range = $this->dateRange($filters)) !== null) {
            $query->whereDate('payment_date', '>=', $range[0]->toDateString())
                ->whereDate('payment_date', '<=', $range[1]->toDateString());
        } else {
            $query->whereRaw('1 = 0');
        }

        $method = $filters['method'] ?? $filters['methodFilter'] ?? '';
        if (in_array($method, ['cash', 'bank_transfer', 'card', 'other'], true)) {
            $query->where('payment_method', $method);
        }

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $matches) use ($search): void {
                $matches->where('receipt_number', 'like', "%{$search}%")
                    ->orWhereHas('student', fn (Builder $student) => $student->where('student_number', 'like', "%{$search}%")
                        ->orWhere('full_name', 'like', "%{$search}%")
                        ->orWhere('nic_number', 'like', "%{$search}%"));
            });
        }

        $sort = in_array($filters['sortField'] ?? $filters['sort'] ?? null, ['payment_date', 'amount'], true)
            ? ($filters['sortField'] ?? $filters['sort']) : 'payment_date';
        $direction = strtoupper((string) ($filters['sortDirection'] ?? $filters['direction'] ?? 'desc')) === 'ASC' ? 'asc' : 'desc';

        return $query->orderBy($sort, $direction)->orderBy('id', $direction);
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable}|null */
    private function customRange(mixed $start, mixed $end, string $timezone): ?array
    {
        if (! is_string($start) || ! is_string($end) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $start) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) {
            return null;
        }

        try {
            $startDate = CarbonImmutable::createFromFormat('!Y-m-d', $start, $timezone);
            $endDate = CarbonImmutable::createFromFormat('!Y-m-d', $end, $timezone);
        } catch (\Throwable) {
            return null;
        }

        if (! $startDate || ! $endDate || $startDate->format('Y-m-d') !== $start || $endDate->format('Y-m-d') !== $end || $endDate->lessThan($startDate)) {
            return null;
        }

        return [$startDate->startOfDay(), $endDate->endOfDay()];
    }
}
