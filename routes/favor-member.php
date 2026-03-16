<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\FavorMember\WalletController;
use App\Http\Controllers\FavorMember\FavorMemberDashboardController;
use App\Http\Controllers\Inventory\ProductController;
use App\Http\Controllers\Management\CustomerController;
use App\Http\Controllers\Pos\CartController;
use App\Http\Controllers\Pos\OrderController;

Route::prefix('favor-member')->name('favor-member.')->middleware(['auth', 'role:favor_member'])->group(function () {

    Route::get('/', [WalletController::class, 'dashboard'])->name('dashboard');

    // Wallet routes
    Route::prefix('wallet')->name('wallet.')->group(function () {
        Route::get('/balance', [WalletController::class, 'getBalance'])->name('balance');
        Route::post('/add-funds', [WalletController::class, 'addFunds'])->name('add-funds');
        Route::get('/payment-form', [WalletController::class, 'showPaymentForm'])->name('payment-form');
        Route::post('/process-payment', [WalletController::class, 'processPayment'])->name('process-payment');
        Route::get('/payment-success', [WalletController::class, 'paymentSuccess'])->name('payment-success');
        Route::get('/transactions', [WalletController::class, 'transactions'])->name('transactions');
    });

    // Cart
    Route::resource('products', ProductController::class)->names('products');
    Route::resource('customers', CustomerController::class)->names('customers');
    Route::resource('orders', OrderController::class)->names('orders');

    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart', [CartController::class, 'store'])->name('cart.store');
    Route::post('/cart/change-qty', [CartController::class, 'changeQty']);
    Route::delete('/cart/delete', [CartController::class, 'delete']);
    Route::delete('/cart/empty', [CartController::class, 'empty']);
});
