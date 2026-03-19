<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Merchant\TeamMember\Cashier\CasierDashboardController;
use App\Http\Controllers\Merchant\TeamMember\Cashier\CashierBalanceController;

 
    Route::prefix('merchant/cashier')->name('merchant.cashier.')->middleware(['auth'])->group(function () {
        // Balance Routes
        Route::prefix('balance')->name('balance.')->group(function () {
            Route::get('/', [CashierBalanceController::class, 'index'])->name('index');
            Route::get('/get', [CashierBalanceController::class, 'getBalance'])->name('get');
            Route::get('/transactions', [CashierBalanceController::class, 'getTransactions'])->name('transactions');
            Route::get('/account-details', [CashierBalanceController::class, 'getAccountDetails'])->name('account-details');
        });
    
 
    });



?>