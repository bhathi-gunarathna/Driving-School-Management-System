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
use App\Http\Controllers\Admin\Vehicles\VehicleDocumentController;
use App\Livewire\Admin\Vehicles\Create as CreateVehicle;
use App\Livewire\Admin\Vehicles\Edit as EditVehicle;
use App\Livewire\Admin\Vehicles\Index as VehicleIndex;
use App\Livewire\Admin\Vehicles\Expiry as VehicleExpiry;
use App\Livewire\Admin\Vehicles\Notifications as VehicleNotifications;
use App\Livewire\Admin\Vehicles\View as ViewVehicle;
use App\Http\Controllers\Admin\Students\MedicalReportController;
use App\Http\Controllers\Admin\Students\StudentPhotoController;
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
use App\Http\Controllers\Admin\ExpenseExportController;
use App\Http\Controllers\Admin\ExpenseReceiptController;
use App\Livewire\Admin\Settings\Edit as EditSystemSettings;
use App\Livewire\Admin\Audit\Index as AuditLogIndex;
use App\Livewire\Admin\Expenses\Index as ExpenseIndex;
use App\Livewire\Admin\Expenses\Create as CreateExpense;
use App\Livewire\Admin\Expenses\Edit as EditExpense;
use App\Livewire\Admin\Expenses\View as ViewExpense;
use App\Livewire\Admin\Expenses\Categories as ExpenseCategories;
use App\Livewire\Admin\Expenses\Reports as ExpenseReports;
use App\Livewire\Notifications\Index as NotificationIndex;


// Public Routes
Route::redirect('/', '/login');


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

            Route::middleware('permission:settings.manage')->get('/settings', EditSystemSettings::class)->name('settings.edit');
            Route::middleware('permission:audit-logs.view')->get('/audit-logs', AuditLogIndex::class)->name('audit.index');

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

            Route::prefix('expenses')->name('expenses.')->group(function () {
                Route::middleware('permission:expenses.view')->group(function () {
                    Route::get('/', ExpenseIndex::class)->name('index');
                    Route::get('/reports', ExpenseReports::class)->name('reports');
                    Route::get('/export.csv', ExpenseExportController::class)->name('export');
                    Route::get('/{expense}/receipt', ExpenseReceiptController::class)->whereNumber('expense')->name('receipt');
                    Route::get('/{expense}', ViewExpense::class)->whereNumber('expense')->name('view');
                });
                Route::middleware('permission:expenses.create')->get('/create', CreateExpense::class)->name('create');
                Route::middleware('permission:expenses.edit')->get('/{expense}/edit', EditExpense::class)->whereNumber('expense')->name('edit');
                Route::middleware('permission:expenses.manage_categories')->get('/categories', ExpenseCategories::class)->name('categories');
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

    Route::get('/notifications', NotificationIndex::class)->name('notifications.index');

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
        ->prefix('admin/vehicles')
        ->name('admin.vehicles.')
        ->group(function () {
            Route::middleware('permission:vehicles.manage')->group(function () {
                Route::get('/create', CreateVehicle::class)->name('create');
                Route::get('/{vehicle}/edit', EditVehicle::class)->whereNumber('vehicle')->name('edit');
            });

            Route::middleware('permission:vehicles.view')->group(function () {
                Route::get('/', VehicleIndex::class)->name('index');
                Route::get('/document-expiry', VehicleExpiry::class)->name('expiry');
                Route::get('/notification-history', VehicleNotifications::class)->name('notifications');
                Route::get('/{vehicle}/documents/{type}', [VehicleDocumentController::class, 'current'])
                    ->whereNumber('vehicle')->whereIn('type', ['insurance', 'revenue-license'])->name('document');
                Route::get('/renewal-documents/{renewal}', [VehicleDocumentController::class, 'renewal'])
                    ->whereNumber('renewal')->name('renewal-document');
                Route::get('/{vehicle}', ViewVehicle::class)->whereNumber('vehicle')->name('view');
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
                Route::get('/{student}/photo', StudentPhotoController::class)
                    ->whereNumber('student')->name('photo');
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
