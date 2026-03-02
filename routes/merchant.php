<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Merchant\MerchantDashboardController;
use App\Http\Controllers\Merchant\MerchantRepresentationController;
use App\Http\Controllers\Merchant\PlanController;
use App\Http\Controllers\Admin\HomeController;
use App\Http\Controllers\FavorMember\FavorMemberDashboardController;
use App\Http\Controllers\Inventory\ProductController;
use App\Http\Controllers\Inventory\PurchaseCartController;
use App\Http\Controllers\Inventory\PurchaseController;
use App\Http\Controllers\Management\CustomerController;
use App\Http\Controllers\Management\SupplierController;
use App\Http\Controllers\OrganizationAdmin\OrganizationAdminDashboardController;
use App\Http\Controllers\Pos\CartController;
use App\Http\Controllers\Pos\OrderController;
use App\Http\Controllers\Settings\SettingController;
use Illuminate\Http\RedirectResponse;
use App\Http\Controllers\ProchargePaymentController;
use App\Http\Controllers\TradeMember\TradeMemberDashboardController;

// Public merchant routes (no subscription/representation required)
 
// routes/web.php or routes/merchant.php

Route::prefix('merchant')->name('merchant.')->middleware(['auth', 'locale', 'role:merchant'])->group(function (): void {

    // Subscription routes
    Route::prefix('subscription')->name('subscription.')->group(function () {
        Route::get('/plans', [PlanController::class, 'index'])->name('plans');
        Route::get('/success', [PlanController::class, 'subscriptionSuccess'])->name('success');
        
        // PayPal routes
        Route::post('/paypal/create', [PlanController::class, 'processPayPalPayment'])->name('paypal.create');
        Route::get('/paypal/success', [PlanController::class, 'paypalSuccess'])->name('paypal.success');
        Route::get('/paypal/cancel', [PlanController::class, 'paypalCancel'])->name('paypal.cancel');
        Route::post('/paypal/ipn', [PlanController::class, 'paypalIpn'])->name('paypal.ipn');
        
        // AJAX routes
        Route::get('/plan-details/{planId}', [PlanController::class, 'getPlanDetails'])->name('plan.details');
    });
});

// Protected merchant routes (require subscription and representation)
Route::prefix('merchant')->name('merchant.')->middleware(['auth', 'locale', 'role:merchant', 'subscription', 'representation'])->group(function (): void {

    Route::get('/', [MerchantDashboardController::class, 'index'])->name('dashboard');
    Route::get('/external-links', [MerchantDashboardController::class, 'externalLinks'])->name('external-links');

    Route::get('/barter-assist-cash-price', [MerchantDashboardController::class, 'showBarterAssistCashPrice'])->name('barter-assist-cash-price');
    Route::get('/barter-assist-pos', [MerchantDashboardController::class, 'showBarterAssistPos'])->name('barter-assist-pos');

    Route::prefix('representation')->name('representation.')->group(function (): void {
        Route::get('/form', [MerchantRepresentationController::class, 'showForm'])->name('form');
        Route::post('/submit', [MerchantRepresentationController::class, 'submitForm'])->name('submit');
    });
    // In routes/web.php or routes/merchant.php
   
    // Voucher routes
    Route::prefix('vouchers')->name('vouchers.')->group(function () {
        Route::get('/', [App\Http\Controllers\Merchant\VoucherController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\Merchant\VoucherController::class, 'create'])->name('create');
        Route::post('/generate', [App\Http\Controllers\Merchant\VoucherController::class, 'generate'])->name('generate');
        Route::post('/{voucherId}/change-expiration', [App\Http\Controllers\Merchant\VoucherController::class, 'changeExpiration'])->name('change-expiration');
        Route::post('/{voucherId}/cancel', [App\Http\Controllers\Merchant\VoucherController::class, 'cancel'])->name('cancel');
    });

    
 

    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingController::class, 'store'])->name('settings.store');
    Route::resource('products', ProductController::class)->names('products');
    Route::resource('customers', CustomerController::class)->names('customers');
    Route::resource('orders', OrderController::class)->names('orders');
    Route::resource('suppliers', SupplierController::class)->names('suppliers');

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
});

Route::prefix('trade-member')->name('trade-member.')->middleware(['auth', 'locale', 'role:trade_member'])->group(function (): void {

    Route::get('/', [TradeMemberDashboardController::class, 'index'])->name('dashboard');
});

Route::prefix('organization')->name('organization-admin.')->middleware(['auth', 'locale', 'role:organization'])->group(function (): void {

    Route::get('/', [OrganizationAdminDashboardController::class, 'index'])->name('dashboard');
});

Route::prefix('favor-member')->name('favor-member.')->middleware(['auth', 'locale', 'role:favor_member'])->group(function (): void {

    Route::get('/', [FavorMemberDashboardController::class, 'index'])->name('dashboard');
});

// for testing outside auth
// Route::prefix('merchant')->name('merchant.')->group(function (): void {
//     Route::get('/procharge/intent', [ProchargePaymentController::class, 'createIntent']);
// });