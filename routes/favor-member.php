<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\FavorMember\WalletController;
use App\Http\Controllers\FavorMember\FavorMemberDashboardController;
// Favor Member Routes





Route::prefix('favor-member')->name('favor-member.')->middleware(['auth', 'role:favor_member'])->group(function () {
    
    
   
    Route::get('/', [WalletController::class, 'dashboard'])->name('dashboard');
    
    // Wallet routes
    Route::prefix('wallet')->name('wallet.')->group(function () {
        Route::get('/test-balance', [App\Http\Controllers\FavorMember\WalletController::class, 'testBalance'])
    ->middleware('auth');
        Route::get('/balance', [WalletController::class, 'getBalance'])->name('balance');
        Route::post('/add-funds', [WalletController::class, 'addFunds'])->name('add-funds');
        Route::get('/payment-form', [WalletController::class, 'showPaymentForm'])->name('payment-form');
        Route::post('/process-payment', [WalletController::class, 'processPayment'])->name('process-payment');
        Route::get('/payment-success', [WalletController::class, 'paymentSuccess'])->name('payment-success');
        Route::get('/transactions', [WalletController::class, 'transactions'])->name('transactions');
    });
});