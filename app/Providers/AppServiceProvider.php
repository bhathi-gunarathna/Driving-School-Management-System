<?php

namespace App\Providers;

use App\Models\Student;
use App\Models\StudentPayment;
use App\Models\StudentVehicleCategory;
use App\Models\SystemSetting;
use App\Models\TrainingCoachAssignment;
use App\Models\TrainingSession;
use App\Models\Trial;
use App\Models\TrialCategoryResult;
use App\Models\TrialParticipant;
use App\Models\TrialResultHistory;
use App\Models\User;
use App\Models\VehicleCategory;
use App\Observers\AuditModelObserver;
use Illuminate\Support\ServiceProvider;
use App\Http\Responses\LoginResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            LoginResponseContract::class,
            LoginResponse::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        foreach ([Student::class, User::class, VehicleCategory::class, StudentVehicleCategory::class, StudentPayment::class, TrainingCoachAssignment::class, TrainingSession::class, Trial::class, TrialParticipant::class, TrialCategoryResult::class, TrialResultHistory::class, SystemSetting::class] as $model) {
            $model::observe(AuditModelObserver::class);
        }

        View::composer(['layouts.admin', 'layouts.coach'], function ($view): void {
            $view->with('unreadNotificationCount', auth()->user()?->unreadNotifications()->count() ?? 0);
            try {
                $view->with('schoolSettings', SystemSetting::current());
            } catch (\Throwable) {
                $view->with('schoolSettings', new SystemSetting());
            }
        });
    }
}
