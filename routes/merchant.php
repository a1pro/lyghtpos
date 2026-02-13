<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Merchant\MerchantDashboardController;
use App\Http\Controllers\Merchant\MerchantRepresentationController;
use App\Http\Controllers\Merchant\PlanController;
use App\Http\Controllers\ProchargePaymentController;

Route::prefix('merchant')->name('merchant.')->middleware(['auth', 'locale', 'role:merchant', 'subscription', 'representation'])->group(function (): void {


    Route::get('/', [MerchantDashboardController::class, 'index'])->name('dashboard');
    Route::get('/external-links', [MerchantDashboardController::class, 'externalLinks'])->name('external-links');

    Route::get('/barter-assist-cash-price', [MerchantDashboardController::class, 'showBarterAssistCashPrice'])->name('barter-assist-cash-price');
    Route::get('/barter-assist-pos', [MerchantDashboardController::class, 'showBarterAssistPos'])->name('barter-assist-pos');

    Route::prefix('subscription')->name('subscription.')->group(function (): void {

        Route::get('/plans', [PlanController::class, 'index'])->name('plans');

        Route::get('/demo-card-details', [PlanController::class, 'showCardDetails'])->name('demo.card');
        Route::post('/demo-payment/submit', [PlanController::class, 'submitPayment'])->name('demo.card.submit');
    });

    Route::prefix('representation')->name('representation.')->group(function (): void {

        Route::get('/form', [MerchantRepresentationController::class, 'showForm'])->name('form');
        Route::post('/submit', [MerchantRepresentationController::class, 'submitForm'])->name('submit');
    });
});

// for testing outside auth
// Route::prefix('merchant')->name('merchant.')->group(function (): void {

//     Route::get('/procharge/intent', [ProchargePaymentController::class, 'createIntent']);
// });
