<?php

use App\Http\Controllers\Api\V1\POS\Auth\PosLoginController;
use App\Http\Controllers\Api\V1\POS\Auth\PosRegisterController;
use App\Http\Controllers\Api\V1\POS\Customer\PosCustomerWallet;
use Illuminate\Support\Facades\Route;

Route::prefix('pos')->group(function () {
    Route::post('login', [PosLoginController::class, 'login'])->name('pos.login');
    Route::post('register', [PosRegisterController::class, 'register'])->name('pos.register');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [PosLoginController::class, 'logout']);

        // ====================User Routes===============================
        Route::prefix('user')->group(function () {

            Route::get('profile', [PosLoginController::class, 'userProfile']);
            Route::get('wallet/balance',[PosCustomerWallet::class,'getBalance']);
        });

    });

});
