<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\WP\Auth\WpRegisterController;
use App\Http\Controllers\Api\V1\WP\Merchant\WpRepresentationController;



Route::prefix('wp')->group(function () {
    Route::post('register', [WpRegisterController::class, 'register']);
    Route::post('check-email', [WpRegisterController::class, 'checkEmail']);
    Route::get('/representation/status/{userId}', [WpRepresentationController::class, 'status']);

});


 



?>