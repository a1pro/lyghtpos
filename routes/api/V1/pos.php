<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\POS\Auth\LoginController;

Route::post('check', [LoginController::class, 'login'])->name('pos.login');




?>