<?php

use App\Http\Controllers\Api\V1\POS\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\V1\POS\Auth\PosLoginController;
use App\Http\Controllers\Api\V1\POS\Auth\PosRegisterController;
use App\Http\Controllers\Api\V1\POS\Customer\PosCustomerAgreementsController;
use App\Http\Controllers\Api\V1\POS\Customer\PosCustomerOrderController;
use App\Http\Controllers\Api\V1\POS\Customer\PosCustomerWallet;
use App\Http\Controllers\Api\V1\POS\Customer\PosJoiningFeesController;
use App\Http\Controllers\Api\V1\POS\Customer\PosPayPalPaymentController;
use App\Http\Controllers\Api\V1\POS\Customer\PosTokenBundleController;
use App\Http\Controllers\Api\V1\POS\Customer\PosCustomerSubscriptionController;
use App\Http\Controllers\Api\V1\POS\Customer\PosProfileController;
use App\Http\Controllers\Api\V1\POS\Settings\PosSettingsController;
use App\Http\Controllers\Location\LocationController;
use Illuminate\Support\Facades\Route;

Route::prefix('pos')->group(function () {
    /** ******************* Auth Routes ******************** */
    Route::post('login', [PosLoginController::class, 'login'])->name('pos.login');
    Route::post('register', [PosRegisterController::class, 'register'])->name('pos.register');

    /** ******************* Forgot Password Routes ******************** */
    Route::prefix('forgot-password')->group(function () {
        Route::post('send-otp', [ForgotPasswordController::class, 'sendOtp']);
        Route::post('verify-otp', [ForgotPasswordController::class, 'verifyOtp']);
        Route::post('reset', [ForgotPasswordController::class, 'resetPassword']);
    });

    Route::get('joining-plans', [PosJoiningFeesController::class, 'getPlans']);

    /**  *************** Token Bundle Routes ********************* */
    Route::get('bundle/active', [PosTokenBundleController::class, 'getActiveTokenBundles']);

    /**  *************** Settings Routes ********************* */
    Route::get('custom-token-charge', [PosSettingsController::class, 'getCustomTokenCharge']);

    /** *************** Location Routes (Country, State, City) ***************** */
    Route::prefix('locations')->group(function () {
        Route::get('countries', [LocationController::class, 'getCountries']);
        Route::get('states', [LocationController::class, 'getStatesByCountry']);
        Route::get('cities', [LocationController::class, 'getCitiesByState']);
    });

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
            Route::get('email/status', [PosRegisterController::class, 'checEmailVerification']);

            /** *************** Profile Routes ****************** */
            Route::prefix('profile')->group(function () {
                Route::get('/', [PosProfileController::class, 'userProfile']);
                Route::post('/update', [PosProfileController::class, 'updateProfile']);
            });

            /** *************** Customer Subscription Plans ****************** */
            Route::prefix('subscription-plans')->group(function () {
                Route::get('/', [PosCustomerSubscriptionController::class, 'plans']);
                Route::post('/purchase', [PosCustomerSubscriptionController::class, 'planPurchase']);

                Route::get('/detail', [PosCustomerSubscriptionController::class, 'customerPlanDetail']);
            });

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

                Route::get('/get-receipt', [PosCustomerOrderController::class, 'getReceipt']);
            });

            /**  ************ Joining Fees Related Routes ***************** */
            Route::get('joining/fees/status', [PosJoiningFeesController::class, 'getPaymentStatus']);

            /** ************** Agreement Relate Routes ******************* */
            Route::prefix('agreements')->name('agreements.')->group(function () {
                Route::get('/pending', [PosCustomerAgreementsController::class, 'getPendingAgreements'])->name('pending');
                Route::post('/content', [PosCustomerAgreementsController::class, 'getAgreementContent'])->name('content');
                Route::post('/accept', [PosCustomerAgreementsController::class, 'acceptAgreement'])->name('accept');
            });
        });
    });
});
