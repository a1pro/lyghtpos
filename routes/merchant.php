<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Merchant\MerchantDashboardController;
use App\Http\Controllers\Merchant\MerchantRepresentationController;
use App\Http\Controllers\Merchant\PlanController;
use App\Http\Controllers\Admin\HomeController;
use App\Http\Controllers\Merchant\TeamMemberController;
use App\Http\Controllers\Inventory\ProductController;
use App\Http\Controllers\Inventory\PurchaseCartController;
use App\Http\Controllers\Inventory\PurchaseController;
use App\Http\Controllers\Management\CustomerController;
use App\Http\Controllers\Management\SupplierController;
use App\Http\Controllers\Merchant\CheckoutController;
use App\Http\Controllers\Merchant\MerchantCartController;
use App\Http\Controllers\Merchant\MerchantOrderController;
use App\Http\Controllers\OrganizationAdmin\OrganizationAdminDashboardController;
use App\Http\Controllers\Settings\SettingController;
use Illuminate\Http\RedirectResponse;
use App\Http\Controllers\ProchargePaymentController;
use App\Http\Controllers\Merchant\PaymentController;
use App\Http\Controllers\TradeMember\TradeMemberDashboardController;
use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;
use App\Http\Controllers\Merchant\MerchantProductController;




Route::prefix('merchant')->name('merchant.')->middleware(['auth', 'locale', 'role:merchant'])->group(function (): void {

    // Subscription 
    Route::prefix('payments')->name('payments.')->controller(PaymentController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        // Make payment form
        Route::get('/create', 'create')->name('create');
        // Redirect to Authorize.net hosted page
        Route::post('/hosted-redirect', 'redirectToHostedPage')->name('hosted-redirect');
        // Process payment for backward compatibility
        Route::post('/process', 'process')->name('process');
        // Payment history
        Route::get('/history', 'history')->name('history');
        // Return from Authorize.net
        Route::get('/return', 'handleReturn')->name('return');
        // Cancel from Authorize.net
        Route::get('/cancel', 'handleCancel')->name('cancel');
        // View single payment
        Route::get('/{id}', 'show')->name('show');
    });

    Route::prefix('products')->name('products.')->group(function(){
        Route::get('/', [MerchantProductController::class, 'index'])->name('index');
        Route::get('create', [MerchantProductController::class, 'create'])->name('create');
        Route::post('/', [MerchantProductController::class, 'store'])->name('store');
        Route::get('{product}', [MerchantProductController::class, 'show'])->name('show');
        Route::get('{product}/edit', [MerchantProductController::class, 'edit'])->name('edit');
        Route::put('{product}', [MerchantProductController::class, 'update'])->name('update');
        Route::delete('{product}', [MerchantProductController::class, 'destroy'])->name('destroy');
        
        // AJAX search route
        Route::get('search/ajax', [MerchantProductController::class, 'search'])->name('search');
    });

    Route::prefix('team-members')->name('team-members.')->group(function () {
        Route::get('/', [TeamMemberController::class, 'index'])->name('index');
        Route::get('/create', [TeamMemberController::class, 'create'])->name('create');
        Route::post('/', [TeamMemberController::class, 'store'])->name('store');
        Route::get('/{teamMember}/edit', [TeamMemberController::class, 'edit'])->name('edit');
        Route::put('/{teamMember}', [TeamMemberController::class, 'update'])->name('update');
        Route::delete('/{teamMember}', [TeamMemberController::class, 'destroy'])->name('destroy');
    });


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
  
    Route::resource('customers', CustomerController::class)->names('customers');
    Route::resource('suppliers', SupplierController::class)->names('suppliers');

    Route::get('/cart', [MerchantCartController::class, 'index'])->name('cart.index');
    Route::post('/cart', [MerchantCartController::class, 'store'])->name('cart.store');
    Route::post('/cart/change-qty', [MerchantCartController::class, 'changeQty']);
    Route::delete('/cart/delete', [MerchantCartController::class, 'delete']);
    Route::delete('/cart/empty', [MerchantCartController::class, 'empty']);

    Route::get('/cart/checkout', [MerchantCartController::class, 'checkout'])->name('cart.checkout');

    Route::get('/checkout/data', [CheckoutController::class, 'getCheckoutData']);
    Route::post('/checkout/process', [CheckoutController::class, 'processCheckout']);

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

    // Order routes
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [MerchantOrderController::class, 'index'])->name('index');
    });
});

/** **************** TRADE MEMBER ************************ */
Route::prefix('trade-member')->name('trade-member.')->middleware(['auth', 'locale', 'role:trade_member'])->group(function (): void {

    Route::get('/', [TradeMemberDashboardController::class, 'index'])->name('dashboard');
});

/** **************** ORGANIZATION ************************ */
Route::prefix('organization')->name('organization-admin.')->middleware(['auth', 'locale', 'role:organization'])->group(function (): void {

    Route::get('/', [OrganizationAdminDashboardController::class, 'index'])->name('dashboard');
});

/** **************** FAVOR MEMBER ************************ */
