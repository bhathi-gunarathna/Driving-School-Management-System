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
            Route::view('/dashboard', 'admin.dashboard')
                ->name('dashboard');


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

            Route::view('/dashboard', 'coach.dashboard')
                ->name('dashboard');

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

});
