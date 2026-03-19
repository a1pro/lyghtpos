<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Merchant\TeamMember\Manager\ManagerDashboardController;
use App\Http\Controllers\Merchant\TeamMember\Manager\ManagerMerchantProductController;



Route::prefix('manager')->name('merchant.manager.')->middleware(['auth'])->group(function(){
    Route::get('/',[ManagerDashboardController::class,'index'])->name('index');

    Route::prefix('products')->name('products.')->group(function(){
        Route::get('/', [ManagerMerchantProductController::class, 'index'])->name('index');
        Route::get('create', [ManagerMerchantProductController::class, 'create'])->name('create');
        Route::post('/', [ManagerMerchantProductController::class, 'store'])->name('store');
        Route::get('{product}', [ManagerMerchantProductController::class, 'show'])->name('show');
        Route::get('{product}/edit', [ManagerMerchantProductController::class, 'edit'])->name('edit');
        Route::put('{product}', [ManagerMerchantProductController::class, 'update'])->name('update');
        Route::delete('{product}', [ManagerMerchantProductController::class, 'destroy'])->name('destroy');
        
        // AJAX search route
        Route::get('search/ajax', [ManagerMerchantProductController::class, 'search'])->name('search');
    });
    
});



?>