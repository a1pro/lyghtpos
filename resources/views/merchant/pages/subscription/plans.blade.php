@extends('merchant.layouts.main-single-page')

@section('content')

<div class="pricing-wrapper">
    <div class="container">
        <div class="pricing-card-main">

            <!-- Header -->
            <div class="pricing-header text-center mb-2">
                <h2>Choose Your Perfect Plan</h2>
                <p>Select the ideal subscription plan for your business growth</p>
            </div>

            <!-- Toggle -->
            <div class="text-center mb-3 d-flex justify-content-center gap-2">


                <div class="billing-toggle">
                    <button type="button" class="btn active" id="monthlyBtn" data-billing="monthly">
                        Monthly
                    </button>

                    <button type="button" class="btn" id="yearlyBtn" data-billing="yearly">
                        Yearly
                    </button>
                </div>

                @php
                $firstPlan = $plans->first();
                @endphp

                @if($firstPlan && $firstPlan->yearly_savings > 0)
                <div class="mt-3">
                    <span class="yearly-savings-badge">
                        Save {{ $firstPlan->yearly_savings }}% with yearly billing
                    </span>
                </div>
                @endif
            </div>

            <!-- Plans -->
            <div class="row">

                @foreach($plans as $plan)

                <div class="col-xl-4 col-lg-4 col-md-6 mb-4">

                    <div class="plan-card {{ $plan->is_popular ? 'popular' : '' }}">

                        @if($plan->is_popular)
                        <div class="popular-tag">
                            MOST POPULAR
                        </div>
                        @endif

                        <div class="plan-body text-center">

                            <div class="mb-3">

                                <span class="plan-badge bg-{{ $plan->badge_color }} text-white">
                                    {{ strtoupper($plan->name) }}
                                </span>

                                @if($plan->badge_text)
                                <span class="badge bg-warning text-dark ms-2">
                                    {{ $plan->badge_text }}
                                </span>
                                @endif
                            </div>

                            <h3 class="fw-bold mb-2">
                                {{ $plan->name }} Plan
                            </h3>

                            <p class="text-muted">
                                {{ $plan->short_description }}
                            </p>

                            <!-- Monthly -->
                            <div class="monthly-price price-box">

                                <h2>
                                    <span>$</span>{{ number_format($plan->monthly_price,0) }}
                                </h2>

                                <p>Per Month</p>
                            </div>

                            <!-- Yearly -->
                            <div class="yearly-price price-box d-none">

                                <h2>
                                    <span>$</span>{{ number_format($plan->yearly_price,0) }}
                                </h2>

                                <p>
                                    Per Month <br>
                                    Billed Annually (${{ number_format($plan->yearly_total ?? ($plan->yearly_price * 12),0) }}/year)
                                </p>
                            </div>

                            <!-- Monthly Limits -->
                            <div class="monthly-limits limits-box">

                                <div class="limit-row">
                                    <span>
                                        <i class="fas fa-box me-2"></i>
                                        Products Upload
                                    </span>

                                    <strong>
                                        {{ $plan->products_limit == -1 ? 'Unlimited' : number_format($plan->products_limit) }}
                                    </strong>
                                </div>

                                <div class="limit-row">
                                    <span>
                                        <i class="fas fa-users me-2"></i>
                                        Customers
                                    </span>

                                    <strong>
                                        {{ $plan->customer_limit == -1 ? 'Unlimited' : number_format($plan->customer_limit) }}
                                    </strong>
                                </div>

                                <small class="text-muted d-block mt-2">
                                    Per Month
                                </small>
                            </div>

                            <!-- Yearly Limits -->
                            <div class="yearly-limits limits-box d-none">

                                <div class="limit-row">
                                    <span>
                                        <i class="fas fa-box me-2"></i>
                                        Products Upload
                                    </span>

                                    <strong>
                                        {{ $plan->yearly_products_limit == -1 ? 'Unlimited' : number_format($plan->yearly_products_limit) }}
                                    </strong>
                                </div>

                                <div class="limit-row">
                                    <span>
                                        <i class="fas fa-users me-2"></i>
                                        Customers
                                    </span>

                                    <strong>
                                        {{ $plan->yearly_customer_limit == -1 ? 'Unlimited' : number_format($plan->yearly_customer_limit) }}
                                    </strong>
                                </div>

                                <small class="text-muted d-block mt-2">
                                    Per Year
                                </small>
                            </div>

                            <!-- Features -->
                            <ul class="features-list text-start">

                                @foreach($plan->features as $feature)

                                <li>
                                    <i class="fas fa-check-circle"></i>
                                    <span>{{ $feature }}</span>
                                </li>

                                @endforeach

                            </ul>

                            <!-- Button -->
                            <button type="button" class="choose-plan-btn w-100 {{ $plan->is_popular ? 'btn-primary-custom' : 'btn-outline-custom' }}" data-plan-id="{{ $plan->id }}" data-plan-name="{{ $plan->name }}" data-plan-monthly-price="${{ number_format($plan->monthly_price,0) }}" data-plan-yearly-price="${{ number_format($plan->yearly_price,0) }}" data-bs-toggle="modal" data-bs-target="#paypalModal">

                                <i class="fab fa-paypal me-2"></i>
                                Choose {{ $plan->name }}
                            </button>

                        </div>
                    </div>
                </div>

                @endforeach

            </div>

            <!-- Footer -->
            <div class="pricing-footer text-center">

                <div class="mb-3">
                    <small>
                        <i class="fas fa-shield-alt text-success me-1"></i>
                        All plans include a 14-day free trial.
                        Secure payments powered by PayPal.
                    </small>
                </div>

                <div class="mb-4">
                    <small>
                        By selecting a plan, you agree to our
                        <a href="#">Terms of Service</a>
                        and
                        <a href="#">Privacy Policy</a>
                    </small>
                </div>

                <div class="trust-images">
                    <img src="https://www.paypalobjects.com/webstatic/mktg/logo/pp_cc_mark_111x69.jpg" alt="">
                    <img src="https://www.paypalobjects.com/webstatic/mktg/logo/AM_SbyPP_mc_vs_dc_ae.jpg" alt="">
                </div>

            </div>

        </div>
    </div>
</div>

<!-- PayPal Modal -->
<div class="modal fade" id="paypalModal" tabindex="-1">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fab fa-paypal me-2"></i>
                    Complete Subscription
                </h5>

                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <!-- Selected Plan -->
                <div class="alert alert-primary rounded-4 border-0 mb-4" id="selectedPlanSummary" style="display:none;">

                    <div class="d-flex align-items-center">

                        <div class="me-3">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>

                        <div>
                            <h6 class="mb-1 fw-bold" id="selectedPlanName"></h6>
                            <div id="selectedPlanPrice"></div>
                            <small id="selectedPlanDuration"></small>
                        </div>

                    </div>
                </div>

                <!-- Summary -->
                <div class="summary-card mb-4">

                    <h5 class="fw-bold mb-4">
                        Payment Summary
                    </h5>

                    <div class="summary-row">
                        <span>Plan</span>
                        <strong id="summaryPlanName">-</strong>
                    </div>

                    <div class="summary-row">
                        <span>Billing</span>
                        <strong id="summaryBillingType">-</strong>
                    </div>

                    <div class="summary-row">
                        <span>Amount</span>
                        <strong class="text-primary" id="summaryAmount">-</strong>
                    </div>

                    <hr>

                    <div class="summary-row">
                        <span class="fw-bold">Total</span>
                        <strong class="text-success fs-5" id="summaryTotal">-</strong>
                    </div>

                </div>

                <!-- Benefits -->
                <div class="row g-3 mb-4">

                    <div class="col-6">
                        <div class="benefit-box">
                            <i class="fas fa-shield-alt"></i>
                            <div class="fw-semibold">Buyer Protection</div>
                        </div>
                    </div>

                    <div class="col-6">
                        <div class="benefit-box">
                            <i class="fas fa-lock"></i>
                            <div class="fw-semibold">Secure Payment</div>
                        </div>
                    </div>

                </div>

                <!-- PayPal Button -->
                <button type="button" class="paypal-btn" id="paypalBtn">

                    <i class="fab fa-paypal me-2"></i>

                    <span class="button-text">
                        Pay with PayPal
                    </span>

                    <span class="spinner-border spinner-border-sm d-none ms-2"></span>
                </button>

                <p class="text-center text-muted small mt-3 mb-0">
                    <i class="fas fa-lock me-1"></i>
                    You will be redirected securely to PayPal
                </p>

            </div>

        </div>
    </div>
</div>

<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">

            <div class="modal-body text-center p-5">

                <div class="success-icon mb-4">
                    <i class="fas fa-check"></i>
                </div>

                <h3 class="fw-bold mb-3">
                    Payment Successful!
                </h3>

                <p class="text-muted mb-2" id="transactionIdDisplay">
                </p>

                <p class="text-muted mb-4">
                    Your subscription has been activated successfully.
                </p>

                <button type="button" class="btn btn-primary btn-lg rounded-pill px-5" id="redirectToDashboard">

                    Go To Dashboard
                </button>

            </div>

        </div>
    </div>
</div>

<!-- Processing Modal -->
<div class="modal fade" id="processingModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">

    <div class="modal-dialog modal-dialog-centered modal-sm">

        <div class="modal-content">

            <div class="processing-box text-center">

                <div class="spinner-border text-primary mb-4" style="width:4rem;height:4rem;">
                </div>

                <h5 class="fw-bold mb-2">
                    Processing Payment...
                </h5>

                <p class="text-muted small mb-0">
                    Please wait while we confirm your transaction
                </p>

            </div>

        </div>
    </div>
</div>

@endsection

@section('js')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Get DOM elements
        const monthlyBtn = document.getElementById('monthlyBtn');
        const yearlyBtn = document.getElementById('yearlyBtn');
        const monthlyPrices = document.querySelectorAll('.monthly-price');
        const yearlyPrices = document.querySelectorAll('.yearly-price');
        const monthlyLimits = document.querySelectorAll('.monthly-limits');
        const yearlyLimits = document.querySelectorAll('.yearly-limits');
        const choosePlanBtns = document.querySelectorAll('.choose-plan-btn');
        const paypalBtn = document.getElementById('paypalBtn');
        const selectedPlanSummary = document.getElementById('selectedPlanSummary');
        const limitsSummary = document.getElementById('limitsSummary');
        const selectedPlanName = document.getElementById('selectedPlanName');
        const selectedPlanPrice = document.getElementById('selectedPlanPrice');
        const selectedPlanDuration = document.getElementById('selectedPlanDuration');
        
        // Limits summary elements
        const productsLimitText = document.getElementById('productsLimitText');
        const customersLimitText = document.getElementById('customersLimitText');
        
        // Summary elements
        const summaryPlanName = document.getElementById('summaryPlanName');
        const summaryBillingType = document.getElementById('summaryBillingType');
        const summaryAmount = document.getElementById('summaryAmount');
        const summaryTotal = document.getElementById('summaryTotal');

        // Modals
        const paypalModal = new bootstrap.Modal(document.getElementById('paypalModal'));
        const successModal = new bootstrap.Modal(document.getElementById('successModal'));
        const processingModal = new bootstrap.Modal(document.getElementById('processingModal'));

        // Define routes
        const processPayPalUrl = '{{ route("merchant.subscription.paypal.create") }}';
        const planDetailsBaseUrl = '/merchant/subscription/plan-details/';
        const dashboardUrl = '{{ route("merchant.dashboard") }}';
        
        console.log('Process PayPal URL:', processPayPalUrl);
        
        let currentBillingType = 'monthly';
        let selectedPlan = null;
        let selectedPlanId = null;

        // Price toggle functions
        function showMonthly() {
            if (monthlyBtn && yearlyBtn) {
                monthlyBtn.classList.add('btn-primary', 'active');
                monthlyBtn.classList.remove('btn-outline-primary');
                yearlyBtn.classList.add('btn-outline-primary');
                yearlyBtn.classList.remove('btn-primary', 'active');
            }

            // Toggle prices
            monthlyPrices.forEach(price => price.classList.remove('d-none'));
            yearlyPrices.forEach(price => price.classList.add('d-none'));
            
            // Toggle limits
            monthlyLimits.forEach(limit => limit.classList.remove('d-none'));
            yearlyLimits.forEach(limit => limit.classList.add('d-none'));
            
            currentBillingType = 'monthly';
            updateSummary();
        }

        function showYearly() {
            if (monthlyBtn && yearlyBtn) {
                yearlyBtn.classList.add('btn-primary', 'active');
                yearlyBtn.classList.remove('btn-outline-primary');
                monthlyBtn.classList.add('btn-outline-primary');
                monthlyBtn.classList.remove('btn-primary', 'active');
            }

            // Toggle prices
            monthlyPrices.forEach(price => price.classList.add('d-none'));
            yearlyPrices.forEach(price => price.classList.remove('d-none'));
            
            // Toggle limits
            monthlyLimits.forEach(limit => limit.classList.add('d-none'));
            yearlyLimits.forEach(limit => limit.classList.remove('d-none'));
            
            currentBillingType = 'yearly';
            updateSummary();
        }

        // Update summary with current plan and billing type
        function updateSummary() {
            if (!selectedPlan) return;

            // Get limits based on billing type
            const productsLimit = currentBillingType === 'monthly' 
                ? selectedPlan.products_limit 
                : selectedPlan.yearly_products_limit;
            
            const customersLimit = currentBillingType === 'monthly' 
                ? selectedPlan.customer_limit 
                : selectedPlan.yearly_customer_limit;
            
            const productsLimitTextValue = productsLimit == -1 ? 'Unlimited' : number_format(productsLimit);
            const customersLimitTextValue = customersLimit == -1 ? 'Unlimited' : number_format(customersLimit);
            
            const period = currentBillingType === 'monthly' ? 'month' : 'year';
            
            // Update limits summary
            if (productsLimitText) {
                productsLimitText.innerHTML = `<i class="fas fa-box me-1"></i> Products: ${productsLimitTextValue} per ${period}`;
            }
            if (customersLimitText) {
                customersLimitText.innerHTML = `<i class="fas fa-users me-1"></i> Customers: ${customersLimitTextValue} per ${period}`;
            }
            
            // Format price display
            let price = '';
            let totalDisplay = '';
            
            if (currentBillingType === 'monthly') {
                price = selectedPlan.monthly_price_formatted || `$${number_format(selectedPlan.monthly_price)}`;
                totalDisplay = price;
                if (selectedPlanDuration) selectedPlanDuration.textContent = `Duration: 30 days`;
            } else {
                price = selectedPlan.yearly_price_formatted || `$${number_format(selectedPlan.yearly_price)}`;
                totalDisplay = price;
                const yearlyTotal = selectedPlan.yearly_total || (selectedPlan.yearly_price * 12);
                if (selectedPlanDuration) selectedPlanDuration.textContent = `Duration: 365 days ($${number_format(yearlyTotal)} total)`;
            }

            // Update modal summary
            if (selectedPlanName) {
                selectedPlanName.textContent = `${selectedPlan.name} Plan`;
            }
            if (selectedPlanPrice) {
                selectedPlanPrice.textContent = `Amount: ${price}`;
            }

            // Update payment summary
            if (summaryPlanName) {
                summaryPlanName.textContent = selectedPlan.name;
            }
            if (summaryBillingType) {
                summaryBillingType.textContent = currentBillingType === 'monthly' ? 'Monthly' : 'Yearly';
            }
            if (summaryAmount) {
                summaryAmount.textContent = price;
            }
            if (summaryTotal) {
                summaryTotal.textContent = totalDisplay;
            }
        }

        // Helper function to format numbers
        function number_format(number) {
            return new Intl.NumberFormat().format(number);
        }

        // Add event listeners for billing toggle
        if (monthlyBtn) monthlyBtn.addEventListener('click', showMonthly);
        if (yearlyBtn) yearlyBtn.addEventListener('click', showYearly);

        // Handle plan selection
        choosePlanBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                selectedPlanId = this.dataset.planId;
                const planName = this.dataset.planName;
                
                // Store price data from button attributes
                const monthlyPrice = this.dataset.planMonthlyPrice;
                const yearlyPrice = this.dataset.planYearlyPrice;
                const yearlyTotal = this.dataset.planYearlyTotal;
                
                console.log('Plan selected:', { planId: selectedPlanId, planName, billingType: currentBillingType });
                
                // Fetch plan details
                const planDetailsUrl = planDetailsBaseUrl + selectedPlanId;
                console.log('Fetching plan details from:', planDetailsUrl);
                
                // Show loading in summary
                if (selectedPlanSummary) {
                    selectedPlanSummary.style.display = 'none';
                }
                if (limitsSummary) {
                    limitsSummary.style.display = 'none';
                }
                
                fetch(planDetailsUrl, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    }
                })
                .then(async response => {
                    console.log('Response status:', response.status);
                    
                    if (!response.ok) {
                        if (response.status === 302) {
                            throw new Error('Redirect detected. Please check if you are logged in.');
                        }
                        if (response.status === 401) {
                            throw new Error('Please login to continue.');
                        }
                        const errorText = await response.text();
                        console.error('Error response:', errorText);
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Plan details received:', data);
                    
                    if (data.success) {
                        selectedPlan = data.data;
                        
                        // Ensure limits are set
                        selectedPlan.products_limit = selectedPlan.products_limit || 0;
                        selectedPlan.customer_limit = selectedPlan.customer_limit || 0;
                        selectedPlan.yearly_products_limit = selectedPlan.yearly_products_limit || 0;
                        selectedPlan.yearly_customer_limit = selectedPlan.yearly_customer_limit || 0;
                        
                        // Update summary
                        updateSummary();
                        
                        if (selectedPlanSummary) {
                            selectedPlanSummary.style.display = 'block';
                        }
                        if (limitsSummary) {
                            limitsSummary.style.display = 'block';
                        }
                        
                        // Reset PayPal button state
                        if (paypalBtn) {
                            paypalBtn.disabled = false;
                            const buttonText = paypalBtn.querySelector('.button-text');
                            if (buttonText) buttonText.textContent = 'Pay with PayPal';
                            const spinner = paypalBtn.querySelector('.spinner-border');
                            if (spinner) spinner.classList.add('d-none');
                        }
                    } else {
                        console.error('Error fetching plan details:', data.message);
                        alert('Error loading plan details: ' + (data.message || 'Please try again.'));
                    }
                })
                .catch(error => {
                    console.error('Error fetching plan details:', error);
                    alert('Error loading plan details. Please make sure you are logged in and try again.');
                });
            });
        });

        // PayPal payment handler
        if (paypalBtn) {
            paypalBtn.addEventListener('click', function() {
                if (!selectedPlanId || !selectedPlan) {
                    alert('Please select a plan first.');
                    return;
                }
                
                // Disable button and show loading
                paypalBtn.disabled = true;
                const buttonText = paypalBtn.querySelector('.button-text');
                const spinner = paypalBtn.querySelector('.spinner-border');
                if (buttonText) buttonText.textContent = 'Redirecting to PayPal...';
                if (spinner) spinner.classList.remove('d-none');
                
                // Show processing modal
                processingModal.show();
                const processingMessage = document.getElementById('processingMessage');
                if (processingMessage) processingMessage.textContent = 'Redirecting to PayPal...';
                
                console.log('Sending PayPal request:', {
                    plan_id: selectedPlanId,
                    billing_type: currentBillingType
                });
                
                // Create PayPal payment
                fetch(processPayPalUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        plan_id: selectedPlanId,
                        billing_type: currentBillingType
                    })
                })
                .then(async response => {
                    const data = await response.json();
                    console.log('PayPal response:', data);
                    
                    if (!response.ok) {
                        throw new Error(data.message || 'Failed to create payment');
                    }
                    return data;
                })
                .then(data => {
                    if (data.success && data.approval_url) {
                        console.log('Redirecting to PayPal:', data.approval_url);
                        // Redirect to PayPal
                        window.location.href = data.approval_url;
                    } else {
                        throw new Error(data.message || 'Failed to initiate PayPal payment');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    
                    // Hide processing modal
                    processingModal.hide();
                    
                    // Show error message
                    alert(error.message || 'An error occurred. Please try again.');
                    
                    // Reset button
                    paypalBtn.disabled = false;
                    if (buttonText) buttonText.textContent = 'Pay with PayPal';
                    if (spinner) spinner.classList.add('d-none');
                });
            });
        }

        // Show success message if redirected from PayPal
        @if(Session::has('success'))
            successModal.show();
            const successMsg = document.getElementById('successMessage');
            if (successMsg) successMsg.textContent = '{{ Session::get('success') }}';
            
            // Auto redirect after 3 seconds
            setTimeout(function() {
                window.location.href = dashboardUrl;
            }, 3000);
        @endif

        // Show info message if cancelled
        @if(Session::has('info'))
            alert('{{ Session::get('info') }}');
        @endif

        // Show error message if any
        @if(Session::has('error'))
            alert('{{ Session::get('error') }}');
        @endif

        // Handle redirect button
        const redirectBtn = document.getElementById('redirectToDashboard');
        if (redirectBtn) {
            redirectBtn.addEventListener('click', function() {
                window.location.href = dashboardUrl;
            });
        }

        // Initialize monthly view
        showMonthly();

        // Handle modal close
        const paypalModalElement = document.getElementById('paypalModal');
        if (paypalModalElement) {
            paypalModalElement.addEventListener('hidden.bs.modal', function () {
                // Reset PayPal button if modal is closed
                if (paypalBtn) {
                    paypalBtn.disabled = false;
                    const buttonText = paypalBtn.querySelector('.button-text');
                    const spinner = paypalBtn.querySelector('.spinner-border');
                    if (buttonText) buttonText.textContent = 'Pay with PayPal';
                    if (spinner) spinner.classList.add('d-none');
                }
            });
        }
    });
</script>
@endsection