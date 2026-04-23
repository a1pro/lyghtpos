@extends('merchant.layouts.main')
@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="mb-0">Dashboard</h3> 
                        
                        @php
                            use App\Models\JoiningFees;
                            use App\Models\UserJoiningFees;
                            
                            $userId = auth()->id();
                            $hasPaidJoiningFee = UserJoiningFees::where('user_id', $userId)
                                ->where('status', 'completed')
                                ->exists();
                            
                            $joiningFee = JoiningFees::where('role_id', 2)
                                ->where('status', 'active')
                                ->first();
                            
                            $showPopup = !$hasPaidJoiningFee && $joiningFee;
                            $joiningFeeAmount = $joiningFee ? $joiningFee->amount : null;
                        @endphp

                        @if($showPopup)
                        <!-- Direct Payment Button - No Modal -->
                        <a href="{{ route('merchant.payments.create', ['amount' => $joiningFeeAmount]) }}" 
                           class="btn btn-info"
                           onclick="sessionStorage.setItem('payment_amount', '{{ $joiningFeeAmount }}');">
                            <i class="fas fa-credit-card me-2"></i>Complete Payment ${{ number_format($joiningFeeAmount, 2) }}
                        </a>
                        @else
                            @if($hasPaidJoiningFee)
                                <span class="badge bg-success">
                                    <i class="fas fa-check-circle me-1"></i>
                                    Joining Fee Paid
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
@if(isset($showPopup) && $showPopup)
<script>
document.addEventListener('DOMContentLoaded', function() {
    sessionStorage.setItem('payment_amount', '{{ $joiningFeeAmount }}');
});
</script>
@endif

@endsection