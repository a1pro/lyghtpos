<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Merchant\MerchantDashboardController;



Route::prefix('merchant')->name('merchant.')->middleware(['auth', 'locale'])->group(function (): void {

Route::get('/',[MerchantDashboardController::class, 'index'])->name('dashboard');


});