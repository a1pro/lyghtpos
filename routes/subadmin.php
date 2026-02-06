<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Subadmin\SubadminDashboardController;
use App\Http\Controllers\ProchargePaymentController;

Route::prefix('subadmin')->name('subadmin.')->middleware(['auth', 'locale', 'role:subadmin'])->group(function (): void {

    Route::get('/', [SubadminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/users', [SubadminDashboardController::class, 'renderUsers'])->name('users');

    Route::get('/users/representation/{userId}', [SubadminDashboardController::class, 'renderUserRepresentation'])->name('user.representation');
});
