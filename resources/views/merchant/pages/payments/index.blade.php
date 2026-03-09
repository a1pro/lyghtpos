@extends('merchant.layouts.main')

@section('title', 'Payment Dashboard')

@push('styles')
<style>
    .stat-card {
        transition: transform 0.2s;
        border: none;
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
    }
    .stat-icon {
        font-size: 2.5rem;
        opacity: 0.7;
    }
    .quick-action-card {
        border: 1px solid #dee2e6;
        border-radius: 10px;
        padding: 1.5rem;
        text-align: center;
        transition: all 0.3s;
        background: white;
    }
    .quick-action-card:hover {
        background: #f8f9fa;
        border-color: #0d6efd;
        cursor: pointer;
    }
    .quick-action-icon {
        font-size: 2rem;
        color: #0d6efd;
        margin-bottom: 1rem;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Payment Dashboard</h1>
        <a href="{{ route('merchant.payments.create') }}" class="btn btn-primary">
            <i class="fas fa-plus-circle"></i> New Payment
        </a>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Payments</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">${{ number_format($totalAmount ?? 0, 2) }}</div>
                            <div class="small mt-2">{{ $totalCount ?? 0 }} transactions</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-credit-card fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Successful Payments</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">${{ number_format($successfulAmount ?? 0, 2) }}</div>
                            <div class="small mt-2">{{ $successfulCount ?? 0 }} transactions</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                This Month</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">${{ number_format($monthlyAmount ?? 0, 2) }}</div>
                            <div class="small mt-2">{{ $monthlyCount ?? 0 }} transactions</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Pending/Failed</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $pendingCount ?? 0 }}</div>
                            <div class="small mt-2">{{ $failedCount ?? 0 }} failed</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <h5 class="mb-3">Quick Actions</h5>
        </div>
        <div class="col-md-3 mb-3">
            <div class="quick-action-card" onclick="window.location='{{ route('merchant.payments.create') }}'">
                <div class="quick-action-icon">
                    <i class="fas fa-credit-card"></i>
                </div>
                <h6>Make Payment</h6>
                <small class="text-muted">Process a new card payment</small>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="quick-action-card" onclick="window.location='{{ route('merchant.payments.history') }}'">
                <div class="quick-action-icon">
                    <i class="fas fa-history"></i>
                </div>
                <h6>Payment History</h6>
                <small class="text-muted">View all transactions</small>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="quick-action-card" onclick="window.location='{{ route('merchant.dashboard') }}'">
                <div class="quick-action-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h6>Dashboard</h6>
                <small class="text-muted">Return to main dashboard</small>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="quick-action-card" onclick="window.location='{{ route('merchant.settings.index') }}'">
                <div class="quick-action-icon">
                    <i class="fas fa-cog"></i>
                </div>
                <h6>Settings</h6>
                <small class="text-muted">Configure payment settings</small>
            </div>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Transactions</h6>
                    <a href="{{ route('merchant.payments.history') }}" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    @if(isset($recentPayments) && $recentPayments->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>Transaction ID</th>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Card</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentPayments as $payment)
                                    <tr>
                                        <td>
                                            <small>{{ substr($payment->transaction_id, 0, 10) }}...</small>
                                        </td>
                                        <td>{{ $payment->created_at->format('M d, Y H:i') }}</td>
                                        <td>${{ number_format($payment->amount, 2) }}</td>
                                        <td>
                                            @if($payment->card_last_four)
                                                **** {{ $payment->card_last_four }}
                                                <br>
                                                <small class="text-muted">{{ ucfirst($payment->card_type) }}</small>
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $statusClass = match($payment->status) {
                                                    'completed' => 'success',
                                                    'pending' => 'warning',
                                                    'failed' => 'danger',
                                                    'refunded' => 'info',
                                                    default => 'secondary'
                                                };
                                            @endphp
                                            <span class="badge bg-{{ $statusClass }}">
                                                {{ ucfirst($payment->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <a href="{{ route('merchant.payments.show', $payment->id) }}" 
                                               class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-credit-card fa-3x text-gray-300 mb-3"></i>
                            <p class="text-muted">No transactions yet.</p>
                            <a href="{{ route('merchant.payments.create') }}" class="btn btn-primary">
                                Make Your First Payment
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Optional: Add any JavaScript for interactivity
    $(document).ready(function() {
        // Initialize tooltips
        $('[data-toggle="tooltip"]').tooltip();
    });
</script>
@endpush