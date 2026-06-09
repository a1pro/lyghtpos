<?php

use App\Http\Controllers\Auth\EmailVerificationController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Merchant\MerchantDashboardController;
use App\Http\Controllers\Merchant\MerchantRepresentationController;
use App\Http\Controllers\Merchant\PlanController;
use App\Http\Controllers\Merchant\TeamMemberController;
use App\Http\Controllers\Inventory\ProductController;
use App\Http\Controllers\Inventory\PurchaseCartController;
use App\Http\Controllers\Inventory\PurchaseController;
use App\Http\Controllers\Management\SupplierController;
use App\Http\Controllers\Merchant\AgreementController;
use App\Http\Controllers\Merchant\CheckoutController;
use App\Http\Controllers\Merchant\CustomerController;
use App\Http\Controllers\Merchant\MerchantCartController;
use App\Http\Controllers\Merchant\MerchantOrderController;
use App\Http\Controllers\OrganizationAdmin\OrganizationAdminDashboardController;
use App\Http\Controllers\Settings\SettingController;
use App\Http\Controllers\Merchant\PaymentController;
use App\Http\Controllers\TradeMember\TradeMemberDashboardController;
use App\Http\Controllers\Merchant\MerchantProductController;
use App\Http\Controllers\Merchant\MerchantProductImportController;
use App\Http\Controllers\Merchant\WalletController;
use App\Http\Controllers\Merchant\CategoryController;
use App\Http\Controllers\Merchant\HandpointLogController;
use App\Http\Controllers\Merchant\MerchantAdvertisementController;
use App\Http\Controllers\Merchant\MerchantProfileController;
use App\Http\Controllers\Merchant\MerchantManageSubscriptionController;
use App\Http\Controllers\Merchant\MerchantSettingController;


// Merchant Email Verification



Route::get('/get-states/{countryId}', [MerchantProfileController::class, 'getStates'])->name('get.states');
Route::get('/get-cities/{stateId}', [MerchantProfileController::class, 'getCities'])->name('get.cities');

Route::prefix('merchant')->name('merchant.')->middleware(['auth', 'locale', 'role:merchant'])->group(function (): void {
    Route::prefix('verify')->name('verify.')->controller(EmailVerificationController::class)->group(function () {
        Route::get('/email', 'showVerificationForm')->name('email');
        Route::post('/send-otp', 'sendOtp')->name('send-otp');
        Route::post('/resend-otp', 'resendOtp')->name('resend-otp');
        Route::post('/verify-otp', 'verifyOtp')->name('verify-otp');
        Route::get('/status', 'checkStatus')->name('status');
    });

    Route::prefix('agreements')->name('agreements.')->group(function () {
        Route::get('/', [AgreementController::class, 'index'])->name('index');
        Route::get('/content/{agreementId}', [AgreementController::class, 'getContent'])->name('content');
        Route::post('/accept', [AgreementController::class, 'accept'])->name('accept');
    });
});

Route::prefix('merchant')->name('merchant.')->middleware(['auth', 'locale', 'role:merchant', 'email.verified'])->group(function (): void {

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
Route::prefix('merchant')->name('merchant.')->middleware(['auth', 'locale', 'role:merchant', 'email.verified', 'subscription', 'representation', 'check.agreements'])->group(function (): void {

    // *********************** Single Routes ***********************
    Route::get('/', [MerchantDashboardController::class, 'index'])->name('dashboard');
    Route::get('/external-links', [MerchantDashboardController::class, 'externalLinks'])->name('external-links');

    Route::get('/barter-assist-cash-price', [MerchantDashboardController::class, 'showBarterAssistCashPrice'])->name('barter-assist-cash-price');
    Route::get('/barter-assist-pos', [MerchantDashboardController::class, 'showBarterAssistPos'])->name('barter-assist-pos');

    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingController::class, 'store'])->name('settings.store');

    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [MerchantProfileController::class, 'index'])->name('index');
        Route::post('/update', [MerchantProfileController::class, 'updateProfile'])->name('update');
        Route::put('/address/update', [MerchantProfileController::class, 'updateAddress'])->name('address.update');

        Route::get('/get-states/{countryId}', [MerchantProfileController::class, 'getStates'])->name('get-states');
        Route::get('/get-cities/{stateId}', [MerchantProfileController::class, 'getCities'])->name('get-cities');

        // Settings
        Route::put('/settings', [MerchantProfileController::class, 'updateSettings'])->name('settings.update');
    });

    Route::prefix('representation')->name('representation.')->group(function (): void {
        Route::get('/form', [MerchantRepresentationController::class, 'showForm'])->name('form');
        Route::post('/submit', [MerchantRepresentationController::class, 'submitForm'])->name('submit');
    });

    /* 
    Manage Subscription Routes
    */

    Route::prefix('manage-subscriptions')->name('manage-subscriptions.')->group(function () {
        Route::get('/', [MerchantManageSubscriptionController::class, 'index'])->name('index');
        Route::get('/plan-details/{planId}', [MerchantManageSubscriptionController::class, 'getPlanDetails'])->name('plan.details');
        Route::post('/process-plan-change', [MerchantManageSubscriptionController::class, 'processPlanChange'])->name('process.plan.change');
        Route::get('/paypal/success', [MerchantManageSubscriptionController::class, 'paypalSuccess'])->name('paypal.success');
        Route::get('/paypal/cancel', [MerchantManageSubscriptionController::class, 'paypalCancel'])->name('paypal.cancel');
        Route::get('/history', [MerchantManageSubscriptionController::class, 'history'])->name('history');

    });

    // *********************** Product Routes ***********************
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/', [MerchantProductController::class, 'index'])->name('index');
        Route::get('create', [MerchantProductController::class, 'create'])->name('create');
        Route::post('/', [MerchantProductController::class, 'store'])->name('store');
        Route::get('{product}', [MerchantProductController::class, 'show'])->name('show');
        Route::get('{product}/edit', [MerchantProductController::class, 'edit'])->name('edit');
        Route::put('{product}', [MerchantProductController::class, 'update'])->name('update');
        Route::delete('{product}', [MerchantProductController::class, 'destroy'])->name('destroy');

        // AJAX search route
        Route::get('search/ajax', [MerchantProductController::class, 'search'])->name('search');

        // Import flow
        Route::prefix('import')->name('import.')->group(function () {
            Route::get('/history', [MerchantProductImportController::class, 'index'])->name('index');
            Route::get('/form', [MerchantProductImportController::class, 'create'])->name('create');
            Route::post('/form', [MerchantProductImportController::class, 'store'])->name('store');
            Route::get('/search/{id}', [MerchantProductImportController::class, 'show'])->name('show');

            // Additional API routes for real-time features
            Route::get('/progress/{cyclosImportId}', [MerchantProductImportController::class, 'progress'])->name('progress');
            Route::delete('/cancel/{cyclosImportId}', [MerchantProductImportController::class, 'cancel'])->name('cancel');
            Route::post('/retry/{cyclosImportId}', [MerchantProductImportController::class, 'retryFailed'])->name('retry');
            Route::get('/download-errors/{cyclosImportId}', [MerchantProductImportController::class, 'downloadErrors'])->name('download-errors');
        });
    });

    // *********************** Advertisements (simple) Routes ***********************
    Route::prefix('advertisements')->name('advertisements.')->group(function () {
        Route::get('/', [MerchantAdvertisementController::class, 'index'])->name('index');
        Route::get('create', [MerchantAdvertisementController::class, 'create'])->name('create');
        Route::post('/', [MerchantAdvertisementController::class, 'store'])->name('store');
        Route::get('{advertisement}/edit', [MerchantAdvertisementController::class, 'edit'])->name('edit');
        Route::put('{product}', [MerchantAdvertisementController::class, 'update'])->name('update');
        Route::delete('{advertisement}', [MerchantAdvertisementController::class, 'destroy'])->name('destroy');
    });

    // *********************** Categories Routes ***********************
    Route::prefix('categories')->name('categories.')->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->name('index');
        Route::post('/refresh', [CategoryController::class, 'refresh'])->name('refresh');
        Route::get('/search', [CategoryController::class, 'search'])->name('search');
        Route::get('/export', [CategoryController::class, 'export'])->name('export');
    });

    // *********************** Wallet Routes ***********************
    Route::prefix('wallet')->name('wallet.')->group(function () {

        // Dashboard and overview
        Route::get('/', [WalletController::class, 'index'])->name('index');
        Route::get('/dashboard', [WalletController::class, 'dashboard'])->name('dashboard');

        // Payout routes
        Route::get('/payout', [WalletController::class, 'showPayoutForm'])->name('payout');
        Route::post('/payout', [WalletController::class, 'requestPayout'])->name('request-payout');
        Route::get('/payout/{payout}', [WalletController::class, 'payoutDetails'])->name('payout.details');
        Route::delete('/payout/{payout}', [WalletController::class, 'cancelPayout'])->name('payout.cancel');

        // History and transactions
        Route::get('/history', [WalletController::class, 'history'])->name('history');
        Route::get('/transactions', [WalletController::class, 'transactions'])->name('transactions');

        // AJAX endpoints
        Route::get('/balance', [WalletController::class, 'getBalance'])->name('balance');
        Route::get('/transactions/ajax', [WalletController::class, 'getTransactions'])->name('transactions.ajax');
        Route::get('/payouts/ajax', [WalletController::class, 'getPayouts'])->name('payouts.ajax');

        // Reports
        Route::get('/report', [WalletController::class, 'report'])->name('report');
        Route::get('/export', [WalletController::class, 'export'])->name('export');
    });

    // *********************** Team Member Routes ***********************
    Route::prefix('team-members')->name('team-members.')->group(function () {
        Route::get('/', [TeamMemberController::class, 'index'])->name('index');
        Route::get('/create', [TeamMemberController::class, 'create'])->name('create');
        Route::post('/', [TeamMemberController::class, 'store'])->name('store');
        Route::get('/{teamMember}/edit', [TeamMemberController::class, 'edit'])->name('edit');
        Route::put('/{teamMember}', [TeamMemberController::class, 'update'])->name('update');
        Route::delete('/{teamMember}', [TeamMemberController::class, 'destroy'])->name('destroy');
    });

    // *********************** Voucher Routes ***********************
    Route::prefix('vouchers')->name('vouchers.')->group(function () {
        Route::get('/', [App\Http\Controllers\Merchant\VoucherController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\Merchant\VoucherController::class, 'create'])->name('create');
        Route::post('/generate', [App\Http\Controllers\Merchant\VoucherController::class, 'generate'])->name('generate');
        Route::post('/{voucherId}/change-expiration', [App\Http\Controllers\Merchant\VoucherController::class, 'changeExpiration'])->name('change-expiration');
        Route::post('/{voucherId}/cancel', [App\Http\Controllers\Merchant\VoucherController::class, 'cancel'])->name('cancel');
    });

    Route::resource('customers', CustomerController::class)->names('customers');
    Route::resource('suppliers', SupplierController::class)->names('suppliers');

    // =============================================
    // Cart Routes (Preserved and Extended)
    // =============================================
    // In your merchant routes group
    Route::prefix('cart')->name('cart.')->group(function () {
        Route::get('/', [MerchantCartController::class, 'index'])->name('index');
        Route::post('/', [MerchantCartController::class, 'store'])->name('store');
        Route::post('/change-qty', [MerchantCartController::class, 'changeQty']);
        Route::delete('/delete', [MerchantCartController::class, 'delete']);
        Route::delete('/empty', [MerchantCartController::class, 'empty']);
        Route::get('/customers', [MerchantCartController::class, 'getCustomers']);
        Route::get('/checkout', [MerchantCartController::class, 'checkout'])->name('checkout');
        // Route::post('/payment/process', [MerchantCartController::class, 'processPayment'])->name('payment.process');
    });

    // =============================================
    // Transaction Routes (New)
    // =============================================
    // Route::prefix('transactions')->name('transactions.')->controller(TransactionController::class)->group(function () {
    //     // List all transactions
    //     Route::get('/', 'index')->name('index');

    //     // Get transaction summary/stats
    //     Route::get('/summary', 'summary')->name('summary');

    //     // View single transaction
    //     Route::get('/{transactionId}', 'show')->name('show');

    //     // Download transaction receipt
    //     Route::get('/{transactionId}/receipt', 'receipt')->name('receipt');
    // });

    // =============================================
    // Checkout Routes (Existing)
    // =============================================
    Route::get('/checkout/data', [CheckoutController::class, 'getCheckoutData']);
    Route::post('/checkout/process', [CheckoutController::class, 'processCheckout']);

    // =============================================
    // Purchase Routes (Existing)
    // =============================================
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
        Route::get('/{order}', [MerchantOrderController::class, 'show'])->name('show');

        Route::get('/{orderId}/receipt', [MerchantOrderController::class, 'getReceipt'])->name('receipt');
    });

    Route::prefix('handpoint-logs')->name('handpoint-logs.')->group(function () {
        Route::get('/', [HandpointLogController::class, 'index'])->name('index');
        Route::get('/{id}', [HandpointLogController::class, 'show'])->name('show');
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
