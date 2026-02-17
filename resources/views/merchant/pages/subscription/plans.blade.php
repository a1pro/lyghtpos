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
                                            data-bs-target="#cardModal">
                                        Choose {{ $plan->name }}
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
                            All plans include a 14-day free trial. No credit card required.
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
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Card Details Modal -->
<div class="modal fade" id="cardModal" tabindex="-1" aria-labelledby="cardModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cardModalLabel">Enter Card Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Selected Plan Summary -->
                <div class="alert alert-info mb-4" id="selectedPlanSummary" style="display: none;">
                    <h6 class="alert-heading" id="selectedPlanName"></h6>
                    <p class="mb-0" id="selectedPlanPrice"></p>
                    <small id="selectedPlanDuration"></small>
                </div>

                <form id="paymentForm">
                    @csrf
                    <input type="hidden" name="plan_id" id="plan_id">
                    <input type="hidden" name="billing_type" id="billing_type" value="monthly">

                    <div class="mb-3">
                        <label for="card_number" class="form-label">Card Number</label>
                        <input type="text" class="form-control" id="card_number" name="card_number" value="4242 4242 4242 4242" required>
                        <div class="invalid-feedback" id="card_number_error"></div>
                    </div>

                    <div class="mb-3">
                        <label for="card_holder" class="form-label">Card Holder</label>
                        <input type="text" class="form-control" id="card_holder" name="card_holder" value="John Doe" required>
                        <div class="invalid-feedback" id="card_holder_error"></div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="expiry" class="form-label">Expiry Date</label>
                            <input type="text" class="form-control" id="expiry" name="expiry" value="12/25" placeholder="MM/YY" required>
                            <div class="invalid-feedback" id="expiry_error"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="cvc" class="form-label">CVC</label>
                            <input type="text" class="form-control" id="cvc" name="cvc" value="123" required>
                            <div class="invalid-feedback" id="cvc_error"></div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="submitPaymentBtn">
                    <span class="spinner-border spinner-border-sm d-none" id="paymentSpinner" role="status" aria-hidden="true"></span>
                    Pay Now
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="successModalLabel">Success!</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-4">
                <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                <h4 class="mt-3" id="successMessage">Subscription started successfully!</h4>
                <p class="text-muted" id="transactionIdDisplay"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="redirectToDashboard">Go to Dashboard</button>
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
        const billingTypeInput = document.getElementById('billing_type');
        const planIdInput = document.getElementById('plan_id');
        const selectedPlanSummary = document.getElementById('selectedPlanSummary');
        const selectedPlanName = document.getElementById('selectedPlanName');
        const selectedPlanPrice = document.getElementById('selectedPlanPrice');
        const selectedPlanDuration = document.getElementById('selectedPlanDuration');
        const submitPaymentBtn = document.getElementById('submitPaymentBtn');
        const paymentSpinner = document.getElementById('paymentSpinner');
        const paymentForm = document.getElementById('paymentForm');
        
        // Define routes
        const processSubscriptionUrl = '{{ route("merchant.subscription.process") }}';
        const planDetailsBaseUrl = '/merchant/subscription/plan-details/';
        const dashboardUrl = '{{ route("merchant.dashboard") }}';
        
        console.log('Process Subscription URL:', processSubscriptionUrl);
        console.log('Dashboard URL:', dashboardUrl);
        
        let currentBillingType = 'monthly';
        let selectedPlan = null;

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
            if (billingTypeInput) billingTypeInput.value = 'monthly';
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
            if (billingTypeInput) billingTypeInput.value = 'yearly';
        }

        // Add event listeners for billing toggle
        if (monthlyBtn) monthlyBtn.addEventListener('click', showMonthly);
        if (yearlyBtn) yearlyBtn.addEventListener('click', showYearly);

        // Handle plan selection
        choosePlanBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                const planId = this.dataset.planId;
                const planName = this.dataset.planName;
                
                console.log('Plan selected:', { planId, planName, billingType: currentBillingType });
                
                // Set plan ID in hidden input
                if (planIdInput) planIdInput.value = planId;
                if (billingTypeInput) billingTypeInput.value = currentBillingType;
                
                // Fetch plan details
                const planDetailsUrl = planDetailsBaseUrl + planId;
                console.log('Fetching plan details from:', planDetailsUrl);
                
                // Show loading state
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
                        if (selectedPlanName) {
                            selectedPlanName.textContent = `${selectedPlan.name} Plan (${currentBillingType === 'monthly' ? 'Monthly' : 'Yearly'})`;
                        }
                        
                        const price = currentBillingType === 'monthly' 
                            ? selectedPlan.monthly_price_formatted 
                            : selectedPlan.yearly_price_formatted;
                        
                        const totalYearly = currentBillingType === 'yearly' 
                            ? ` (${selectedPlan.yearly_total}/yr)` 
                            : '';
                        
                        if (selectedPlanPrice) {
                            selectedPlanPrice.textContent = `Amount: ${price}${totalYearly}`;
                        }
                        
                        const days = currentBillingType === 'monthly' ? '30 days' : '365 days';
                        if (selectedPlanDuration) {
                            selectedPlanDuration.textContent = `Duration: ${days}`;
                        }
                        
                        if (selectedPlanSummary) {
                            selectedPlanSummary.style.display = 'block';
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

        // Handle payment submission
        if (submitPaymentBtn) {
            submitPaymentBtn.addEventListener('click', function() {
                // Clear previous errors
                document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                document.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
                
                // Validate form
                const cardNumber = document.getElementById('card_number')?.value;
                const cardHolder = document.getElementById('card_holder')?.value;
                const expiry = document.getElementById('expiry')?.value;
                const cvc = document.getElementById('cvc')?.value;
                
                if (!cardNumber || !cardHolder || !expiry || !cvc) {
                    alert('Please fill in all card details');
                    return;
                }
                
                // Show spinner
                if (paymentSpinner) paymentSpinner.classList.remove('d-none');
                submitPaymentBtn.disabled = true;
                
                // Get form data
                const formData = new FormData(paymentForm);
                
                // Debug: Log form data
                console.log('Submitting to:', processSubscriptionUrl);
                for (let pair of formData.entries()) {
                    console.log(pair[0] + ': ' + pair[1]);
                }
                
                // Submit payment
                fetch(processSubscriptionUrl, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                    }
                })
                .then(async response => {
                    console.log('Payment response status:', response.status);
                    
                    if (!response.ok) {
                        if (response.status === 302) {
                            throw new Error('Redirect detected. Please check if you are logged in.');
                        }
                        if (response.status === 422) {
                            return response.json();
                        }
                        const errorText = await response.text();
                        console.error('Error response:', errorText);
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Payment response:', data);
                    
                    if (paymentSpinner) paymentSpinner.classList.add('d-none');
                    submitPaymentBtn.disabled = false;
                    
                    if (data.success) {
                        // Hide card modal
                        const cardModal = bootstrap.Modal.getInstance(document.getElementById('cardModal'));
                        if (cardModal) {
                            cardModal.hide();
                        }
                        
                        // Show success modal
                        const successMessageEl = document.getElementById('successMessage');
                        const transactionIdEl = document.getElementById('transactionIdDisplay');
                        
                        if (successMessageEl) successMessageEl.textContent = data.message;
                        if (transactionIdEl) transactionIdEl.textContent = `Transaction ID: ${data.transaction_id}`;
                        
                        const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                        successModal.show();
                        
                        // Reset form
                        if (paymentForm) paymentForm.reset();
                        if (selectedPlanSummary) selectedPlanSummary.style.display = 'none';
                        
                        // Auto redirect after 3 seconds
                        setTimeout(function() {
                            window.location.href = dashboardUrl;
                        }, 3000);
                    } else {
                        // Handle validation errors
                        if (data.errors) {
                            Object.keys(data.errors).forEach(key => {
                                const input = document.getElementById(key);
                                const errorDiv = document.getElementById(key + '_error');
                                if (input && errorDiv) {
                                    input.classList.add('is-invalid');
                                    errorDiv.textContent = data.errors[key][0];
                                }
                            });
                        } else {
                            alert(data.message || 'An error occurred. Please try again.');
                        }
                    }
                })
                .catch(error => {
                    if (paymentSpinner) paymentSpinner.classList.add('d-none');
                    submitPaymentBtn.disabled = false;
                    console.error('Fetch error:', error);
                    alert(error.message || 'An error occurred. Please try again. Check console for details.');
                });
            });
        }

        // Initialize monthly view
        showMonthly();

        // Add click handler for redirect button
        const redirectBtn = document.getElementById('redirectToDashboard');
        if (redirectBtn) {
            redirectBtn.addEventListener('click', function() {
                window.location.href = dashboardUrl;
            });
        }
    });
</script>
@endsection