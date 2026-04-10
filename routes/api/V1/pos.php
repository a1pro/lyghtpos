<?php

use App\Http\Controllers\Api\V1\POS\Auth\PosLoginController;
use App\Http\Controllers\Api\V1\POS\Auth\PosRegisterController;
use App\Http\Controllers\Api\V1\POS\Customer\PosCustomerOrderController;
use App\Http\Controllers\Api\V1\POS\Customer\PosCustomerWallet;
use App\Http\Controllers\Api\V1\POS\Customer\PosJoiningFeesController;
use App\Http\Controllers\Api\V1\POS\Customer\PosPayPalPaymentController;
use App\Http\Controllers\Api\V1\POS\Customer\PosTokenBundleController;
use Illuminate\Support\Facades\Route;

Route::prefix('pos')->group(function () {
    /** ******************* Auth Routes ******************** */
    Route::post('login', [PosLoginController::class, 'login'])->name('pos.login');
    Route::post('register', [PosRegisterController::class, 'register'])->name('pos.register');

    Route::get('joining-plans', [PosJoiningFeesController::class, 'getPlans']);

    /**  *************** Token Bundle Routes ********************* */
    Route::get('bundle/active', [PosTokenBundleController::class, 'getActiveTokenBundles']);

    Route::middleware('auth:sanctum')->group(function () {

        Route::post('logout', [PosLoginController::class, 'logout']);
        Route::prefix('paypal')->group(function () {
            Route::post('create-order', [PosPayPalPaymentController::class, 'createOrder']);
            Route::post('capture-payment', [PosPayPalPaymentController::class, 'capturePayment']);
            Route::get('payment-status', [PosPayPalPaymentController::class, 'checkPaymentStatus']);
            Route::get('payment-history', [PosPayPalPaymentController::class, 'getPaymentHistory']);
        });

        /** ************** User Routes ************************ */
        Route::prefix('user')->group(function () {
            Route::post('otp/verification', [PosRegisterController::class, 'otpVerification']);
            Route::post('otp/resend', [PosRegisterController::class, 'resendOtp']);
            Route::get('profile', [PosLoginController::class, 'userProfile']);
            Route::get('email/status', [PosRegisterController::class, 'checEmailVerification']);

            

            /** ******* Wallet related routes ********** */
            Route::prefix('wallet')->group(function () {
                Route::get('balance', [PosCustomerWallet::class, 'getBalance']);
                Route::post('add/balance', [PosCustomerWallet::class, 'customerWalletRecharge']);
                Route::get('transaction', [PosCustomerWallet::class, 'showTransaction']);
            });
            
            /** ******* ORDER related routes ********** */
            Route::prefix('order')->group(function () {
                Route::get('pending', [PosCustomerOrderController::class, 'getPendingOrders']);
                Route::get('history', [PosCustomerOrderController::class, 'getOrdersHistory']);
                Route::post('purchase', [PosCustomerOrderController::class, 'purchaseOrder']);
            });

            /**  ************ Joining Fees Related Routes ***************** */
            Route::get('joining/fees/status', [PosJoiningFeesController::class, 'getPaymentStatus']);
        });
    });
});
