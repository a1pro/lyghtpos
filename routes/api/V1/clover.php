<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\CLOVER\Auth\CloverAuthController;
use App\Http\Controllers\Api\V1\CLOVER\Merchant\CloverCustomersController;
use App\Http\Controllers\Api\V1\CLOVER\Merchant\CloverMerchantProfileController;
use App\Http\Controllers\Api\V1\CLOVER\Merchant\CloverMerchantSettingsController;
use App\Http\Controllers\Api\V1\CLOVER\Merchant\CloverProductsController;
use App\Http\Controllers\Location\LocationController;

Route::prefix('clover')->group(function () {
    Route::post('login', [CloverAuthController::class, 'login']);

    /** *************** Location Routes (Country, State, City) ***************** */
    Route::prefix('locations')->group(function () {
        Route::get('countries', [LocationController::class, 'getCountries']);
        Route::get('states', [LocationController::class, 'getStatesByCountry']);
        Route::get('cities', [LocationController::class, 'getCitiesByState']);
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::prefix('user')->group(function () {
            Route::post('logout', [CloverAuthController::class, 'logout']);

            /** ****************** Product Routes ****************** */
            Route::prefix('products')->name('products.')->group(function () {
                Route::get('/', [CloverProductsController::class, 'getMerchantProducts']);
                Route::get('/categories', [CloverProductsController::class, 'getCategories']);
            });

            /** ****************** Customer Routes ****************** */
            Route::prefix('customers')->name('customers.')->group(function () {
                Route::get('/', [CloverCustomersController::class, 'getMerchantCustomers']);
                Route::post('/add', [CloverCustomersController::class, 'createMerchantCustomer']);
            });

            /** ****************** Merchant Settings Routes ****************** */
            Route::prefix('settings')->name('settings')->group(function () {
                Route::get('/', [CloverMerchantSettingsController::class, 'getMerchantSettings']);
            });

            /** ****************** Merchant Profile Routes ****************** */
            Route::prefix('profile')->name('profile')->group(function () {
                Route::get('/', [CloverMerchantProfileController::class, 'userProfile']);
                Route::post('/update-address', [CloverMerchantProfileController::class, 'updateMerchantAddress']);
            });
        });
    });
});
