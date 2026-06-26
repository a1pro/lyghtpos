<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\PAX\Auth\PaxAuthController;
use App\Http\Controllers\Api\V1\PAX\Merchant\PaxCustomersController;
use App\Http\Controllers\Api\V1\PAX\Merchant\PaxMerchantProfileController;
use App\Http\Controllers\Api\V1\PAX\Merchant\PaxMerchantSettingsController;
use App\Http\Controllers\Api\V1\PAX\Merchant\PaxOrderController;
use App\Http\Controllers\Api\V1\PAX\Merchant\PaxProductsController;
use App\Http\Controllers\Location\LocationController;

Route::prefix('pax')->group(function () {
    Route::post('login', [PaxAuthController::class, 'login']);

    /** *************** Location Routes (Country, State, City) ***************** */
    Route::prefix('locations')->group(function () {
        Route::get('countries', [LocationController::class, 'getCountries']);
        Route::get('states', [LocationController::class, 'getStatesByCountry']);
        Route::get('cities', [LocationController::class, 'getCitiesByState']);
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::prefix('user')->group(function () {
            Route::post('logout', [PaxAuthController::class, 'logout']);

            /** ****************** Product Routes ****************** */
            Route::prefix('products')->name('products.')->group(function () {
                Route::get('/', [PaxProductsController::class, 'getMerchantProducts']);
                Route::get('/categories', [PaxProductsController::class, 'getCategories']);
            });

            /** ****************** Customer Routes ****************** */
            Route::prefix('customers')->name('customers.')->group(function () {
                Route::get('/', [PaxCustomersController::class, 'getMerchantCustomers']);
                Route::post('/add', [PaxCustomersController::class, 'createMerchantCustomer']);
            });

            /** ****************** Order Routes ****************** */
            Route::prefix('orders')->name('orders.')->group(function () {
                Route::post('/', [PaxOrderController::class, 'createCustomerOrder']);
                Route::post('/complete', [PaxOrderController::class, 'completeCustomerOrder']);
                Route::post('/fail', [PaxOrderController::class, 'failCustomerOrder']);

                Route::post('/handpoint', [PaxOrderController::class, 'recordHandpointResponse']);

                Route::get('/get-receipt', [PaxOrderController::class, 'getReceipt']);
            });

            /** ****************** Merchant Settings Routes ****************** */
            Route::prefix('settings')->name('settings')->group(function () {
                Route::get('/', [PaxMerchantSettingsController::class, 'getMerchantSettings']);
            });

            /** ****************** Merchant Profile Routes ****************** */
            Route::prefix('profile')->name('profile')->group(function () {
                Route::get('/', [PaxMerchantProfileController::class, 'userProfile']);
                Route::post('/update-address', [PaxMerchantProfileController::class, 'updateMerchantAddress']);
            });
        });
    });
});
