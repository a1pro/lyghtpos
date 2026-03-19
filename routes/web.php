<?php

use App\Http\Controllers\Admin\GovernanceLayerController;
use App\Http\Controllers\Admin\HomeController;
use App\Http\Controllers\Inventory\ProductController;
use App\Http\Controllers\Inventory\PurchaseCartController;
use App\Http\Controllers\Inventory\PurchaseController;
use App\Http\Controllers\Management\CustomerController;
use App\Http\Controllers\Management\SupplierController;
use App\Http\Controllers\Pos\CartController;
use App\Http\Controllers\Pos\OrderController;
use App\Http\Controllers\Settings\SettingController;
use App\Http\Controllers\Admin\AdminMerchantController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('dashboard');

// Translations
Route::get('/locale/{type}', function ($type) {
    $translations = trans($type);
    return response()->json($translations);
});

// Custom registration routes for 2-step registration
Route::get('register', [App\Http\Controllers\Auth\RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('register', [App\Http\Controllers\Auth\RegisterController::class, 'register'])->name('register.step1');
Route::get('register/representation', [App\Http\Controllers\Auth\RegisterController::class, 'showRepresentationForm'])->name('register.representation');
Route::post('register/final', [App\Http\Controllers\Auth\RegisterController::class, 'register'])->name('register.final');

Auth::routes();

Route::prefix('admin')->middleware(['auth', 'locale', 'role:admin'])->group(function (): void {
    Route::get('/', HomeController::class)->name('home');
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingController::class, 'store'])->name('settings.store');
    Route::resource('products', ProductController::class);
    Route::resource('customers', CustomerController::class);
    Route::resource('orders', OrderController::class);
    Route::resource('suppliers', SupplierController::class);

    // ===================================Merchant List===========================================================

Route::prefix('merchants')->name('admin.merchants.')->group(function(){
    Route::get('/merchants/{id}/change-password', [AdminMerchantController::class, 'showChangePasswordForm'])->name('change-password');
    Route::post('/merchants/{id}/update-password', [AdminMerchantController::class, 'updatePassword'])->name('update-password');
    Route::get('/', [AdminMerchantController::class, 'renderMerchants'])->name('index');
});

    // =============================================End===========================================================

    // ===================================Vouchers===========================================================

    Route::prefix('vouchers')->name('admin.vouchers.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\AdminVoucherController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\Admin\AdminVoucherController::class, 'create'])->name('create');
        Route::post('/generate', [App\Http\Controllers\Admin\AdminVoucherController::class, 'generate'])->name('generate');
        Route::get('/{voucherId}', [App\Http\Controllers\Admin\AdminVoucherController::class, 'show'])->name('show');
        
        // Expiration routes
        Route::get('/{voucherId}/edit-expiration', [App\Http\Controllers\Admin\AdminVoucherController::class, 'editExpiration'])->name('edit-expiration');
        Route::post('/{voucherId}/change-expiration', [App\Http\Controllers\Admin\AdminVoucherController::class, 'changeExpiration'])->name('change-expiration');
        
        // Cancel route (AJAX)
        Route::post('/{voucherId}/cancel', [App\Http\Controllers\Admin\AdminVoucherController::class, 'cancel'])->name('cancel');
    });
    
    // Governance Layers
    Route::get('/governance-layers', [GovernanceLayerController::class, 'index'])->name('admin.governance-layers');
    Route::get('/governance-layers/create', [GovernanceLayerController::class, 'create'])->name('admin.governance-layers.create');
    Route::post('/governance-layers/create', [GovernanceLayerController::class, 'store'])->name('admin.governance-layers.store');
    Route::get('/governance-layers/{governanceLayer}/edit', [GovernanceLayerController::class, 'edit'])->name('admin.governance-layers.edit');
    Route::put('/governance-layers/{governanceLayer}', [GovernanceLayerController::class, 'update'])->name('admin.governance-layers.update');
    Route::delete('/governance-layers/{governanceLayer}', [GovernanceLayerController::class, 'destroy'])->name('admin.governance-layers.destroy');

    // POS Cart
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart', [CartController::class, 'store'])->name('cart.store');
    Route::post('/cart/change-qty', [CartController::class, 'changeQty']);
    Route::delete('/cart/delete', [CartController::class, 'delete']);
    Route::delete('/cart/empty', [CartController::class, 'empty']);

    Route::get('/purchases/data', [PurchaseController::class, 'data'])->name('purchases.data');
    Route::get('/purchases/{purchase}/receipt', [PurchaseController::class, 'receipt'])->name('purchases.receipt');
    Route::resource('purchases', PurchaseController::class);

    // Purchase Cart API
    Route::prefix('purchase-cart')->name('purchase-cart.')->group(function (): void {
        Route::get('/', [PurchaseCartController::class, 'index'])->name('index');
        Route::post('/', [PurchaseCartController::class, 'store'])->name('store');
        Route::post('/change-qty', [PurchaseCartController::class, 'changeQty'])->name('change-qty');
        Route::post('/change-price', [PurchaseCartController::class, 'changePrice'])->name('change-price');
        Route::delete('/delete', [PurchaseCartController::class, 'delete'])->name('delete');
        Route::delete('/empty', [PurchaseCartController::class, 'empty'])->name('empty');
    });

    // Orders
    Route::post('/orders/partial-payment', [OrderController::class, 'partialPayment'])->name('orders.partial-payment');

    // Translations
    Route::get('/locale/{type}', function ($type) {
        $translations = trans($type);
        return response()->json($translations);
    });

    // Language Switch
    Route::get('/lang-switch/{lang}', function ($lang) {
        $supportedLocales = ['en', 'es'];

        if (in_array($lang, $supportedLocales)) {
            session(['locale' => $lang]);
            app()->setLocale($lang);
        }

        return redirect()->back();
    })->name('lang.switch');
});


require __DIR__ . '/merchant.php';
require __DIR__ . '/subadmin.php';
require __DIR__ . '/favor-member.php';
require __DIR__ . '/team-member/merchant/manager.php';
require __DIR__ . '/team-member/merchant/cashier.php';

