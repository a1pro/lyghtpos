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
                            <button type="button" class="btn btn-primary active" id="monthlyBtn">Monthly</button>
                            <button type="button" class="btn btn-outline-primary" id="yearlyBtn">Yearly</button>
                        </div>
                        @php
                        $firstPlan = $plans->first();
                        @endphp
                        @if($firstPlan && $firstPlan->yearly_savings > 0)
                        <span class="badge bg-success ms-3 align-self-center">Save {{ $firstPlan->yearly_savings }}% with yearly</span>
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

                                    <!-- Buy Button -->
                                    <a href="{{ route('merchant.subscription.demo.card') }}" class="btn {{ $plan->is_popular ? 'btn-primary' : 'btn-outline-' . $plan->badge_color }} w-100">
                                        Choose {{ $plan->name }}
                                    </a>
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
@endsection

@section('js')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const monthlyBtn = document.getElementById('monthlyBtn');
        const yearlyBtn = document.getElementById('yearlyBtn');
        const monthlyPrices = document.querySelectorAll('.monthly-price');
        const yearlyPrices = document.querySelectorAll('.yearly-price');

        function showMonthly() {
            monthlyBtn.classList.add('btn-primary', 'active');
            monthlyBtn.classList.remove('btn-outline-primary');
            yearlyBtn.classList.add('btn-outline-primary');
            yearlyBtn.classList.remove('btn-primary', 'active');

            monthlyPrices.forEach(price => price.classList.remove('d-none'));
            yearlyPrices.forEach(price => price.classList.add('d-none'));
        }

        function showYearly() {
            yearlyBtn.classList.add('btn-primary', 'active');
            yearlyBtn.classList.remove('btn-outline-primary');
            monthlyBtn.classList.add('btn-outline-primary');
            monthlyBtn.classList.remove('btn-primary', 'active');

            monthlyPrices.forEach(price => price.classList.add('d-none'));
            yearlyPrices.forEach(price => price.classList.remove('d-none'));
        }

        monthlyBtn.addEventListener('click', showMonthly);
        yearlyBtn.addEventListener('click', showYearly);

        // Initialize monthly view
        showMonthly();
    });

</script>
@endsection
