<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\PAX\Auth\PaxAuthController;
use App\Http\Controllers\Api\V1\PAX\Merchant\PaxCustomersController;
use App\Http\Controllers\Api\V1\PAX\Merchant\PaxProductsController;

Route::prefix('pax')->group(function () {
    Route::post('login', [PaxAuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::prefix('user')->group(function () {

            /** ****************** Product Routes ****************** */
            Route::prefix('products')->name('products.')->group(function () {
                Route::get('/', [PaxProductsController::class, 'getMerchantProducts']);
            });

            /** ****************** Customer Routes ****************** */
            Route::prefix('customers')->name('customers.')->group(function () {
                Route::get('/', [PaxCustomersController::class, 'getMerchantCustomers']);
                Route::post('/add', [PaxCustomersController::class, 'createMerchantCustomer']);
            });
        });
    });
});
