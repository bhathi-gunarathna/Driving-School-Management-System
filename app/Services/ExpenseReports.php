<?php

namespace App\Services;

use App\Models\Expense;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ExpenseReports
{
    /** @param array<string, mixed> $filters */
    public function expenses(array $filters): Builder
    {
        $query = Expense::query()->with(['category', 'vehicle', 'createdBy']);

        if (($range = $this->dateRange($filters)) !== null) {
            $query->whereDate('expense_date', '>=', $range[0]->toDateString())
                ->whereDate('expense_date', '<=', $range[1]->toDateString());
        } elseif (($filters['startDate'] ?? '') !== '' || ($filters['endDate'] ?? '') !== '') {
            $query->whereRaw('1 = 0');
        }

        $query->when($this->positiveInteger($filters['category'] ?? null), fn (Builder $builder, int $id) => $builder->where('expense_category_id', $id))
            ->when($this->positiveInteger($filters['vehicle'] ?? null), fn (Builder $builder, int $id) => $builder->where('vehicle_id', $id))
            ->when(in_array($filters['method'] ?? null, Expense::PAYMENT_METHODS, true), fn (Builder $builder) => $builder->where('payment_method', $filters['method']));

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $matches) use ($search): void {
                $like = '%'.$search.'%';
                $matches->where('title', 'like', $like)
                    ->orWhere('paid_to', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhereHas('category', fn (Builder $category) => $category->where('name', 'like', $like))
                    ->orWhereHas('vehicle', fn (Builder $vehicle) => $vehicle->where('registration_number', 'like', $like));
            });
        }

        return $query;
    }

    /** @param array<string, mixed> $filters @return array{0: CarbonImmutable, 1: CarbonImmutable}|null */
    public function dateRange(array $filters): ?array
    {
        $start = $filters['startDate'] ?? null;
        $end = $filters['endDate'] ?? null;
        if (! is_string($start) || ! is_string($end) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $start) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) {
            return null;
        }

        try {
            $from = CarbonImmutable::createFromFormat('!Y-m-d', $start, config('app.timezone', 'Asia/Colombo'));
            $to = CarbonImmutable::createFromFormat('!Y-m-d', $end, config('app.timezone', 'Asia/Colombo'));
        } catch (\Throwable) {
            return null;
        }

        if (! $from || ! $to || $from->format('Y-m-d') !== $start || $to->format('Y-m-d') !== $end || $to->lessThan($from)) {
            return null;
        }

        return [$from->startOfDay(), $to->endOfDay()];
    }

    /** @param array<string, mixed> $filters @return \Illuminate\Support\Collection<int, object> */
    public function grouped(array $filters, string $group): \Illuminate\Support\Collection
    {
        $query = $this->expenses($filters)->reorder();
        $driver = DB::connection()->getDriverName();

        if ($group === 'category') {
            return $query->join('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
                ->selectRaw('expense_categories.id as group_id, expense_categories.name as label, COUNT(expenses.id) as expense_count, SUM(expenses.amount) as total')
                ->groupBy('expense_categories.id', 'expense_categories.name')->orderBy('expense_categories.name')->get();
        }

        if ($group === 'vehicle') {
            return $query->leftJoin('vehicles', 'expenses.vehicle_id', '=', 'vehicles.id')
                ->selectRaw("expenses.vehicle_id as group_id, COALESCE(vehicles.registration_number, 'Unassigned') as label, COUNT(expenses.id) as expense_count, SUM(expenses.amount) as total")
                ->groupBy('expenses.vehicle_id', 'vehicles.registration_number')->orderBy('label')->get();
        }

        $expression = match ($group) {
            'monthly' => $driver === 'sqlite' ? "strftime('%Y-%m', expense_date)" : "DATE_FORMAT(expense_date, '%Y-%m')",
            'yearly' => $driver === 'sqlite' ? "strftime('%Y', expense_date)" : 'YEAR(expense_date)',
            default => 'DATE(expense_date)',
        };

        return $query->selectRaw("{$expression} as label, COUNT(expenses.id) as expense_count, SUM(expenses.amount) as total")
            ->groupByRaw($expression)->orderBy('label')->get();
    }

    private function positiveInteger(mixed $value): ?int
    {
        return filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
    }
}
