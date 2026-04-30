@extends('merchant.layouts.main')

@section('title', 'Manage Subscriptions')

@section('content-header', 'Subscription Plans')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="text-center flex-grow-1">
                            <h3 class="fw-bold mb-0">Choose your plan</h3>
                            <p class="text-muted mb-0">Select the perfect plan for your business needs</p>
                        </div>
                        <div>
                            <a href="{{ route('merchant.manage-subscriptions.history') }}" class="btn btn-outline-info">
                                <i class="fas fa-history me-1"></i> View History
                            </a>
                        </div>
                    </div>

                    <!-- Pricing Toggle (Monthly/Yearly) -->
                    <div class="d-flex justify-content-center mb-4">
                        <div class="btn-group" role="group" aria-label="Billing period">
                            <button type="button" class="btn btn-primary active" id="monthlyBtn" data-billing="monthly">Monthly</button>
                            <button type="button" class="btn btn-outline-primary" id="yearlyBtn" data-billing="yearly">Yearly</button>
                        </div>
                        @php
                        $firstPlan = $plans->first();
                        @endphp
                        @if($firstPlan && isset($firstPlan->yearly_savings) && $firstPlan->yearly_savings > 0)
                        <span class="badge bg-success ms-3 align-self-center yearly-savings-badge">Save {{ $firstPlan->yearly_savings }}% with yearly</span>
                        @endif
                    </div>

                    <!-- Plans Row -->
                    <div class="row g-3">
                        @forelse($plans as $plan)
                        @php
                        $isCurrentPlan = (isset($currentSubscription) && $currentSubscription && $currentSubscription->plan_id == $plan->id);
                        $planCardClass = $isCurrentPlan ? 'border-success border-2' : (($plan->is_popular ?? false) ? 'border-primary' : '');
                        @endphp
                        <div class="col-12 col-md-6 col-lg-4 mb-3">
                            <div class="card h-100 shadow-sm position-relative {{ $planCardClass }}" style="{{ $isCurrentPlan ? 'background: linear-gradient(135deg, #ffffff 0%, #f0fff4 100%);' : '' }}">
                                @if($isCurrentPlan)
                                <div class="current-plan-banner">CURRENT PLAN</div>
                                @elseif($plan->is_popular ?? false)
                                <div class="popular-banner">POPULAR</div>
                                @endif

                                <div class="card-body text-center">
                                    <div class="mb-2">
                                        <span class="badge bg-{{ $plan->badge_color ?? 'secondary' }}">{{ strtoupper($plan->name) }}</span>
                                        @if($plan->badge_text ?? false)
                                        <span class="badge bg-warning text-dark ms-1">{{ $plan->badge_text }}</span>
                                        @endif
                                    </div>

                                    <h4 class="card-title fw-bold mb-2">{{ $plan->name }} Plan</h4>
                                    <p class="card-text text-muted small">{{ $plan->short_description ?? 'Perfect for your business needs' }}</p>

                                    <div class="monthly-price">
                                        <h2 class="display-5 fw-bold {{ $isCurrentPlan ? 'text-success' : '' }}">${{ number_format($plan->monthly_price, 2) }}</h2>
                                        <p class="text-muted small">per month</p>
                                    </div>

                                    <div class="yearly-price d-none">
                                        <h2 class="display-5 fw-bold {{ $isCurrentPlan ? 'text-success' : '' }}">${{ number_format($plan->yearly_price, 2) }}</h2>
                                        <p class="text-muted small">per month, billed annually</p>
                                        <small class="text-muted">(${{ number_format(($plan->yearly_total ?? ($plan->yearly_price * 12)), 2) }}/year)</small>
                                    </div>

                                    <div class="monthly-limits mt-3">
                                        <div class="border-top pt-2">
                                            <div class="d-flex justify-content-between mb-1">
                                                <span class="small"><i class="fas fa-box me-1"></i>Products:</span>
                                                <strong class="small">{{ $plan->products_limit == -1 ? 'Unlimited' : number_format($plan->products_limit) }}</strong>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <span class="small"><i class="fas fa-users me-1"></i>Customers:</span>
                                                <strong class="small">{{ $plan->customer_limit == -1 ? 'Unlimited' : number_format($plan->customer_limit) }}</strong>
                                            </div>
                                            <small class="text-muted d-block mt-1">per month</small>
                                        </div>
                                    </div>

                                    <div class="yearly-limits d-none mt-3">
                                        <div class="border-top pt-2">
                                            <div class="d-flex justify-content-between mb-1">
                                                <span class="small"><i class="fas fa-box me-1"></i>Products:</span>
                                                <strong class="small">{{ ($plan->yearly_products_limit ?? $plan->products_limit) == -1 ? 'Unlimited' : number_format($plan->yearly_products_limit ?? $plan->products_limit) }}</strong>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <span class="small"><i class="fas fa-users me-1"></i>Customers:</span>
                                                <strong class="small">{{ ($plan->yearly_customer_limit ?? $plan->customer_limit) == -1 ? 'Unlimited' : number_format($plan->yearly_customer_limit ?? $plan->customer_limit) }}</strong>
                                            </div>
                                            <small class="text-muted d-block mt-1">per year</small>
                                        </div>
                                    </div>

                                    <ul class="list-unstyled mt-3 mb-3 text-start">
                                        @php
                                        $features = is_array($plan->features) ? $plan->features : (json_decode($plan->features, true) ?? []);
                                        @endphp
                                        @foreach(array_slice($features, 0, 3) as $feature)
                                        <li class="mb-1 small"><i class="fas fa-check text-success me-1"></i> {{ Str::limit($feature, 40) }}</li>
                                        @endforeach
                                        @if(count($features) > 3)
                                        <li class="small text-muted">+{{ count($features) - 3 }} more features</li>
                                        @endif
                                    </ul>

                                    @if($isCurrentPlan)
                                    <button type="button" class="btn btn-success w-100" disabled>
                                        <i class="fas fa-check-circle me-1"></i>Current Plan
                                    </button>
                                    @else
                                    <button type="button" class="btn {{ ($plan->is_popular ?? false) ? 'btn-primary' : 'btn-outline-' . ($plan->badge_color ?? 'secondary') }} w-100 choose-plan-btn" data-plan-id="{{ $plan->id }}" data-plan-name="{{ $plan->name }}">
                                        <i class="fab fa-paypal me-1"></i>Subscribe to {{ $plan->name }}
                                    </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="col-12">
                            <div class="alert alert-info text-center">
                                <i class="fas fa-info-circle"></i> No subscription plans available at the moment.
                            </div>

                        </div>
                        @endforelse
                    </div>

                    <div class="text-center mt-4">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            All plans include a 14-day free trial. Secure payments powered by PayPal.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- PayPal Payment Modal -->
<div class="modal fade" id="paypalModal" tabindex="-1" aria-labelledby="paypalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white py-2">
                <h5 class="modal-title">
                    <i class="fab fa-paypal me-1"></i>Complete Subscription
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <div id="modalLoadingState" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 mb-0 text-muted">Loading plan details...</p>
                </div>

                <div id="modalContentState" style="display: none;">
                    <div class="alert alert-info mb-3 py-2">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check-circle text-info"></i>
                            </div>
                            <div class="flex-grow-1 ms-2">
                                <h6 class="alert-heading mb-0" id="selectedPlanName"></h6>
                                <p class="mb-0 small" id="selectedPlanPrice"></p>
                                <small id="selectedPlanDuration" class="text-muted"></small>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-secondary mb-3 py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-chart-line me-1"></i>
                                <strong class="small">Your Limits:</strong>
                            </div>
                            <div class="text-end">
                                <div id="productsLimitText" class="small"></div>
                                <div id="customersLimitText" class="small"></div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-light p-3 rounded mb-3">
                        <h6 class="mb-2">Payment Summary</h6>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small">Plan:</span>
                            <strong class="small" id="summaryPlanName">-</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small">Billing:</span>
                            <strong class="small" id="summaryBillingType">-</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small">Amount:</span>
                            <strong class="small text-primary" id="summaryAmount">-</strong>
                        </div>
                        <hr class="my-1">
                        <div class="d-flex justify-content-between">
                            <span class="small">Total:</span>
                            <strong class="text-success small" id="summaryTotal">-</strong>
                        </div>
                    </div>

                    <button type="button" class="btn btn-primary w-100" id="paypalPaymentBtn">
                        <i class="fab fa-paypal me-1"></i>
                        <span class="button-text">Pay with PayPal</span>
                        <span class="spinner-border spinner-border-sm d-none ms-1" role="status"></span>
                    </button>
                </div>

                <p class="text-muted small text-center mt-2 mb-0">
                    <i class="fas fa-lock me-1"></i> Secure payment via PayPal
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-success text-white py-2">
                <h5 class="modal-title">
                    <i class="fas fa-check-circle me-1"></i>Success!
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-3">
                <i class="fas fa-check-circle text-success" style="font-size: 3rem;"></i>
                <h6 class="mt-2" id="successMessage">Subscription started successfully!</h6>
            </div>
        </div>
    </div>
</div>

<!-- Processing Modal -->
<div class="modal fade" id="processingModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-body text-center py-3">
                <div class="spinner-border text-primary mb-2" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <h6 class="mb-0">Processing Payment...</h6>
                <small class="text-muted">Please wait</small>
            </div>
        </div>
    </div>
</div>

<!-- Error Modal -->
<div class="modal fade" id="errorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-1"></i>Error
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="errorMessage"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<form id="csrfForm" method="POST" style="display: none;">
    @csrf
</form>
@endsection

@section('css')
<style>
    .current-plan-banner {
        position: absolute;
        top: 15px;
        right: -35px;
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
        padding: 5px 40px;
        font-size: 0.7rem;
        font-weight: bold;
        transform: rotate(45deg);
        z-index: 10;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        width: 140px;
        text-align: center;
        white-space: nowrap;
    }

    .popular-banner {
        position: absolute;
        top: 15px;
        right: -35px;
        background: linear-gradient(135deg, #007bff, #00b4db);
        color: white;
        padding: 5px 40px;
        font-size: 0.7rem;
        font-weight: bold;
        transform: rotate(45deg);
        z-index: 10;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        width: 120px;
        text-align: center;
        white-space: nowrap;
    }

    .card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        height: 100%;
    }

    .card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1) !important;
    }

    @media (max-width: 768px) {

        .current-plan-banner,
        .popular-banner {
            font-size: 0.6rem;
            padding: 4px 35px;
        }

        .current-plan-banner {
            width: 130px;
        }

        .popular-banner {
            width: 110px;
        }
    }

    /* Custom scrollbar */
    ::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }

    ::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }

    ::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 10px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: #555;
    }

</style>
@endsection

@section('js')
<script>
    // Get CSRF token - Fixed version without optional chaining
    let csrfToken = null;
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const csrfForm = document.querySelector('#csrfForm input[name="_token"]');

    if (csrfMeta) {
        csrfToken = csrfMeta.getAttribute('content');
    } else if (csrfForm) {
        csrfToken = csrfForm.value;
    }

    console.log('CSRF Token present:', !!csrfToken);

    // Fetch wrapper with CSRF
    const fetchWithCSRF = (url, options = {}) => {
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json'
                , 'Accept': 'application/json'
                , 'X-CSRF-TOKEN': csrfToken
                , 'X-Requested-With': 'XMLHttpRequest'
            }
            , credentials: 'same-origin'
        };

        return fetch(url, {
            ...defaultOptions
            , ...options
        });
    };

    // Show error modal
    function showError(message) {
        const errorModalElement = document.getElementById('errorModal');
        if (errorModalElement) {
            const errorModal = new bootstrap.Modal(errorModalElement);
            const errorMessage = document.getElementById('errorMessage');
            if (errorMessage) {
                errorMessage.textContent = message;
            }
            errorModal.show();
        } else {
            alert('Error: ' + message);
        }
    }

    // Show success modal
    function showSuccess(message) {
        const successModalElement = document.getElementById('successModal');
        if (successModalElement) {
            const successModal = new bootstrap.Modal(successModalElement);
            const successMessage = document.getElementById('successMessage');
            if (successMessage) {
                successMessage.textContent = message;
            }
            successModal.show();
            setTimeout(() => {
                successModal.hide();
                window.location.href = dashboardUrl;
            }, 3000);
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM loaded - Subscription page');

        // Routes
        const planDetailsUrl = '{{ url("merchant/manage-subscriptions/plan-details") }}';
        const processPayPalUrl = '{{ route("merchant.manage-subscriptions.process.plan.change") }}';
        const dashboardUrl = '{{ route("merchant.dashboard") }}';

        console.log('Plan Details URL:', planDetailsUrl);
        console.log('Process PayPal URL:', processPayPalUrl);

        let currentBillingType = 'monthly';
        let selectedPlan = null;
        let selectedPlanId = null;

        // Initialize modals
        let paypalModal = null;
        let successModal = null;
        let processingModal = null;

        const paypalModalElement = document.getElementById('paypalModal');
        const successModalElement = document.getElementById('successModal');
        const processingModalElement = document.getElementById('processingModal');

        if (paypalModalElement) paypalModal = new bootstrap.Modal(paypalModalElement);
        if (successModalElement) successModal = new bootstrap.Modal(successModalElement);
        if (processingModalElement) processingModal = new bootstrap.Modal(processingModalElement);

        // DOM elements
        const monthlyBtn = document.getElementById('monthlyBtn');
        const yearlyBtn = document.getElementById('yearlyBtn');
        const monthlyPrices = document.querySelectorAll('.monthly-price');
        const yearlyPrices = document.querySelectorAll('.yearly-price');
        const monthlyLimits = document.querySelectorAll('.monthly-limits');
        const yearlyLimits = document.querySelectorAll('.yearly-limits');

        // Toggle functions
        function showMonthly() {
            if (monthlyBtn && yearlyBtn) {
                monthlyBtn.classList.add('btn-primary', 'active');
                monthlyBtn.classList.remove('btn-outline-primary');
                yearlyBtn.classList.add('btn-outline-primary');
                yearlyBtn.classList.remove('btn-primary', 'active');
            }

            monthlyPrices.forEach(price => price.classList.remove('d-none'));
            yearlyPrices.forEach(price => price.classList.add('d-none'));
            monthlyLimits.forEach(limit => limit.classList.remove('d-none'));
            yearlyLimits.forEach(limit => limit.classList.add('d-none'));

            currentBillingType = 'monthly';
            if (selectedPlan) updateModalSummary();
        }

        function showYearly() {
            if (monthlyBtn && yearlyBtn) {
                yearlyBtn.classList.add('btn-primary', 'active');
                yearlyBtn.classList.remove('btn-outline-primary');
                monthlyBtn.classList.add('btn-outline-primary');
                monthlyBtn.classList.remove('btn-primary', 'active');
            }

            monthlyPrices.forEach(price => price.classList.add('d-none'));
            yearlyPrices.forEach(price => price.classList.remove('d-none'));
            monthlyLimits.forEach(limit => limit.classList.add('d-none'));
            yearlyLimits.forEach(limit => limit.classList.remove('d-none'));

            currentBillingType = 'yearly';
            if (selectedPlan) updateModalSummary();
        }

        // Add toggle event listeners
        if (monthlyBtn) monthlyBtn.addEventListener('click', showMonthly);
        if (yearlyBtn) yearlyBtn.addEventListener('click', showYearly);

        // Helper functions
        function number_format(number) {
            if (number === null || number === undefined) return '0';
            return new Intl.NumberFormat().format(number);
        }

        function updateModalSummary() {
            if (!selectedPlan) return;

            const productsLimit = currentBillingType === 'monthly' ? selectedPlan.products_limit : (selectedPlan.yearly_products_limit || selectedPlan.products_limit);
            const customersLimit = currentBillingType === 'monthly' ? selectedPlan.customer_limit : (selectedPlan.yearly_customer_limit || selectedPlan.customer_limit);
            const period = currentBillingType === 'monthly' ? 'month' : 'year';

            const productsText = document.getElementById('productsLimitText');
            const customersText = document.getElementById('customersLimitText');

            if (productsText) {
                productsText.innerHTML = '<i class="fas fa-box me-1"></i> Products: ' + (productsLimit == -1 ? 'Unlimited' : number_format(productsLimit)) + '/' + period;
            }
            if (customersText) {
                customersText.innerHTML = '<i class="fas fa-users me-1"></i> Customers: ' + (customersLimit == -1 ? 'Unlimited' : number_format(customersLimit)) + '/' + period;
            }

            let price = '';
            let totalDisplay = '';

            if (currentBillingType === 'monthly') {
                price = selectedPlan.monthly_price_formatted || '$' + number_format(selectedPlan.monthly_price);
                totalDisplay = price;
                const durationSpan = document.getElementById('selectedPlanDuration');
                if (durationSpan) durationSpan.textContent = '30 days';
            } else {
                price = selectedPlan.yearly_price_formatted || '$' + number_format(selectedPlan.yearly_price);
                totalDisplay = price;
                const durationSpan = document.getElementById('selectedPlanDuration');
                if (durationSpan) durationSpan.textContent = '365 days';
            }

            const planNameSpan = document.getElementById('selectedPlanName');
            const planPriceSpan = document.getElementById('selectedPlanPrice');
            const summaryPlanSpan = document.getElementById('summaryPlanName');
            const summaryBillingSpan = document.getElementById('summaryBillingType');
            const summaryAmountSpan = document.getElementById('summaryAmount');
            const summaryTotalSpan = document.getElementById('summaryTotal');

            if (planNameSpan) planNameSpan.textContent = selectedPlan.name + ' Plan';
            if (planPriceSpan) planPriceSpan.textContent = price;
            if (summaryPlanSpan) summaryPlanSpan.textContent = selectedPlan.name;
            if (summaryBillingSpan) summaryBillingSpan.textContent = currentBillingType === 'monthly' ? 'Monthly' : 'Yearly';
            if (summaryAmountSpan) summaryAmountSpan.textContent = price;
            if (summaryTotalSpan) summaryTotalSpan.textContent = totalDisplay;
        }

        // Initialize plan selection buttons
        function initializePlanButtons() {
            const planButtons = document.querySelectorAll('.choose-plan-btn');
            console.log('Found plan buttons:', planButtons.length);

            planButtons.forEach(function(btn) {
                // Remove existing listeners by cloning
                const newBtn = btn.cloneNode(true);
                btn.parentNode.replaceChild(newBtn, btn);

                newBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    selectedPlanId = this.dataset.planId;
                    console.log('Plan selected:', selectedPlanId);

                    // Reset modal to loading state
                    const loadingState = document.getElementById('modalLoadingState');
                    const contentState = document.getElementById('modalContentState');
                    if (loadingState) loadingState.style.display = 'block';
                    if (contentState) contentState.style.display = 'none';

                    // Show modal
                    if (paypalModal) paypalModal.show();

                    // Fetch plan details
                    fetchWithCSRF(planDetailsUrl + '/' + selectedPlanId, {
                            method: 'GET'
                        })
                        .then(function(res) {
                            if (!res.ok) {
                                throw new Error('HTTP ' + res.status);
                            }
                            return res.json();
                        })
                        .then(function(data) {
                            console.log('Plan details received:', data);
                            if (data.success) {
                                selectedPlan = data.data;
                                updateModalSummary();
                                if (loadingState) loadingState.style.display = 'none';
                                if (contentState) contentState.style.display = 'block';
                            } else {
                                throw new Error(data.message || 'Error loading plan details');
                            }
                        })
                        .catch(function(error) {
                            console.error('Fetch error:', error);
                            showError('Error loading plan details. Please try again.');
                            if (paypalModal) paypalModal.hide();
                        });
                });
            });
        }

        // Initialize PayPal payment button
        function initializePayPalButton() {
            const paypalPaymentBtn = document.getElementById('paypalPaymentBtn');
            if (!paypalPaymentBtn) {
                console.error('PayPal button not found');
                return;
            }

            // Remove existing listeners
            const newBtn = paypalPaymentBtn.cloneNode(true);
            paypalPaymentBtn.parentNode.replaceChild(newBtn, paypalPaymentBtn);

            newBtn.addEventListener('click', function() {
                if (!selectedPlanId || !selectedPlan) {
                    showError('Please select a plan first');
                    return;
                }

                // Disable button and show spinner
                this.disabled = true;
                const buttonText = this.querySelector('.button-text');
                const spinner = this.querySelector('.spinner-border');
                if (buttonText) buttonText.textContent = 'Processing...';
                if (spinner) spinner.classList.remove('d-none');

                // Close PayPal modal and show processing
                if (paypalModal) paypalModal.hide();
                if (processingModal) processingModal.show();

                console.log('Sending payment request:', {
                    plan_id: selectedPlanId
                    , billing_type: currentBillingType
                });

                // Create PayPal order
                fetchWithCSRF(processPayPalUrl, {
                        method: 'POST'
                        , body: JSON.stringify({
                            plan_id: selectedPlanId
                            , billing_type: currentBillingType
                        })
                    })
                    .then(function(res) {
                        return res.json().then(function(data) {
                            if (!res.ok) {
                                throw new Error(data.message || 'HTTP ' + res.status);
                            }
                            return data;
                        });
                    })
                    .then(function(data) {
                        console.log('PayPal response:', data);
                        if (data.success && data.approval_url) {
                            window.location.href = data.approval_url;
                        } else {
                            throw new Error(data.message || 'Failed to initiate PayPal payment');
                        }
                    })
                    .catch(function(error) {
                        console.error('Payment error:', error);
                        if (processingModal) processingModal.hide();
                        showError('Payment error: ' + error.message);

                        // Reset button
                        newBtn.disabled = false;
                        const buttonText = newBtn.querySelector('.button-text');
                        const spinner = newBtn.querySelector('.spinner-border');
                        if (buttonText) buttonText.textContent = 'Pay with PayPal';
                        if (spinner) spinner.classList.add('d-none');

                        // Show PayPal modal again
                        if (paypalModal) paypalModal.show();
                    });
            });
        }

        // Initialize everything
        initializePlanButtons();
        initializePayPalButton();

        // Handle session messages
        @if(Session::has('success'))
        showSuccess('{{ Session::get('
            success ') }}');
        @endif

        @if(Session::has('error'))
        setTimeout(function() {
            showError('{{ Session::get('
                error ') }}');
        }, 100);
        @endif

        @if(Session::has('info'))
        setTimeout(function() {
            showError('{{ Session::get('
                info ') }}');
        }, 100);
        @endif

        // Handle modal close reset
        if (paypalModalElement) {
            paypalModalElement.addEventListener('hidden.bs.modal', function() {
                const paypalBtn = document.getElementById('paypalPaymentBtn');
                if (paypalBtn) {
                    paypalBtn.disabled = false;
                    const buttonText = paypalBtn.querySelector('.button-text');
                    const spinner = paypalBtn.querySelector('.spinner-border');
                    if (buttonText) buttonText.textContent = 'Pay with PayPal';
                    if (spinner) spinner.classList.add('d-none');
                }
                // Reset modal state
                const loadingState = document.getElementById('modalLoadingState');
                const contentState = document.getElementById('modalContentState');
                if (loadingState) loadingState.style.display = 'block';
                if (contentState) contentState.style.display = 'none';
            });
        }

        // Initial toggle state
        showMonthly();
    });

</script>
@endsection
