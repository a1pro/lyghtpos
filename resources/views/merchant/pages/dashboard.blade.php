@extends('merchant.layouts.main')

@section('content')



<section class="content merchant-dashboard">
    <div class="container-fluid">

        {{-- Welcome Banner --}}
        <div class="row">
            <div class="col-12">
                <div class="dashboard-welcome-card">
                    <div class="row align-items-center">
                        <div class="col-lg-8 col-md-7">
                            <h2>
                                Welcome Back, {{ auth()->user()->name ?? 'Merchant' }}! 👋
                            </h2>
                            <p>
                                Here's what's happening with your business today.
                            </p>
                        </div>

                        <div class="col-lg-4 col-md-5 text-md-right mt-3 mt-md-0">
                            @if(!empty($showPopup) && !empty($joiningFeeAmount))
                                <a href="{{ route('merchant.payments.create', ['amount' => $joiningFeeAmount]) }}"
                                   class="btn btn-light btn-lg dashboard-payment-btn"
                                   onclick="sessionStorage.setItem('payment_amount', '{{ $joiningFeeAmount }}');">
                                    <i class="fas fa-credit-card mr-2"></i>
                                    Complete Payment ${{ number_format($joiningFeeAmount, 2) }}
                                </a>
                            @elseif(!empty($hasPaidJoiningFee))
                                <div class="dashboard-paid-badge">
                                    <i class="fas fa-check-circle mr-2"></i>
                                    Joining Fee Paid
                                </div>
                            @else
                                <div class="dashboard-no-fee-badge">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    No Joining Fee Required
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Stats Cards --}}
        <div class="row dashboard-stats-row">

            {{-- External Links --}}
       <div class="col-lg-3 col-md-6 col-12">
                <div class="dashboard-card dashboard-card-light">
                    <div class="dashboard-card-body">
                         
                        <p>External Links</p>

                        <div class="dashboard-large-icon">
                           <i class="fa-solid fa-link"></i>
                        </div>
                    </div>

                    <a href="{{ route('merchant.external-links') }}" class="dashboard-card-footer">
                        External Links
                        <i class="fas fa-arrow-circle-right ml-1"></i>
                    </a>
                </div>
            </div>

            {{-- Total Customers --}}
            <div class="col-lg-3 col-md-6 col-12">
                <div class="dashboard-card dashboard-card-primary">
                    <div class="dashboard-card-body">
                        <h3 id="totalCustomers">0</h3>
                        <p>Total Customers</p>

                        <div class="dashboard-large-icon">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>

                    <a href="#" class="dashboard-card-footer">
                        Active customer accounts
                        <i class="fas fa-arrow-circle-right ml-1"></i>
                    </a>
                </div>
            </div>

            {{-- Product Limit --}}
            <div class="col-lg-3 col-md-6 col-12">
                <div class="dashboard-card dashboard-card-success">
                    <div class="dashboard-card-body">
                        <h3 id="totalProductLimitSummary">0</h3>
                        <p>Product Limit</p>

                        <div class="dashboard-large-icon">
                            <i class="fas fa-boxes"></i>
                        </div>
                    </div>

                    <a href="#" class="dashboard-card-footer">
                        Maximum products allowed
                        <i class="fas fa-arrow-circle-right ml-1"></i>
                    </a>
                </div>
            </div>

            {{-- Customer Limit --}}
            <div class="col-lg-3 col-md-6 col-12">
                <div class="dashboard-card dashboard-card-warning">
                    <div class="dashboard-card-body">
                        <h3 id="totalCustomerLimitSummary">0</h3>
                        <p>Customer Limit</p>

                        <div class="dashboard-large-icon">
                            <i class="fas fa-user-plus"></i>
                        </div>
                    </div>

                    <a href="#" class="dashboard-card-footer">
                        Maximum customers allowed
                        <i class="fas fa-arrow-circle-right ml-1"></i>
                    </a>
                </div>
            </div>
        </div>

        {{-- Subscription Plans Info --}}
        <div class="row mt-4">
            <div class="col-12">
                <div class="card card-primary card-outline dashboard-info-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-crown mr-2"></i>
                            Subscription Plans Info
                        </h3>

                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>

                    <div class="card-body">

                        {{-- Customer Analytics --}}
                        <div class="dashboard-section-title text-info">
                            <i class="fas fa-users mr-2"></i>
                            Customer Analytics
                        </div>

                        <div class="row mb-4">
                            <div class="col-lg-4 col-md-6 col-12">
                                <div class="dashboard-info-box">
                                    <span>Total Customer Limit</span>
                                    <strong class="text-info" id="totalCustomerLimit">0</strong>

                                    <div class="progress dashboard-progress">
                                        <div class="progress-bar bg-info" id="customerLimitProgress" style="width: 0%"></div>
                                    </div>

                                    <small>Plan Limit</small>
                                </div>
                            </div>

                            <div class="col-lg-4 col-md-6 col-12">
                                <div class="dashboard-info-box">
                                    <span>Total Customer Added</span>
                                    <strong class="text-success" id="totalCustomersAdded">0</strong>
                                    <small>Active customers in system</small>
                                </div>
                            </div>

                            <div class="col-lg-4 col-md-6 col-12">
                                <div class="dashboard-info-box">
                                    <span>Customer Limit Left</span>
                                    <strong class="text-warning" id="customerLimitLeft">0</strong>
                                    <small>Slots for new customers</small>
                                </div>
                            </div>
                        </div>

                        {{-- Product Analytics --}}
                        <div class="dashboard-section-title text-success">
                            <i class="fas fa-box mr-2"></i>
                            Product Analytics
                        </div>

                        <div class="row">
                            <div class="col-lg-4 col-md-6 col-12">
                                <div class="dashboard-info-box">
                                    <span>Total Product Limit</span>
                                    <strong class="text-primary" id="totalProductLimit">0</strong>

                                    <div class="progress dashboard-progress">
                                        <div class="progress-bar bg-primary" id="productLimitProgress" style="width: 0%"></div>
                                    </div>

                                    <small>Plan Limit</small>
                                </div>
                            </div>

                            <div class="col-lg-4 col-md-6 col-12">
                                <div class="dashboard-info-box">
                                    <span>Total Product Added</span>
                                    <strong class="text-success" id="totalProductsAdded">0</strong>
                                    <small>Active products in inventory</small>
                                </div>
                            </div>

                            <div class="col-lg-4 col-md-6 col-12">
                                <div class="dashboard-info-box">
                                    <span>Total Products Left</span>
                                    <strong class="text-warning" id="productsLimitLeft">0</strong>
                                    <small>Slots for new products</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer text-center text-muted">
                        <small>
                            Monitor your usage and plan limits to optimize your subscription.
                        </small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Joining Fee Alert --}}
        @if(!empty($showPopup) && !empty($joiningFeeAmount))
            <div class="row mt-3">
                <div class="col-12">
                    <div class="alert alert-warning alert-dismissible fade show dashboard-alert" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>

                        <i class="fas fa-rocket mr-2"></i>
                        <strong>Unlock Full Features!</strong>
                        Complete your joining fee payment of
                        <strong>${{ number_format($joiningFeeAmount, 2) }}</strong>
                        to activate all subscription features and start growing your business.
                    </div>
                </div>
            </div>
        @endif

    </div>
</section>

<style>
    .merchant-dashboard-header {
        padding: 15px 0;
    }

    .merchant-dashboard {
        padding-bottom: 30px;
    }

    .dashboard-welcome-card {
        background: linear-gradient(135deg, #4da3e6 0%, #69b5ed 100%);
        color: #ffffff;
        border-radius: 8px;
        padding: 28px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
        margin-bottom: 24px;
    }

    .dashboard-welcome-card h2 {
        font-size: 32px;
        font-weight: 600;
        margin-bottom: 8px;
    }

    .dashboard-welcome-card p {
        font-size: 16px;
        margin-bottom: 0;
    }

    .dashboard-payment-btn {
        font-weight: 600;
        border-radius: 6px;
        white-space: normal;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.12);
    }

    .dashboard-paid-badge,
    .dashboard-no-fee-badge {
        display: inline-block;
        color: #ffffff;
        font-weight: 600;
        padding: 12px 18px;
        border-radius: 6px;
    }

    .dashboard-paid-badge {
        background: rgba(40, 167, 69, 0.9);
    }

    .dashboard-no-fee-badge {
        background: rgba(255, 255, 255, 0.25);
    }

    .dashboard-stats-row {
        margin-top: 5px;
    }

    .dashboard-card {
        position: relative;
        overflow: hidden;
        min-height: 155px;
        border-radius: 8px;
        margin-bottom: 20px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
        transition: 0.25s ease-in-out;
    }

    .dashboard-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 18px rgba(0, 0, 0, 0.14);
    }

    .dashboard-card-body {
        position: relative;
        padding: 18px;
        min-height: 115px;
        z-index: 2;
    }

    .dashboard-card h3 {
        font-size: 34px;
        font-weight: 700;
        margin: 0 0 8px;
        line-height: 1.1;
    }

    .dashboard-card p {
        font-size: 16px;
        margin-bottom: 0;
    }

    .dashboard-card-footer {
        display: block;
        position: relative;
        z-index: 3;
        padding: 8px 12px;
        text-align: center;
        text-decoration: none !important;
        font-size: 14px;
        transition: 0.2s ease-in-out;
    }

    .dashboard-card-primary {
        background: #3498db;
        color: #ffffff;
    }

    .dashboard-card-success {
        background: #2fbf71;
        color: #ffffff;
    }

    .dashboard-card-warning {
        background: #ffeb3b;
        color: #1f2937;
    }

    .dashboard-card-primary .dashboard-card-footer,
    .dashboard-card-success .dashboard-card-footer {
        background: rgba(0, 0, 0, 0.12);
        color: #ffffff;
    }

    .dashboard-card-warning .dashboard-card-footer {
        background: rgba(0, 0, 0, 0.12);
        color: #1f2937;
    }

    .dashboard-card-primary .dashboard-card-footer:hover,
    .dashboard-card-success .dashboard-card-footer:hover,
    .dashboard-card-warning .dashboard-card-footer:hover {
        background: rgba(0, 0, 0, 0.2);
    }

    .dashboard-card-light {
        background: #ffffff;
        color: #1f2937;
    }

    .dashboard-card-light .dashboard-card-footer {
        background: #e9ecef;
        color: #343a40;
    }

    .dashboard-card-light .dashboard-card-footer:hover {
        background: #dde1e5;
        color: #111827;
    }

    .dashboard-card-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 14px;
    }

    .dashboard-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
    }

    .dashboard-icon-info {
        background: rgba(52, 152, 219, 0.15);
        color: #3498db;
    }

    .dashboard-badge {
        display: inline-block;
        padding: 5px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 600;
    }

    .dashboard-badge-info {
        background: #5dade2;
        color: #ffffff;
    }

    .dashboard-large-icon {
        position: absolute;
        right: 18px;
        top: 25px;
        font-size: 56px;
        opacity: 0.22;
        z-index: 1;
    }

    .dashboard-info-card {
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
    }

    .dashboard-section-title {
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 14px;
    }

    .dashboard-info-box {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        min-height: 145px;
        margin-bottom: 20px;
        text-align: center;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
        transition: 0.25s ease-in-out;
    }

    .dashboard-info-box:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 14px rgba(0, 0, 0, 0.12);
    }

    .dashboard-info-box span {
        display: block;
        color: #6c757d;
        text-transform: uppercase;
        font-size: 13px;
        font-weight: 700;
        margin-bottom: 8px;
    }

    .dashboard-info-box strong {
        display: block;
        font-size: 32px;
        line-height: 1.2;
        margin-bottom: 8px;
    }

    .dashboard-info-box small {
        display: block;
        color: #6c757d;
        font-size: 12px;
    }

    .dashboard-progress {
        height: 5px;
        margin: 12px 0 8px;
        border-radius: 10px;
    }

    .dashboard-progress .progress-bar {
        transition: width 0.6s ease;
    }

    .dashboard-alert {
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
    }

    @media (max-width: 991px) {
        .dashboard-welcome-card h2 {
            font-size: 26px;
        }

        .dashboard-card h3 {
            font-size: 28px;
        }
    }

    @media (max-width: 575px) {
        .merchant-dashboard-header h1 {
            font-size: 24px;
        }

        .dashboard-welcome-card {
            padding: 22px;
        }

        .dashboard-welcome-card h2 {
            font-size: 23px;
        }

        .breadcrumb.float-sm-right {
            float: none !important;
            margin-top: 8px;
        }
    }
</style>

<script>
    const subscriptionData = {
        totalCustomerLimit: {{ $customers['data']['customerLimit'] ?? NULL }},
        totalCustomersAdded: {{ $customers['data']['totalUser'] ?? NULL }},
        totalProductLimit: {{ $products['data']['productLimit'] ?? NULL }},
        totalProductsAdded: {{ $products['data']['currentproductcount'] ?? NULL }},
        totalCustomers: {{ $customers['data']['customerCount'] ?? NULL }},
    };

    function formatNumber(num) {
        return Number(num || 0).toLocaleString();
    }

    function setText(id, value) {
        const element = document.getElementById(id);

        if (element) {
            element.textContent = formatNumber(value);
        }
    }

    function setProgress(id, current, total) {
        const element = document.getElementById(id);

        if (!element) {
            return;
        }

        const percentage = total > 0 ? Math.min((current / total) * 100, 100) : 0;
        element.style.width = percentage + '%';
    }

    function updateDashboard() {
        const customerLeft = Math.max(
            0,
            subscriptionData.totalCustomerLimit - subscriptionData.totalCustomersAdded
        );

        const productLeft = Math.max(
            0,
            subscriptionData.totalProductLimit - subscriptionData.totalProductsAdded
        );

        setText('totalCustomers', subscriptionData.totalCustomers);
        setText('totalProductLimitSummary', subscriptionData.totalProductLimit);
        setText('totalCustomerLimitSummary', subscriptionData.totalCustomerLimit);

        setText('totalCustomerLimit', subscriptionData.totalCustomerLimit);
        setText('totalCustomersAdded', subscriptionData.totalCustomersAdded);
        setText('customerLimitLeft', customerLeft);

        setText('totalProductLimit', subscriptionData.totalProductLimit);
        setText('totalProductsAdded', subscriptionData.totalProductsAdded);
        setText('productsLimitLeft', productLeft);

        setProgress(
            'customerLimitProgress',
            subscriptionData.totalCustomersAdded,
            subscriptionData.totalCustomerLimit
        );

        setProgress(
            'productLimitProgress',
            subscriptionData.totalProductsAdded,
            subscriptionData.totalProductLimit
        );
    }

    document.addEventListener('DOMContentLoaded', function () {
        updateDashboard();

        @if(!empty($showPopup) && !empty($joiningFeeAmount))
            sessionStorage.setItem('payment_amount', '{{ $joiningFeeAmount }}');
        @endif
    });
</script>

@endsection