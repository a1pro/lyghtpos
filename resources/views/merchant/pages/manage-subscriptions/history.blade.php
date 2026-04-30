@extends('merchant.layouts.main')

@section('title', 'Subscription History')

@section('content-header', 'Subscription History')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Current Active Plan Card -->
            @if($stats['current_plan'])
            <div class="card mb-4 border-info">
                <div class="card-header bg-info bg-opacity-10 border-bottom border-info">
                    <h5 class="mb-0 text-info">
                        <i class="fas fa-check-circle me-2"></i>Current Active Plan
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="d-flex flex-wrap align-items-center gap-3">
                                <div>
                                    <h4 class="mb-0">{{ $stats['current_plan']['name'] }}</h4>
                                    <p class="text-muted mb-0">
                                        <i class="fas fa-calendar-alt me-1"></i>
                                        Billing: {{ ucfirst($stats['current_plan']['billing_type']) }}
                                    </p>
                                </div>
                                <div class="vr d-none d-md-block"></div>
                                <div>
                                    <small class="text-muted d-block">Amount Paid</small>
                                    <strong class="h5 text-success">${{ number_format($stats['current_plan']['amount_paid'], 2) }}</strong>
                                </div>
                                <div class="vr d-none d-md-block"></div>
                                <div>
                                    <small class="text-muted d-block">Valid Until</small>
                                    <strong>{{ Carbon\Carbon::parse($stats['current_plan']['end_date'])->format('F d, Y') }}</strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-md-end mt-3 mt-md-0">
                            <span class="badge bg-info fs-6 px-3 py-2">
                                <i class="fas fa-sync-alt me-1"></i> Active Now
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Statistics Cards - Simple & Clean -->
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="text-muted mb-1">Total Subscriptions</p>
                                    <h3 class="mb-0">{{ $stats['total_subscriptions'] }}</h3>
                                    <small class="text-success">All time</small>
                                </div>
                                <div class="bg-light rounded p-2">
                                    <i class="fas fa-history text-primary fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="text-muted mb-1">Total Spent</p>
                                    <h3 class="mb-0">${{ number_format($stats['total_spent'], 2) }}</h3>
                                    <small class="text-muted">On past subscriptions</small>
                                </div>
                                <div class="bg-light rounded p-2">
                                    <i class="fas fa-dollar-sign text-success fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="text-muted mb-1">History Records</p>
                                    <h3 class="mb-0">{{ $stats['history_count'] }}</h3>
                                    <small class="text-muted">Past subscriptions</small>
                                </div>
                                <div class="bg-light rounded p-2">
                                    <i class="fas fa-list-ul text-info fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-sliders-h me-2"></i>Filter History
                    </h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('merchant.manage-subscriptions.history') }}" id="filterForm">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Status</label>
                                <select name="status" class="form-select form-select-sm">
                                    <option value="all">All Status</option>
                                    <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Expired/Changed</option>
                                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label fw-semibold">Billing Type</label>
                                <select name="billing_type" class="form-select form-select-sm">
                                    <option value="all">All Types</option>
                                    @foreach($billingTypes as $type)
                                    <option value="{{ $type }}" {{ request('billing_type') == $type ? 'selected' : '' }}>
                                        {{ ucfirst($type) }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label fw-semibold">Payment Method</label>
                                <select name="payment_method" class="form-select form-select-sm">
                                    <option value="all">All Methods</option>
                                    @foreach($paymentMethods as $method)
                                    <option value="{{ $method }}" {{ request('payment_method') == $method ? 'selected' : '' }}>
                                        {{ ucfirst($method) }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label fw-semibold">From Date</label>
                                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
                            </div>

                            <div class="col-md-2">
                                <label class="form-label fw-semibold">To Date</label>
                                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
                            </div>

                            <div class="col-md-1">
                                <label class="form-label fw-semibold">&nbsp;</label>
                                <button type="submit" class="btn btn-primary btn-sm w-100">
                                    <i class="fas fa-search"></i> Filter
                                </button>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">Transaction ID</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" name="search" class="form-control" placeholder="Search by transaction ID..." value="{{ request('search') }}">
                                    <button type="submit" class="btn btn-outline-primary">
                                        <i class="fas fa-search"></i> Search
                                    </button>
                                    @if(request()->anyFilled(['status', 'billing_type', 'date_from', 'date_to', 'search', 'payment_method']))
                                    <a href="{{ route('merchant.manage-subscriptions.history') }}" class="btn btn-outline-danger">
                                        <i class="fas fa-times"></i> Clear
                                    </a>
                                    @endif
                                </div>
                            </div>


                        </div>
                    </form>
                </div>
            </div>

            <!-- History Table -->
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-clock me-2"></i>Past Subscriptions
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Plan Details</th>
                                    <th>Billing</th>
                                    <th>Subscription Period</th>
                                    <th>Amount</th>
                                    <th>Payment</th>
                                    <th>Transaction ID</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($histories as $history)
                                <tr>
                                    <td>{{ $history->id }}</td>
                                    <td>
                                        <strong>{{ $history->plan->name ?? 'N/A' }}</strong>
                                        @if($history->plan)
                                        <div class="small text-muted mt-1">
                                            <span class="badge bg-light text-dark">
                                                <i class="fas fa-box"></i> {{ $history->plan->products_limit == -1 ? 'Unlimited' : number_format($history->plan->products_limit) }} products
                                            </span>
                                        </div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $history->billing_type == 'monthly' ? 'bg-primary bg-opacity-10 text-primary' : 'bg-success bg-opacity-10 text-success' }}">
                                            {{ ucfirst($history->billing_type) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="small">
                                            <div><i class="fas fa-calendar-alt text-muted"></i> From: {{ $history->start_date ? $history->start_date->format('M d, Y') : 'N/A' }}</div>
                                            <div><i class="fas fa-calendar-check text-muted"></i> To: {{ $history->end_date ? $history->end_date->format('M d, Y') : 'N/A' }}</div>
                                        </div>
                                    </td>
                                    <td>
                                        <strong class="text-success">${{ number_format($history->amount_paid, 2) }}</strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary">
                                            <i class="fab fa-{{ $history->payment_method == 'paypal' ? 'paypal' : 'credit-card' }}"></i>
                                            {{ ucfirst($history->payment_method) }}
                                        </span>
                                    </td>
                                    <td>
                                        <code class="small">{{ $history->transaction_id }}</code>
                                    </td>
                                    <td>
                                        @if($history->status == 'expired')
                                        <span class="badge bg-dark bg-opacity-10 text-warning">
                                            <i class="fas fa-hourglass-end"></i> History
                                        </span>
                                        @elseif($history->status == 'cancelled')
                                        <span class="badge bg-danger bg-opacity-10 text-danger">
                                            <i class="fas fa-ban"></i> Cancelled
                                        </span>
                                        @else
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary">
                                            <i class="fas fa-history"></i> Changed
                                        </span>
                                        @endif
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $history->created_at->format('M d, Y') }}</small>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center py-5">
                                        <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                                        <h6 class="text-muted">No subscription history found</h6>
                                        <p class="text-muted small mb-3">You haven't changed or expired any subscriptions yet.</p>
                                        <a href="{{ route('merchant.manage-subscriptions.index') }}" class="btn btn-primary btn-sm">
                                            <i class="fas fa-shopping-cart"></i> Browse Plans
                                        </a>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($histories->hasPages())
                    <div class="card-footer bg-white">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <div class="small text-muted">
                                Showing {{ $histories->firstItem() }} to {{ $histories->lastItem() }} of {{ $histories->total() }} entries
                            </div>
                            <div>
                                {{ $histories->links() }}
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('css')
<style>
    .bg-opacity-10 {
        --bs-bg-opacity: 0.1;
    }

    .bg-primary.bg-opacity-10 {
        background-color: rgba(13, 110, 253, 0.1) !important;
    }

    .bg-success.bg-opacity-10 {
        background-color: rgba(25, 135, 84, 0.1) !important;
    }

    .bg-warning.bg-opacity-10 {
        background-color: rgba(255, 193, 7, 0.1) !important;
    }

    .bg-danger.bg-opacity-10 {
        background-color: rgba(220, 53, 69, 0.1) !important;
    }

    .bg-secondary.bg-opacity-10 {
        background-color: rgba(108, 117, 125, 0.1) !important;
    }

    .table> :not(caption)>*>* {
        vertical-align: middle;
        padding: 1rem 0.75rem;
    }

    .card {
        border: 1px solid rgba(0, 0, 0, 0.08);
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        transition: all 0.2s ease;
    }

    .card:hover {
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
    }

    .form-select-sm,
    .form-control-sm {
        font-size: 0.875rem;
    }

    .btn-sm {
        font-size: 0.875rem;
        padding: 0.35rem 0.75rem;
    }

    code {
        font-size: 0.75rem;
        background: #f8f9fa;
        padding: 0.2rem 0.4rem;
        border-radius: 4px;
    }

    @media (max-width: 768px) {
        .table-responsive {
            font-size: 0.85rem;
        }

        .table> :not(caption)>*>* {
            padding: 0.75rem 0.5rem;
        }

        .card-body .row .col-md-1,
        .card-body .row .col-md-8,
        .card-body .row .col-md-4 {
            margin-top: 0.5rem;
        }
    }

</style>
@endsection

@section('js')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-submit form when select filters change
        const autoSubmitInputs = document.querySelectorAll('#filterForm select');
        autoSubmitInputs.forEach(input => {
            input.addEventListener('change', function() {
                document.getElementById('filterForm').submit();
            });
        });

        // Date inputs manual submit
        const dateInputs = document.querySelectorAll('#filterForm input[type="date"]');
        dateInputs.forEach(input => {
            input.addEventListener('change', function() {
                document.getElementById('filterForm').submit();
            });
        });

        // Preserve scroll position on filter
        const filterForm = document.getElementById('filterForm');
        if (filterForm) {
            filterForm.addEventListener('submit', function() {
                sessionStorage.setItem('scrollPosition', window.scrollY);
            });

            // Restore scroll position
            const scrollPosition = sessionStorage.getItem('scrollPosition');
            if (scrollPosition) {
                window.scrollTo(0, parseInt(scrollPosition));
                sessionStorage.removeItem('scrollPosition');
            }
        }
    });

</script>
@endsection
