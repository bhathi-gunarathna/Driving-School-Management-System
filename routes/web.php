<?php

use Illuminate\Support\Facades\Route;

use App\Livewire\Admin\Coaches\Index;
use App\Livewire\Admin\Coaches\Create;
use App\Livewire\Admin\Coaches\Edit;
use App\Livewire\Admin\Coaches\View as ViewCoach;


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

});