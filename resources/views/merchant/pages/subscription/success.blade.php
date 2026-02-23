{{-- resources/views/merchant/pages/subscription/success.blade.php --}}
@extends('merchant.layouts.main-single-page')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body text-center py-5">
                    <!-- Success Icon -->
                    <div class="mb-4">
                        <div class="bg-success bg-opacity-10 rounded-circle d-inline-block p-4">
                            <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                        </div>
                    </div>

                    <!-- Success Message -->
                    <h2 class="fw-bold mb-3">Thank You for Subscribing!</h2>
                    <p class="text-muted mb-4">Your subscription has been successfully activated.</p>

                    <!-- Subscription Details -->
                    @if(isset($details))
                    <div class="card bg-light mb-4">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Subscription Details</h5>
                            <div class="row">
                                <div class="col-sm-6 text-start">
                                    <p class="mb-2"><strong>Plan:</strong> {{ $details['plan_name'] }}</p>
                                    <p class="mb-2"><strong>Billing:</strong> {{ ucfirst($details['billing_type']) }}</p>
                                    <p class="mb-0"><strong>Amount:</strong> ${{ number_format($details['amount'], 2) }}</p>
                                </div>
                                <div class="col-sm-6 text-start">
                                    <p class="mb-2"><strong>Transaction ID:</strong><br>
                                        <small class="text-muted">{{ $details['transaction_id'] }}</small>
                                    </p>
                                    <p class="mb-0"><strong>Valid Until:</strong> {{ $details['end_date'] }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Action Buttons -->
                    <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                        <a href="{{ route('merchant.dashboard') }}" class="btn btn-primary btn-lg px-4">
                            <i class="fas fa-tachometer-alt me-2"></i>Go to Dashboard
                        </a>
                        <a href="{{ route('merchant.subscription.plans') }}" class="btn btn-outline-primary btn-lg px-4">
                            <i class="fas fa-arrow-left me-2"></i>Back to Plans
                        </a>
                    </div>

                    <!-- Email Confirmation -->
                    <p class="text-muted small mt-4">
                        <i class="fas fa-envelope me-1"></i>
                        A confirmation email has been sent to your email address.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection