<?php

namespace App\Livewire\Admin\Finance;

use App\Models\Student;
use App\Models\StudentPayment;
use App\Models\StudentPaymentPlan;
use App\Models\Trial;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\FinancialReports;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Overview extends Component
{
    public function mount(): void { $this->authorizeOwner(); }

    private function authorizeOwner(): void
    {
        $user = auth()->user();
        abort_unless($user?->hasRole('Owner') && $user->can('financial-reports.view'), 403);
    }

    public function render()
    {
        $this->authorizeOwner();
        $plans = StudentPaymentPlan::query();
        $statusCounts = (clone $plans)->select('payment_status', DB::raw('COUNT(*) as count'))->groupBy('payment_status')->pluck('count', 'payment_status');
        $totalFees = (clone $plans)->sum('total_agreed_fee');
        $outstanding = (clone $plans)->sum('outstanding_balance');
        $received = StudentPayment::sum('amount');
        $today = CarbonImmutable::now(config('app.timezone', 'UTC'))->startOfDay();
        $dailyTotals = StudentPayment::query()->whereDate('payment_date', '>=', $today->subDays(13)->toDateString())
            ->whereDate('payment_date', '<=', $today->toDateString())
            ->selectRaw('DATE(payment_date) as report_date, SUM(amount) as income')->groupByRaw('DATE(payment_date)')->pluck('income', 'report_date');
        $dailyTrend = collect(range(13, 0))->map(function (int $offset) use ($today, $dailyTotals): array {
            $date = $today->subDays($offset)->toDateString();
            return ['label' => $today->subDays($offset)->format('d M'), 'income' => (float) ($dailyTotals[$date] ?? 0)];
        });
        $monthExpression = DB::connection()->getDriverName() === 'sqlite' ? "strftime('%Y-%m', payment_date)" : "DATE_FORMAT(payment_date, '%Y-%m')";
        $monthTotals = StudentPayment::query()->whereBetween('payment_date', [$today->subMonthsNoOverflow(11)->startOfMonth()->toDateString(), $today->toDateString()])
            ->selectRaw("{$monthExpression} as report_month, SUM(amount) as income")->groupByRaw($monthExpression)->pluck('income', 'report_month');
        $monthlyTrend = collect(range(11, 0))->map(function (int $offset) use ($today, $monthTotals): array {
            $month = $today->subMonthsNoOverflow($offset)->startOfMonth();
            return ['label' => $month->format('M y'), 'income' => (float) ($monthTotals[$month->format('Y-m')] ?? 0)];
        });
        $methodBreakdown = StudentPayment::query()->selectRaw('payment_method, COUNT(*) as transactions, SUM(amount) as income')
            ->groupBy('payment_method')->orderBy('payment_method')->get();
        $recentPayments = StudentPayment::with(['student', 'receivedBy'])->orderByDesc('payment_date')->orderByDesc('id')->limit(8)->get();

        return view('livewire.admin.finance.overview', [
            'totalFees' => $totalFees,
            'received' => $received,
            'outstanding' => $outstanding,
            'fullyPaidCount' => $statusCounts[StudentPaymentPlan::STATUS_FULLY_PAID] ?? 0,
            'partiallyPaidCount' => $statusCounts[StudentPaymentPlan::STATUS_PARTIALLY_PAID] ?? 0,
            'pendingCount' => $statusCounts[StudentPaymentPlan::STATUS_PENDING] ?? 0,
            'dailyTrend' => $dailyTrend,
            'monthlyTrend' => $monthlyTrend,
            'methodBreakdown' => $methodBreakdown,
            'recentPayments' => $recentPayments,
            'totalCourseFees' => $totalFees,
            'paidVsOutstandingMax' => max((float) $received, (float) $outstanding, 1),
            'maxDailyIncome' => max(1, (float) $dailyTrend->max('income')),
            'maxMonthlyIncome' => max(1, (float) $monthlyTrend->max('income')),
            'methodTotal' => max(1, (float) $methodBreakdown->sum('income')),
        ])->layout('layouts.admin');
    }
}
