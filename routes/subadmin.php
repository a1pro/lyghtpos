<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Subadmin\SubadminDashboardController;
use App\Http\Controllers\ProchargePaymentController;
use App\Http\Controllers\Subadmin\RepresentationController;

Route::prefix('subadmin')->name('subadmin.')->middleware(['auth', 'locale', 'role:subadmin'])->group(function (): void {

    Route::get('/merchants/{id}/change-password', [SubadminDashboardController::class, 'showChangePasswordForm'])->name('merchants.change-password');
    Route::post('/merchants/{id}/update-password', [SubadminDashboardController::class, 'updatePassword'])->name('merchants.update-password');

    Route::get('/', [SubadminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/merchants', [SubadminDashboardController::class, 'renderMerchants'])->name('merchants');
    Route::post('/users/{userId}/governance/{governanceId}/update', 
    [RepresentationController::class, 'updateGovernanceLayer'])
    ->name('users.governance.update');
    // Representation routes
    Route::controller(RepresentationController::class)->name('representations.')->group(function () {
        Route::get('/merchants/representations/{userId}', 'viewRepresentation')->name('view');
        Route::post('/merchants/representations/{representation}/review', 'submitReview')->name('submit-review');

  

       
    });
});
