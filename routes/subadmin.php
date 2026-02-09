<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Subadmin\SubadminDashboardController;
use App\Http\Controllers\ProchargePaymentController;
use App\Http\Controllers\Subadmin\RepresentationController;

Route::prefix('subadmin')->name('subadmin.')->middleware(['auth', 'locale', 'role:subadmin'])->group(function (): void {

    Route::get('/', [SubadminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/merchants', [SubadminDashboardController::class, 'renderMerchants'])->name('merchants');

    // Representation routes
    Route::controller(RepresentationController::class)->name('representations.')->group(function () {
        Route::get('/merchants/representations/{userId}', 'viewRepresentation')->name('view');
        Route::post('/merchants/representations/{representation}/review', 'submitReview')->name('submit-review');
    });
});
