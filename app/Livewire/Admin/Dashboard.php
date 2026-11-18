<?php

namespace App\Livewire\Admin;

use App\Models\Student;
use App\Models\StudentPayment;
use App\Models\StudentPaymentPlan;
use App\Models\SystemSetting;
use App\Models\Trial;
use App\Models\TrainingSession;
use App\Models\User;
use App\Models\VehicleCategory;
use App\Services\VehicleDocumentExpiryService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Dashboard extends Component
{
    public function mount(): void
    {
        $user = auth()->user();
        abort_unless($user?->hasRole('Owner') && $user->can('dashboard.view'), 403);
    }

    public function render()
    {
        $user = auth()->user();
        abort_unless($user?->hasRole('Owner') && $user->can('dashboard.view'), 403);
        $financeVisible = $user->can('financial-reports.view');
        $today = CarbonImmutable::now(config('app.timezone', 'UTC'));
        $dailyIncome = collect();
        $recentPayments = collect();
        $receivedTotal = 0;
        $outstandingTotal = 0;
        $vehicleAlertsVisible = $user->hasRole('Owner') && $user->can('vehicles.view');
        $vehicleSummary = [
            'active_vehicles' => 0,
            'expiring_soon' => 0,
            'expired_documents' => 0,
            'insurance_expiring' => 0,
            'licenses_expiring' => 0,
        ];
        $vehicleAlerts = collect();

        if ($vehicleAlertsVisible) {
            $schoolTimezone = SystemSetting::query()->value('timezone');
            if (! is_string($schoolTimezone) || ! in_array($schoolTimezone, timezone_identifiers_list(), true)) {
                $schoolTimezone = config('app.timezone', 'Asia/Colombo');
            }
            $vehicleToday = CarbonImmutable::now($schoolTimezone)->startOfDay();
            $todayDate = $vehicleToday->toDateString();
            $throughDate = $vehicleToday->addDays(30)->toDateString();
            $documents = DB::query()->fromSub(app(VehicleDocumentExpiryService::class)->documents(true), 'vehicle_documents');

            $vehicleSummary = [
                'active_vehicles' => DB::table('vehicles')->where('status', 'active')->count(),
                'expiring_soon' => (clone $documents)->whereDate('expiry_date', '>=', $todayDate)->whereDate('expiry_date', '<=', $throughDate)->count(),
                'expired_documents' => (clone $documents)->whereDate('expiry_date', '<', $todayDate)->count(),
                'insurance_expiring' => (clone $documents)->where('document_type', 'insurance')->whereDate('expiry_date', '>=', $todayDate)->whereDate('expiry_date', '<=', $throughDate)->count(),
                'licenses_expiring' => (clone $documents)->where('document_type', 'revenue_license')->whereDate('expiry_date', '>=', $todayDate)->whereDate('expiry_date', '<=', $throughDate)->count(),
            ];

            $vehicleAlerts = (clone $documents)
                ->whereDate('expiry_date', '<=', $throughDate)
                ->orderBy('expiry_date')
                ->orderBy('registration_number')
                ->limit(8)
                ->get()
                ->map(function (object $document) use ($vehicleToday, $schoolTimezone): object {
                    $expiryDate = CarbonImmutable::parse($document->expiry_date, $schoolTimezone)->startOfDay();
                    $document->days_remaining = (int) $vehicleToday->diffInDays($expiryDate, false);

                    return $document;
                });
        }

        if ($financeVisible) {
            $from = $today->subDays(6)->toDateString();
            $to = $today->toDateString();
            $amounts = StudentPayment::query()->whereDate('payment_date', '>=', $from)->whereDate('payment_date', '<=', $to)
                ->selectRaw('DATE(payment_date) as report_date, SUM(amount) as income')->groupByRaw('DATE(payment_date)')->pluck('income', 'report_date');
            $dailyIncome = collect(range(6, 0))->map(function (int $daysAgo) use ($today, $amounts): array {
                $date = $today->subDays($daysAgo);
                return ['label' => $date->format('D'), 'date' => $date->toDateString(), 'income' => (float) ($amounts[$date->toDateString()] ?? 0)];
            });
            $receivedTotal = StudentPayment::sum('amount');
            $outstandingTotal = StudentPaymentPlan::sum('outstanding_balance');
            $recentPayments = StudentPayment::with('student')->orderByDesc('payment_date')->orderByDesc('id')->limit(6)->get();
        }

        return view('livewire.admin.dashboard', [
            'totalStudents' => Student::count(),
            'activeStudents' => Student::where('status', Student::STATUS_ACTIVE)->count(),
            'activeCoaches' => User::role('Coach')->where('is_active', true)->count(),
            'activeCategories' => VehicleCategory::active()->count(),
            'recentStudents' => Student::query()->latest()->limit(5)->get(),
            'upcomingTrials' => Trial::upcoming()->withCount('participants')->orderBy('trial_date')->limit(5)->get(),
            'upcomingTrialCount' => Trial::upcoming()->count(),
            'todayTrainingCount' => TrainingSession::whereDate('training_date', $today->toDateString())->count(),
            'financeVisible' => $financeVisible,
            'vehicleAlertsVisible' => $vehicleAlertsVisible,
            'vehicleSummary' => $vehicleSummary,
            'vehicleAlerts' => $vehicleAlerts,
            'vehicleThroughLabel' => isset($vehicleToday) ? $vehicleToday->addDays(30)->format(config('school.date_format', 'd M Y')) : null,
            'receivedTotal' => $receivedTotal,
            'outstandingTotal' => $outstandingTotal,
            'recentPayments' => $recentPayments,
            'dailyIncome' => $dailyIncome,
            'maxDailyIncome' => max(1, (float) $dailyIncome->max('income')),
        ])->layout('layouts.admin');
    }
}
