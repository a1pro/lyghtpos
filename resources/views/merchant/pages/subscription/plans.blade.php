@extends('merchant.layouts.main-single-page')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <!-- Header Section -->
                    <div class="text-center mb-5">
                        <h3 class="fw-bold">Choose your plan</h3>
                        <p class="text-muted">Select the perfect plan for your business needs</p>
                    </div>

                    <!-- Pricing Toggle (Monthly/Yearly) -->
                    <div class="d-flex justify-content-center mb-5">
                        <div class="btn-group" role="group" aria-label="Billing period">
                            <button type="button" class="btn btn-primary active" id="monthlyBtn" data-billing="monthly">Monthly</button>
                            <button type="button" class="btn btn-outline-primary" id="yearlyBtn" data-billing="yearly">Yearly</button>
                        </div>
                        @php
                        $firstPlan = $plans->first();
                        @endphp
                        @if($firstPlan && $firstPlan->yearly_savings > 0)
                        <span class="badge bg-success ms-3 align-self-center yearly-savings-badge">Save {{ $firstPlan->yearly_savings }}% with yearly</span>
                        @endif
                    </div>

                    <!-- Plans Row -->
                    <div class="row">
                        @foreach($plans as $plan)
                        <div class="col-md-4 mb-4">
                            <div class="card h-100 shadow-sm {{ $plan->is_popular ? 'border-primary' : '' }}">
                                <div class="card-body text-center">
                                    <div class="mb-3">
                                        <span class="badge bg-{{ $plan->badge_color }}">{{ strtoupper($plan->name) }}</span>
                                        @if($plan->badge_text)
                                        <span class="badge bg-warning text-dark ms-1">{{ $plan->badge_text }}</span>
                                        @endif
                                    </div>

                                    <h4 class="card-title fw-bold">{{ $plan->name }} Plan</h4>
                                    <p class="card-text text-muted">{{ $plan->short_description }}</p>

                                    <!-- Monthly Price -->
                                    <div class="monthly-price">
                                        <h2 class="display-4 fw-bold">${{ number_format($plan->monthly_price, 0) }}</h2>
                                        <p class="text-muted">per month</p>
                                    </div>

                                    <!-- Yearly Price (Hidden by default) -->
                                    <div class="yearly-price d-none">
                                        <h2 class="display-4 fw-bold">${{ number_format($plan->yearly_price, 0) }}</h2>
                                        <p class="text-muted">per month, billed annually (${{ number_format($plan->yearly_total ?? ($plan->yearly_price * 12), 0) }}/yr)</p>
                                    </div>

                                    <!-- Features -->
                                    <ul class="list-unstyled mt-4 mb-4">
                                        @foreach($plan->features as $feature)
                                        <li class="mb-2"><i class="fas fa-check text-success"></i> {{ $feature }}</li>
                                        @endforeach
                                    </ul>

                                    <!-- Choose Plan Button -->
                                    <button type="button" 
                                            class="btn {{ $plan->is_popular ? 'btn-primary' : 'btn-outline-' . $plan->badge_color }} w-100 choose-plan-btn" 
                                            data-plan-id="{{ $plan->id }}"
                                            data-plan-name="{{ $plan->name }}"
                                            data-bs-toggle="modal"
                                            data-bs-target="#paypalModal">
                                        <i class="fab fa-paypal me-2"></i>Choose {{ $plan->name }}
                                    </button>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <!-- Footer Note -->
                    <div class="text-center mt-4">
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i>
                            All plans include a 14-day free trial. Secure payments powered by PayPal.
                        </small>
                    </div>

                    <!-- Terms and Conditions -->
                    <div class="text-center mt-3">
                        <small class="text-muted">
                            By selecting a plan, you agree to our
                            <a href="#">Terms of Service</a> and
                            <a href="#">Privacy Policy</a>
                        </small>
                    </div>

                    <!-- PayPal Trust Badge -->
                    <div class="text-center mt-4">
                        <img src="https://www.paypalobjects.com/webstatic/mktg/logo/pp_cc_mark_111x69.jpg" alt="PayPal" class="me-2" style="height: 30px;">
                        <img src="https://www.paypalobjects.com/webstatic/mktg/logo/AM_SbyPP_mc_vs_dc_ae.jpg" alt="Payment Methods" style="height: 30px;">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- PayPal Payment Modal -->
<div class="modal fade" id="paypalModal" tabindex="-1" aria-labelledby="paypalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="paypalModalLabel">
                    <i class="fab fa-paypal me-2"></i>Complete Your Subscription
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Selected Plan Summary -->
                <div class="alert alert-info mb-4" id="selectedPlanSummary" style="display: none;">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle fa-2x text-info"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="alert-heading mb-1" id="selectedPlanName"></h6>
                            <p class="mb-0" id="selectedPlanPrice"></p>
                            <small id="selectedPlanDuration" class="text-muted"></small>
                        </div>
                    </div>
                </div>

                <!-- PayPal Payment Section -->
                <div class="text-center py-4">
                    <!-- PayPal Logo -->
                    <div class="mb-4">
                        <img src="https://www.paypalobjects.com/webstatic/icon/pp258.png" alt="PayPal" style="height: 60px;">
                    </div>

                    <!-- Payment Summary -->
                    <div class="payment-summary bg-light p-4 rounded mb-4">
                        <h5 class="mb-3">Payment Summary</h5>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Plan:</span>
                            <strong id="summaryPlanName">-</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Billing:</span>
                            <strong id="summaryBillingType">-</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Amount:</span>
                            <strong class="text-primary" id="summaryAmount">-</strong>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <span>Total:</span>
                            <strong class="text-success h5 mb-0" id="summaryTotal">-</strong>
                        </div>
                    </div>

                    <!-- PayPal Benefits -->
                    <div class="row g-3 mb-4">
                        <div class="col-6">
                            <div class="text-center">
                                <i class="fas fa-shield-alt fa-2x text-primary mb-2"></i>
                                <small class="d-block">Buyer Protection</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <i class="fas fa-lock fa-2x text-primary mb-2"></i>
                                <small class="d-block">Secure Payment</small>
                            </div>
                        </div>
                    </div>

                    <!-- PayPal Button -->
                    <button type="button" class="btn btn-primary btn-lg w-100" id="paypalBtn">
                        <i class="fab fa-paypal me-2"></i>
                        <span class="button-text">Pay with PayPal</span>
                        <span class="spinner-border spinner-border-sm d-none ms-2" role="status" aria-hidden="true"></span>
                    </button>

                    <!-- Security Note -->
                    <p class="text-muted small mt-3 mb-0">
                        <i class="fas fa-lock me-1"></i>
                        You'll be redirected to PayPal's secure payment page
                    </p>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <div class="w-100 text-center">
                    <small class="text-muted">
                        <i class="fas fa-credit-card me-1"></i>
                        We accept all major credit cards via PayPal
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="successModalLabel">
                    <i class="fas fa-check-circle me-2"></i>Payment Successful!
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-4">
                <div class="mb-4">
                    <i class="fas fa-check-circle text-success" style="font-size: 5rem;"></i>
                </div>
                <h4 class="mb-3" id="successMessage">Subscription started successfully!</h4>
                <p class="text-muted mb-2" id="transactionIdDisplay"></p>
                <p class="text-muted mb-0">You will be redirected to your dashboard in a few seconds...</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary w-100" id="redirectToDashboard">
                    Go to Dashboard Now
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Processing Modal -->
<div class="modal fade" id="processingModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="processingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-body text-center py-4">
                <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <h5 class="mb-2" id="processingMessage">Processing Payment...</h5>
                <p class="text-muted small mb-0">Please wait while we confirm your payment</p>
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
        const choosePlanBtns = document.querySelectorAll('.choose-plan-btn');
        const paypalBtn = document.getElementById('paypalBtn');
        const selectedPlanSummary = document.getElementById('selectedPlanSummary');
        const selectedPlanName = document.getElementById('selectedPlanName');
        const selectedPlanPrice = document.getElementById('selectedPlanPrice');
        const selectedPlanDuration = document.getElementById('selectedPlanDuration');
        
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

            monthlyPrices.forEach(price => price.classList.remove('d-none'));
            yearlyPrices.forEach(price => price.classList.add('d-none'));
            
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

            monthlyPrices.forEach(price => price.classList.add('d-none'));
            yearlyPrices.forEach(price => price.classList.remove('d-none'));
            
            currentBillingType = 'yearly';
            updateSummary();
        }

        // Update summary with current plan and billing type
        function updateSummary() {
            if (!selectedPlan) return;

            const price = currentBillingType === 'monthly' 
                ? selectedPlan.monthly_price_formatted 
                : selectedPlan.yearly_price_formatted;
            
            const totalYearly = currentBillingType === 'yearly' 
                ? ` (${selectedPlan.yearly_total}/yr)` 
                : '';
            
            const days = currentBillingType === 'monthly' ? '30 days' : '365 days';

            // Update modal summary
            if (selectedPlanName) {
                selectedPlanName.textContent = `${selectedPlan.name} Plan`;
            }
            if (selectedPlanPrice) {
                selectedPlanPrice.textContent = `Amount: ${price}${totalYearly}`;
            }
            if (selectedPlanDuration) {
                selectedPlanDuration.textContent = `Duration: ${days}`;
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
                summaryTotal.textContent = price;
            }
        }

        // Add event listeners for billing toggle
        if (monthlyBtn) monthlyBtn.addEventListener('click', showMonthly);
        if (yearlyBtn) yearlyBtn.addEventListener('click', showYearly);

        // Handle plan selection
        choosePlanBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                selectedPlanId = this.dataset.planId;
                const planName = this.dataset.planName;
                
                console.log('Plan selected:', { planId: selectedPlanId, planName, billingType: currentBillingType });
                
                // Fetch plan details
                const planDetailsUrl = planDetailsBaseUrl + selectedPlanId;
                console.log('Fetching plan details from:', planDetailsUrl);
                
                // Show loading in summary
                if (selectedPlanSummary) {
                    selectedPlanSummary.style.display = 'none';
                }
                
                fetch(planDetailsUrl, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                    }
                })
                .then(async response => {
                    console.log('Response status:', response.status);
                    
                    if (!response.ok) {
                        if (response.status === 302) {
                            throw new Error('Redirect detected. Please check if you are logged in.');
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
                        
                        // Update summary
                        updateSummary();
                        
                        if (selectedPlanSummary) {
                            selectedPlanSummary.style.display = 'block';
                        }
                        
                        // Reset PayPal button state
                        if (paypalBtn) {
                            paypalBtn.disabled = false;
                            paypalBtn.querySelector('.button-text').textContent = 'Pay with PayPal';
                            paypalBtn.querySelector('.spinner-border').classList.add('d-none');
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
                paypalBtn.querySelector('.button-text').textContent = 'Redirecting to PayPal...';
                paypalBtn.querySelector('.spinner-border').classList.remove('d-none');
                
                // Show processing modal
                processingModal.show();
                document.getElementById('processingMessage').textContent = 'Redirecting to PayPal...';
                
                console.log('Sending PayPal request:', {
                    plan_id: selectedPlanId,
                    billing_type: currentBillingType
                });
                
                // Create PayPal payment
                fetch(processPayPalUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
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
                    if (data.success) {
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
                    paypalBtn.querySelector('.button-text').textContent = 'Pay with PayPal';
                    paypalBtn.querySelector('.spinner-border').classList.add('d-none');
                });
            });
        }

        // Show success message if redirected from PayPal
        @if(Session::has('success'))
            successModal.show();
            document.getElementById('successMessage').textContent = '{{ Session::get('success') }}';
            
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

        // Format expiry date input (if any)
        const expiryInput = document.getElementById('expiry');
        if (expiryInput) {
            expiryInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length >= 2) {
                    value = value.slice(0, 2) + '/' + value.slice(2, 4);
                }
                e.target.value = value;
            });
        }

        // Handle modal close
        document.getElementById('paypalModal').addEventListener('hidden.bs.modal', function () {
            // Reset PayPal button if modal is closed
            if (paypalBtn) {
                paypalBtn.disabled = false;
                paypalBtn.querySelector('.button-text').textContent = 'Pay with PayPal';
                paypalBtn.querySelector('.spinner-border').classList.add('d-none');
            }
        });
    });
</script>
@endsection