<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\PAX\Auth\PaxAuthController;



Route::prefix('v1/pax')->group(function(){
Route::post('login',[PaxAuthController::class,'login']);
});








?>