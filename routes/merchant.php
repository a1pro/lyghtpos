<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Merchant\MerchantDashboardController;
use App\Http\Controllers\ProchargePaymentController;

Route::prefix('merchant')->name('merchant.')->middleware(['auth', 'locale'])->group(function (): void {

    Route::get('/', [MerchantDashboardController::class, 'index'])->name('dashboard');
});

// for testing outside auth
Route::prefix('merchant')->name('merchant.')->group(function (): void {

    Route::get('/procharge/intent', [ProchargePaymentController::class, 'createIntent']);
});
