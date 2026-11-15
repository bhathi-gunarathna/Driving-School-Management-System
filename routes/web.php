<?php

use Illuminate\Support\Facades\Route;

use App\Livewire\Admin\Coaches\Index;
use App\Livewire\Admin\Coaches\Create;
use App\Livewire\Admin\Coaches\Edit;
use App\Livewire\Admin\Coaches\View as ViewCoach;
use App\Livewire\Admin\VehicleCategories\Create as CreateVehicleCategory;
use App\Livewire\Admin\VehicleCategories\Edit as EditVehicleCategory;
use App\Livewire\Admin\VehicleCategories\Index as VehicleCategoryIndex;
use App\Livewire\Admin\VehicleCategories\View as ViewVehicleCategory;
use App\Http\Controllers\Admin\Students\MedicalReportController;
use App\Livewire\Admin\Students\Create as CreateStudent;
use App\Livewire\Admin\Students\Edit as EditStudent;
use App\Livewire\Admin\Students\Index as StudentIndex;
use App\Livewire\Admin\Students\View as ViewStudent;
use App\Livewire\Admin\Payments\Index as PaymentIndex;
use App\Livewire\Admin\Payments\Create as CreatePayment;
use App\Livewire\Admin\Payments\Receipt as PaymentReceipt;
use App\Livewire\Admin\Training\Index as TrainingIndex;
use App\Livewire\Coach\Training\Dashboard as CoachTrainingDashboard;
use App\Livewire\Coach\Training\Index as CoachTrainingIndex;
use App\Livewire\Coach\Training\Record as RecordTrainingSession;
use App\Livewire\Admin\Trials\Index as TrialIndex;
use App\Livewire\Admin\Trials\Create as CreateTrial;
use App\Livewire\Admin\Trials\View as ViewTrial;
use App\Livewire\Coach\Trials\Index as CoachTrialIndex;
use App\Livewire\Coach\Trials\View as CoachTrialView;
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Admin\Finance\Overview as FinancialOverview;
use App\Livewire\Admin\Finance\IncomeReport;
use App\Livewire\Admin\Finance\OutstandingReport;
use App\Http\Controllers\Admin\FinancialExportController;


// Public Routes
Route::get('/', function () {
    return view('welcome');
});


// Authenticated Routes
Route::middleware('auth')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | ADMIN ROUTES
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:Owner')
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {

            // Dashboard
            Route::get('/dashboard', AdminDashboard::class)
                ->name('dashboard');

            Route::prefix('finance')->name('finance.')->group(function () {
                Route::middleware('permission:financial-reports.view')->group(function () {
                    Route::get('/overview', FinancialOverview::class)->name('overview');
                    Route::get('/income', IncomeReport::class)->name('income');
                    Route::get('/outstanding', OutstandingReport::class)->name('outstanding');
                });
                Route::middleware('permission:financial-reports.export')->prefix('exports')->name('exports.')->group(function () {
                    Route::get('/transactions.csv', [FinancialExportController::class, 'transactions'])->name('transactions');
                    Route::get('/daily.csv', [FinancialExportController::class, 'daily'])->name('daily');
                    Route::get('/monthly.csv', [FinancialExportController::class, 'monthly'])->name('monthly');
                    Route::get('/outstanding.csv', [FinancialExportController::class, 'outstanding'])->name('outstanding');
                });
            });


            /*
            |--------------------------------------------------------------------------
            | COACH MANAGEMENT
            |--------------------------------------------------------------------------
            */

            Route::prefix('coaches')
                ->name('coaches.')
                ->group(function () {

                    // Coach List
                    Route::middleware('permission:coaches.view')->group(function () {

                        Route::get('/', Index::class)
                            ->name('index');

                    });

                    // Create Coach
                    Route::middleware('permission:coaches.manage')->group(function () {

                        Route::get('/create', Create::class)
                            ->name('create');

                    });

                    // Edit Coach
                    Route::middleware('permission:coaches.manage')->group(function () {

                        Route::get('/{coach}/edit', Edit::class)
                            ->name('edit');

                    });

                    // View Coach - Always keep dynamic route last
                    Route::middleware('permission:coaches.view')->group(function () {

                        Route::get('/{coach}', ViewCoach::class)
                            ->name('view');

                    });

                });

        });


    /*
    |--------------------------------------------------------------------------
    | COACH ROUTES
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:Coach')
        ->prefix('coach')
        ->name('coach.')
        ->group(function () {

            Route::get('/dashboard', CoachTrainingDashboard::class)
                ->name('dashboard');

            Route::middleware('permission:training.view_assigned')->get('/training', CoachTrainingIndex::class)->name('training.index');
            Route::middleware('permission:training.record')->get('/training/enrollments/{enrollment}/record', RecordTrainingSession::class)
                ->whereNumber('enrollment')->name('training.record');
            Route::middleware('permission:trials.view_assigned')->prefix('trials')->name('trials.')->group(function () {
                Route::get('/', CoachTrialIndex::class)->name('index');
                Route::get('/{trial}', CoachTrialView::class)->whereNumber('trial')->name('view');
            });

        });

    /*
    |--------------------------------------------------------------------------
    | VEHICLE CATEGORY MANAGEMENT
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:Owner')
        ->prefix('admin/vehicle-categories')
        ->name('admin.vehicle-categories.')
        ->group(function () {
            Route::middleware('permission:vehicle-categories.manage')->group(function () {
                Route::get('/create', CreateVehicleCategory::class)->name('create');
                Route::get('/{category}/edit', EditVehicleCategory::class)->whereNumber('category')->name('edit');
            });

            Route::middleware('permission:vehicle-categories.view')->group(function () {
                Route::get('/', VehicleCategoryIndex::class)->name('index');
                Route::get('/{category}', ViewVehicleCategory::class)->whereNumber('category')->name('view');
            });
        });

    Route::middleware('role:Owner')
        ->prefix('admin/students')
        ->name('admin.students.')
        ->group(function () {
            Route::middleware('permission:students.create')->get('/create', CreateStudent::class)->name('create');

            Route::middleware('permission:students.update')->get('/{student}/edit', EditStudent::class)
                ->whereNumber('student')->name('edit');

            Route::middleware('permission:students.view_all')->group(function () {
                Route::get('/', StudentIndex::class)->name('index');
                Route::get('/{student}/medical-report', MedicalReportController::class)
                    ->whereNumber('student')->name('medical-report');
                Route::get('/{student}', ViewStudent::class)->whereNumber('student')->name('view');
            });
        });

    Route::middleware('role:Owner')->prefix('admin/payments')->name('admin.payments.')->group(function () {
        Route::middleware('permission:payments.manage')->get('/students/{student}/create', CreatePayment::class)
            ->whereNumber('student')->name('create');
        Route::middleware('permission:payments.view')->group(function () {
            Route::get('/', PaymentIndex::class)->name('index');
            Route::get('/{payment}/receipt', PaymentReceipt::class)->whereNumber('payment')->name('receipt');
        });
    });

    Route::middleware(['role:Owner', 'permission:training.view'])->prefix('admin/training')->name('admin.training.')
        ->get('/', TrainingIndex::class)->name('index');

    Route::middleware('role:Owner')->prefix('admin/trials')->name('admin.trials.')->group(function () {
        Route::middleware('permission:trials.manage')->get('/create', CreateTrial::class)->name('create');
        Route::middleware('permission:trials.view')->group(function () {
            Route::get('/', TrialIndex::class)->name('index');
            Route::get('/{trial}', ViewTrial::class)->whereNumber('trial')->name('view');
        });
    });

});
