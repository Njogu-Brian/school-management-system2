<?php

use App\Http\Controllers\Operator\OperatorBillingController;
use App\Http\Controllers\Operator\OperatorDashboardController;
use App\Http\Controllers\Operator\OperatorSchoolController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'ensure.control_plane', 'ensure.operator'])
    ->prefix('operator')
    ->name('operator.')
    ->group(function () {
        Route::get('/', [OperatorDashboardController::class, 'index'])->name('dashboard');
        Route::get('/schools', [OperatorSchoolController::class, 'index'])->name('schools.index');
        Route::get('/schools/create', [OperatorSchoolController::class, 'create'])->name('schools.create');
        Route::post('/schools', [OperatorSchoolController::class, 'store'])->name('schools.store');
        Route::get('/schools/{school}', [OperatorSchoolController::class, 'show'])->name('schools.show');
        Route::post('/schools/{school}/suspend', [OperatorSchoolController::class, 'suspend'])->name('schools.suspend');
        Route::post('/schools/{school}/activate', [OperatorSchoolController::class, 'activate'])->name('schools.activate');
        Route::get('/billing', [OperatorBillingController::class, 'index'])->name('billing.index');
        Route::post('/schools/{school}/payments', [OperatorBillingController::class, 'storePayment'])->name('schools.payments.store');
    });
