@extends('merchant.layouts.main')
@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="mb-0">Dashboard</h3> 
                        @php $paymentCheck = payment_check(); @endphp

                        @if($paymentCheck['show_popup'])
                        <!-- Direct Payment Button - No Modal -->
                        <a href="{{ route('merchant.payments.create', ['amount' => $paymentCheck['pledge_value']]) }}" 
                           class="btn btn-info"
                           onclick="sessionStorage.setItem('payment_amount', '{{ $paymentCheck['pledge_value'] }}');">
                            <i class="fas fa-credit-card me-2"></i>Complete Payment ${{ number_format($paymentCheck['pledge_value'], 2) }}
                        </a>
                        @else
                            @if(!$paymentCheck['all_layers_approved'] && $paymentCheck['total_layers'] > 0)
                                <span class="badge bg-warning">
                                    <i class="fas fa-clock me-1"></i>
                                    Governance Checks: {{ $paymentCheck['approved_count'] }}/{{ $paymentCheck['total_layers'] }} Approved
                                </span>
                            @elseif(in_array($paymentCheck['payment_status'], ['approved', 'completed', 'success']))
                                <span class="badge bg-success">
                                    <i class="fas fa-check-circle me-1"></i>
                                    Payment Completed
                                </span>
                            @endif
                        @endif
                    </div>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-4">
                            <div class="card h-100 text-center">
                                <div class="card-body">
                                    <div class="mb-3">
                                        <i class="fas fa-file-invoice-dollar fa-3x text-primary"></i>
                                    </div>
                                    <h5 class="card-title">External Links</h5>
                                    <p class="card-text text-muted">Visit external links page to access more of our features</p>
                                    <a href="{{ route('merchant.external-links') }}" class="btn btn-outline-primary">
                                        View &rarr;
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Store payment amount on page load -->
@if($paymentCheck['show_popup'])
<script>
document.addEventListener('DOMContentLoaded', function() {
    sessionStorage.setItem('payment_amount', '{{ $paymentCheck['pledge_value'] }}');
});
</script>
@endif

@endsection
