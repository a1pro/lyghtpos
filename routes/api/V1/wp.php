<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\WP\Auth\WpRegisterController;




Route::prefix('wp')->group(function () {
    Route::post('register', [WpRegisterController::class, 'register']);
    Route::post('check-email', [WpRegisterController::class, 'checkEmail']);
});



?>