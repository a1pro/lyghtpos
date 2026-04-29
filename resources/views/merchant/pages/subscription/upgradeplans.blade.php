@extends('merchant.layouts.main')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            
            <!-- Current Subscription Card -->
            @if($currentSubscription && $currentPlan)
            <div class="card mb-4 border-{{ $hasScheduledDowngrade ? 'warning' : 'success' }}">
                <div class="card-header bg-{{ $hasScheduledDowngrade ? 'warning' : 'success' }} text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-{{ $hasScheduledDowngrade ? 'clock' : 'check-circle' }} me-2"></i>
                        {{ $hasScheduledDowngrade ? 'Scheduled Downgrade' : 'Current Subscription' }}
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-7">
                            <h3 class="fw-bold mb-2">{{ $currentPlan->name }} Plan</h3>
                            <p class="text-muted mb-2">{{ $currentPlan->short_description ?? 'Your current subscription plan' }}</p>
                            <div class="d-flex flex-wrap gap-3 mb-3">
                                <span class="badge bg-primary">
                                    <i class="fas fa-calendar me-1"></i>{{ ucfirst($currentBillingType) }} Billing
                                </span>
                                <span class="badge bg-success">
                                    <i class="fas fa-hourglass-half me-1"></i>{{ max(0, $daysRemaining) }} days remaining
                                </span>
                                @if($hasScheduledDowngrade)
                                <span class="badge bg-warning text-dark">
                                    <i class="fas fa-arrow-down me-1"></i>Downgrading to {{ $scheduledDowngradeInfo['plan_name'] }} on {{ $scheduledDowngradeInfo['effective_date'] }}
                                </span>
                                @endif
                            </div>
                            <div class="mt-3">
                                <div class="progress" style="height: 10px;">
                                    @php
                                        $totalDays = $currentBillingType === 'monthly' ? 30 : 365;
                                        $usedDays = max(0, $totalDays - max(0, $daysRemaining));
                                        $percentage = $totalDays > 0 ? min(100, ($usedDays / $totalDays) * 100) : 0;
                                    @endphp
                                    <div class="progress-bar bg-success" role="progressbar" 
                                         style="width: {{ $percentage }}%" 
                                         aria-valuenow="{{ $percentage }}" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                    </div>
                                </div>
                                <small class="text-muted mt-2 d-block">
                                    {{ $usedDays }} days used of {{ $totalDays }} days
                                </small>
                            </div>
                        </div>
                        <div class="col-md-5 text-md-end">
                            <div class="bg-light p-3 rounded">
                                <small class="text-muted">Current Price</small>
                                <h4 class="mb-0">${{ number_format($currentPlan->monthly_price, 2) }}/month</h4>
                                @if($currentBillingType === 'yearly')
                                <small class="text-muted">Yearly Total: ${{ number_format($currentPlan->yearly_total ?? ($currentPlan->yearly_price * 12), 2) }}</small>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    @if($hasScheduledDowngrade && isset($scheduledDowngradeInfo['refund_amount']) && $scheduledDowngradeInfo['refund_amount'] > 0)
                    <div class="alert alert-info mt-3 mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        You will receive a refund of <strong>${{ number_format($scheduledDowngradeInfo['refund_amount'], 2) }}</strong> when the downgrade takes effect.
                        <button type="button" class="btn btn-sm btn-outline-danger float-end" id="cancelDowngradeBtn">
                            <i class="fas fa-times me-1"></i>Cancel Downgrade
                        </button>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Page Header -->
            <div class="text-center mb-5">
                <h3 class="fw-bold">{{ $currentSubscription ? 'Manage Your Plan' : 'Choose Your Plan' }}</h3>
                <p class="text-muted">
                    @if($currentSubscription)
                        @if($upgradePlans->count() > 0 && $downgradePlans->count() > 0)
                            Upgrade to get more features or downgrade to a more affordable plan
                        @elseif($upgradePlans->count() > 0)
                            Upgrade to a higher tier plan to unlock more features
                        @elseif($downgradePlans->count() > 0)
                            Downgrade to a more affordable plan
                        @else
                            You are on the perfect plan for your needs
                        @endif
                    @else
                        Select the perfect plan for your business needs
                    @endif
                </p>
            </div>

            <!-- Pricing Toggle -->
            <div class="d-flex justify-content-center mb-5">
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-primary active" id="monthlyBtn">Monthly Billing</button>
                    <button type="button" class="btn btn-outline-primary" id="yearlyBtn">Yearly Billing <span class="badge bg-success ms-1">Save 20%</span></button>
                </div>
            </div>

            <!-- UPGRADE PLANS SECTION (Higher tier plans) -->
            @if($upgradePlans->count() > 0)
            <div class="mb-5">
                <div class="text-center mb-4">
                    <h4 class="fw-bold text-success">
                        <i class="fas fa-arrow-up me-2"></i>Upgrade Options
                    </h4>
                    <p class="text-muted">Get more features and higher limits</p>
                </div>
                <div class="row">
                    @foreach($upgradePlans as $plan)
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 shadow-sm {{ $plan->is_popular ? 'border-primary border-2' : '' }} plan-card">
                            @if($plan->is_popular)
                            <div class="position-absolute top-0 start-50 translate-middle">
                                <span class="badge bg-danger rounded-pill px-3 py-2">
                                    <i class="fas fa-fire me-1"></i>Most Popular
                                </span>
                            </div>
                            @endif

                            <div class="card-body text-center">
                                <div class="mb-3 mt-2">
                                    <span class="badge bg-{{ $plan->badge_color ?? 'primary' }} px-3 py-2">{{ strtoupper($plan->name) }}</span>
                                </div>

                                <h4 class="card-title fw-bold">{{ $plan->name }} Plan</h4>
                                <p class="card-text text-muted small">{{ $plan->short_description ?? 'Perfect for growing businesses' }}</p>

                                <!-- Monthly Price -->
                                <div class="monthly-price">
                                    <h2 class="display-4 fw-bold text-primary">${{ number_format($plan->monthly_price, 0) }}</h2>
                                    <p class="text-muted">per month</p>
                                </div>

                                <!-- Yearly Price -->
                                <div class="yearly-price d-none">
                                    <h2 class="display-4 fw-bold text-primary">${{ number_format($plan->yearly_price, 0) }}</h2>
                                    <p class="text-muted">per month, billed annually</p>
                                </div>

                                <!-- Prorated Upgrade Cost -->
                                @if($currentSubscription && $remainingValue > 0)
                                @php
                                    $currentPrice = $currentBillingType === 'monthly' ? $currentPlan->monthly_price : $currentPlan->yearly_price;
                                    $newPrice = $currentBillingType === 'monthly' ? $plan->monthly_price : $plan->yearly_price;
                                    $upgradeCost = max(0, $newPrice - $remainingValue);
                                @endphp
                                <div class="alert alert-success py-2 mt-2 mb-3">
                                    <small>
                                        <i class="fas fa-tag me-1"></i>
                                        Upgrade for only <strong>${{ number_format($upgradeCost, 2) }}</strong> (prorated)
                                    </small>
                                </div>
                                @endif

                                <!-- Limits -->
                                <div class="mt-3">
                                    <div class="border-top pt-3">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span><i class="fas fa-box me-2"></i>Products:</span>
                                            <strong>{{ $plan->products_limit == -1 ? 'Unlimited' : number_format($plan->products_limit) }}</strong>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span><i class="fas fa-users me-2"></i>Customers:</span>
                                            <strong>{{ $plan->customer_limit == -1 ? 'Unlimited' : number_format($plan->customer_limit) }}</strong>
                                        </div>
                                    </div>
                                </div>

                                <!-- Features -->
                                <ul class="list-unstyled mt-4 mb-4 text-start">
                                    @php
                                        $features = is_string($plan->features) ? json_decode($plan->features, true) : ($plan->features ?? []);
                                    @endphp
                                    @foreach(array_slice($features, 0, 4) as $feature)
                                    <li class="mb-2">
                                        <i class="fas fa-check-circle text-success me-2"></i> {{ $feature }}
                                    </li>
                                    @endforeach
                                </ul>

                                <!-- Upgrade Button -->
                                <button type="button" 
                                        class="btn btn-success w-100 plan-action-btn" 
                                        data-plan-id="{{ $plan->id }}"
                                        data-plan-name="{{ $plan->name }}"
                                        data-action-type="upgrade">
                                    <i class="fas fa-arrow-up me-2"></i>Upgrade to {{ $plan->name }}
                                </button>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- DOWNGRADE PLANS SECTION (Lower tier plans) -->
            @if($downgradePlans->count() > 0)
            <div class="mb-5">
                <div class="text-center mb-4">
                    <h4 class="fw-bold text-warning">
                        <i class="fas fa-arrow-down me-2"></i>Downgrade Options
                    </h4>
                    <p class="text-muted">Choose a more affordable plan that fits your current needs</p>
                </div>
                <div class="row">
                    @foreach($downgradePlans as $plan)
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 shadow-sm plan-card">
                            <div class="card-body text-center">
                                <div class="mb-3 mt-2">
                                    <span class="badge bg-secondary px-3 py-2">{{ strtoupper($plan->name) }}</span>
                                </div>

                                <h4 class="card-title fw-bold">{{ $plan->name }} Plan</h4>
                                <p class="card-text text-muted small">{{ $plan->short_description ?? 'Simple and affordable' }}</p>

                                <!-- Monthly Price -->
                                <div class="monthly-price">
                                    <h2 class="display-4 fw-bold text-secondary">${{ number_format($plan->monthly_price, 0) }}</h2>
                                    <p class="text-muted">per month</p>
                                </div>

                                <!-- Yearly Price -->
                                <div class="yearly-price d-none">
                                    <h2 class="display-4 fw-bold text-secondary">${{ number_format($plan->yearly_price, 0) }}</h2>
                                    <p class="text-muted">per month, billed annually</p>
                                </div>

                                <!-- Savings Info -->
                                @if($currentSubscription && $remainingValue > 0)
                                @php
                                    $currentPrice = $currentBillingType === 'monthly' ? $currentPlan->monthly_price : $currentPlan->yearly_price;
                                    $newPrice = $currentBillingType === 'monthly' ? $plan->monthly_price : $plan->yearly_price;
                                    $monthlySavings = $currentPrice - $newPrice;
                                    $refundAmount = max(0, $remainingValue - $newPrice);
                                @endphp
                                <div class="alert alert-info py-2 mt-2 mb-3">
                                    <small>
                                        <i class="fas fa-save me-1"></i>
                                        Save <strong>${{ number_format($monthlySavings, 2) }}</strong>/{{ $currentBillingType === 'monthly' ? 'month' : 'year' }}
                                        @if($refundAmount > 0)
                                        <br><i class="fas fa-money-bill-wave me-1"></i>Refund: <strong>${{ number_format($refundAmount, 2) }}</strong>
                                        @endif
                                    </small>
                                </div>
                                @endif

                                <!-- Limits -->
                                <div class="mt-3">
                                    <div class="border-top pt-3">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span><i class="fas fa-box me-2"></i>Products:</span>
                                            <strong>{{ $plan->products_limit == -1 ? 'Unlimited' : number_format($plan->products_limit) }}</strong>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span><i class="fas fa-users me-2"></i>Customers:</span>
                                            <strong>{{ $plan->customer_limit == -1 ? 'Unlimited' : number_format($plan->customer_limit) }}</strong>
                                        </div>
                                    </div>
                                </div>

                                <!-- Features -->
                                <ul class="list-unstyled mt-4 mb-4 text-start">
                                    @php
                                        $features = is_string($plan->features) ? json_decode($plan->features, true) : ($plan->features ?? []);
                                    @endphp
                                    @foreach(array_slice($features, 0, 4) as $feature)
                                    <li class="mb-2">
                                        <i class="fas fa-check-circle text-success me-2"></i> {{ $feature }}
                                    </li>
                                    @endforeach
                                </ul>

                                <!-- Downgrade Button -->
                                <button type="button" 
                                        class="btn btn-outline-warning w-100 plan-action-btn" 
                                        data-plan-id="{{ $plan->id }}"
                                        data-plan-name="{{ $plan->name }}"
                                        data-action-type="downgrade">
                                    <i class="fas fa-arrow-down me-2"></i>Downgrade to {{ $plan->name }}
                                </button>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- No Plans Available Message -->
            @if($upgradePlans->count() == 0 && $downgradePlans->count() == 0 && !$currentSubscription)
            <div class="alert alert-info text-center">
                <i class="fas fa-info-circle me-2"></i>
                No plans available at the moment. Please check back later.
            </div>
            @endif

            <!-- Current Plan Only Message -->
            @if($currentSubscription && $upgradePlans->count() == 0 && $downgradePlans->count() == 0)
            <div class="alert alert-success text-center">
                <i class="fas fa-check-circle me-2"></i>
                You are on the perfect plan for your needs!
                <div class="mt-3">
                    <a href="{{ route('merchant.dashboard') }}" class="btn btn-primary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>
            @endif

            <!-- Footer Note -->
            <div class="text-center mt-4">
                <small class="text-muted">
                    <i class="fas fa-info-circle"></i>
                    @if($currentSubscription)
                        Upgrades take effect immediately with prorated pricing. Downgrades take effect at the end of your billing cycle.
                    @else
                        All plans include secure payments powered by PayPal.
                    @endif
                </small>
            </div>
        </div>
    </div>
</div>

<!-- Plan Action Modal -->
<div class="modal fade" id="planActionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" id="modalHeader">
                <h5 class="modal-title" id="planActionModalLabel">
                    <i class="fas me-2" id="modalIcon"></i>
                    <span id="modalTitle">Loading...</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="planDetailsContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Processing Modal -->
<div class="modal fade" id="processingModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-body text-center py-4">
                <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <h5 class="mb-2" id="processingMessage">Processing...</h5>
                <p class="text-muted small mb-0">Please wait while we process your request</p>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // DOM Elements
    const monthlyBtn = document.getElementById('monthlyBtn');
    const yearlyBtn = document.getElementById('yearlyBtn');
    const monthlyPrices = document.querySelectorAll('.monthly-price');
    const yearlyPrices = document.querySelectorAll('.yearly-price');
    const planActionBtns = document.querySelectorAll('.plan-action-btn');
    const cancelDowngradeBtn = document.getElementById('cancelDowngradeBtn');
    
    // Modals
    const planActionModal = new bootstrap.Modal(document.getElementById('planActionModal'));
    const processingModal = new bootstrap.Modal(document.getElementById('processingModal'));
    
    // Routes
    const getPlanDetailsUrl = '/merchant/subscription/plan-details/';
    const processPlanChangeUrl = '{{ route("merchant.subscription.plan.change") }}';
    const cancelDowngradeUrl = '{{ route("merchant.subscription.downgrade.cancel") }}';
    
    let currentBillingType = 'monthly';
    let selectedPlanId = null;
    let selectedPlanName = null;
    let selectedActionType = null;
    
    function numberFormat(number) {
        return new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(number);
    }
    
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
        currentBillingType = 'monthly';
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
    }
    
    if (monthlyBtn) monthlyBtn.addEventListener('click', showMonthly);
    if (yearlyBtn) yearlyBtn.addEventListener('click', showYearly);
    
    // Handle plan action button clicks
    planActionBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            selectedPlanId = this.dataset.planId;
            selectedPlanName = this.dataset.planName;
            selectedActionType = this.dataset.actionType;
            
            processingModal.show();
            
            fetch(getPlanDetailsUrl + selectedPlanId, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                }
            })
            .then(response => response.json())
            .then(data => {
                processingModal.hide();
                if (data.success) {
                    displayPlanModal(data.data);
                } else {
                    alert('Error: ' + (data.message || 'Failed to load plan details'));
                }
            })
            .catch(error => {
                processingModal.hide();
                console.error('Error:', error);
                alert('Error loading plan details. Please try again.');
            });
        });
    });
    
    // Display plan modal
    function displayPlanModal(plan) {
        const modalContent = document.getElementById('planDetailsContent');
        const modalHeader = document.getElementById('modalHeader');
        const modalIcon = document.getElementById('modalIcon');
        const modalTitle = document.getElementById('modalTitle');
        
        const planPrice = currentBillingType === 'monthly' 
            ? plan.monthly_price_formatted 
            : plan.yearly_price_formatted;
        
        const proration = plan.proration;
        const isUpgrade = plan.action_type === 'upgrade';
        const isDowngrade = plan.action_type === 'downgrade';
        
        if (isUpgrade) {
            modalHeader.className = 'modal-header bg-success text-white';
            modalIcon.className = 'fas fa-arrow-up me-2';
            modalTitle.textContent = `Upgrade to ${plan.name} Plan`;
        } else if (isDowngrade) {
            modalHeader.className = 'modal-header bg-warning text-dark';
            modalIcon.className = 'fas fa-arrow-down me-2';
            modalTitle.textContent = `Downgrade to ${plan.name} Plan`;
        } else {
            modalHeader.className = 'modal-header bg-primary text-white';
            modalIcon.className = 'fab fa-paypal me-2';
            modalTitle.textContent = `Subscribe to ${plan.name} Plan`;
        }
        
        let html = `
            <div class="alert alert-info mb-4">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle fa-2x text-info"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="alert-heading mb-1">${plan.name} Plan</h6>
                        <p class="mb-0">${plan.short_description || 'Perfect for your business needs'}</p>
                    </div>
                </div>
            </div>
            
            <div class="payment-summary bg-light p-4 rounded mb-4">
                <h5 class="mb-3">Payment Summary</h5>
                <div class="d-flex justify-content-between mb-2">
                    <span>Plan:</span>
                    <strong>${plan.name} (${currentBillingType === 'monthly' ? 'Monthly' : 'Yearly'})</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Price:</span>
                    <strong>${planPrice}</strong>
                </div>`;
        
        if (proration && proration.type === 'upgrade') {
            html += `
                <div class="d-flex justify-content-between mb-2 text-success">
                    <span><i class="fas fa-minus-circle me-1"></i>Remaining Credit:</span>
                    <strong>$${numberFormat(proration.remaining_value)}</strong>
                </div>
                <hr>
                <div class="d-flex justify-content-between">
                    <span>Amount to Pay Today:</span>
                    <strong class="text-success h5 mb-0">$${numberFormat(proration.amount_to_pay)}</strong>
                </div>
                <div class="alert alert-success mt-3 mb-0 small">
                    <i class="fas fa-info-circle me-1"></i>
                    You have $${numberFormat(proration.remaining_value)} credit from your current plan.
                </div>`;
        } else if (proration && proration.type === 'downgrade') {
            html += `
                <hr>
                <div class="d-flex justify-content-between mb-2">
                    <span>Your Credit:</span>
                    <strong>$${numberFormat(proration.remaining_value)}</strong>
                </div>
                <div class="d-flex justify-content-between mb-2 text-success">
                    <span><i class="fas fa-arrow-down me-1"></i>Refund Amount:</span>
                    <strong>$${numberFormat(proration.refund_amount)}</strong>
                </div>
                <div class="alert alert-warning mt-3 mb-0 small">
                    <i class="fas fa-clock me-1"></i>
                    This downgrade will take effect on <strong>${proration.effective_date}</strong>. 
                    You will get a refund of <strong>$${numberFormat(proration.refund_amount)}</strong>.
                </div>`;
        } else {
            html += `
                <hr>
                <div class="d-flex justify-content-between">
                    <span>Total:</span>
                    <strong class="text-primary h5 mb-0">${planPrice}</strong>
                </div>`;
        }
        
        html += `
            </div>
            
            <div class="text-center py-3">
                <div class="mb-3">
                    <img src="https://www.paypalobjects.com/webstatic/icon/pp258.png" alt="PayPal" style="height: 50px;">
                </div>`;
        
        if (isDowngrade) {
            html += `
                <button type="button" class="btn btn-warning btn-lg w-100" id="confirmDowngradeActionBtn">
                    <i class="fas fa-check-circle me-2"></i>
                    Confirm Downgrade
                </button>`;
        } else {
            html += `
                <button type="button" class="btn btn-primary btn-lg w-100" id="confirmPaymentBtn">
                    <i class="fab fa-paypal me-2"></i>
                    <span class="button-text">Pay with PayPal</span>
                    <span class="spinner-border spinner-border-sm d-none ms-2" role="status" aria-hidden="true"></span>
                </button>`;
        }
        
        html += `
                <p class="text-muted small mt-3 mb-0">
                    <i class="fas fa-lock me-1"></i>
                    Secure payment via PayPal
                </p>
            </div>
        `;
        
        modalContent.innerHTML = html;
        planActionModal.show();
        
        if (isDowngrade) {
            const confirmDowngradeBtn = document.getElementById('confirmDowngradeActionBtn');
            if (confirmDowngradeBtn) {
                confirmDowngradeBtn.addEventListener('click', () => confirmDowngrade(plan));
            }
        } else {
            const confirmPaymentBtn = document.getElementById('confirmPaymentBtn');
            if (confirmPaymentBtn) {
                confirmPaymentBtn.addEventListener('click', () => processPayment(plan));
            }
        }
    }
    
    function processPayment(plan) {
        const button = document.getElementById('confirmPaymentBtn');
        const buttonText = button?.querySelector('.button-text');
        const spinner = button?.querySelector('.spinner-border');
        
        if (button) {
            button.disabled = true;
            if (buttonText) buttonText.textContent = 'Redirecting to PayPal...';
            if (spinner) spinner.classList.remove('d-none');
        }
        
        planActionModal.hide();
        processingModal.show();
        
        fetch(processPlanChangeUrl, {
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
        .then(response => response.json())
        .then(data => {
            if (data.success && data.approval_url) {
                window.location.href = data.approval_url;
            } else {
                throw new Error(data.message || 'Failed to process payment');
            }
        })
        .catch(error => {
            processingModal.hide();
            alert(error.message || 'An error occurred. Please try again.');
            if (button) {
                button.disabled = false;
                if (buttonText) buttonText.textContent = 'Pay with PayPal';
                if (spinner) spinner.classList.add('d-none');
            }
            planActionModal.show();
        });
    }
    
    function confirmDowngrade(plan) {
        const button = document.getElementById('confirmDowngradeActionBtn');
        if (button) {
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
        }
        
        planActionModal.hide();
        processingModal.show();
        
        fetch(processPlanChangeUrl, {
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
        .then(response => response.json())
        .then(data => {
            processingModal.hide();
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.message || 'Failed to process downgrade');
                if (button) {
                    button.disabled = false;
                    button.innerHTML = 'Confirm Downgrade';
                }
            }
        })
        .catch(error => {
            processingModal.hide();
            alert('An error occurred. Please try again.');
            if (button) {
                button.disabled = false;
                button.innerHTML = 'Confirm Downgrade';
            }
        });
    }
    
    if (cancelDowngradeBtn) {
        cancelDowngradeBtn.addEventListener('click', function() {
            if (confirm('Are you sure you want to cancel the scheduled downgrade?')) {
                processingModal.show();
                
                fetch(cancelDowngradeUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    processingModal.hide();
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert(data.message || 'Failed to cancel downgrade');
                    }
                })
                .catch(error => {
                    processingModal.hide();
                    alert('An error occurred. Please try again.');
                });
            }
        });
    }
    
    showMonthly();
    
    @if(Session::has('success'))
        Swal.fire({ icon: 'success', title: 'Success!', text: '{{ Session::get('success') }}', timer: 3000 });
    @endif
    
    @if(Session::has('error'))
        alert('{{ Session::get('error') }}');
    @endif
});
</script>
@endsection

<style>
.plan-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.plan-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.15) !important;
}
.progress {
    background-color: #e9ecef;
    border-radius: 10px;
}
.progress-bar {
    border-radius: 10px;
    transition: width 0.5s ease;
}
</style>