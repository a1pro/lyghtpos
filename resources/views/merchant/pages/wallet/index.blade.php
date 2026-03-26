{{-- resources/views/merchant/pages/wallet/index.blade.php --}}
@extends('merchant.layouts.main')

@section('content')
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Wallet & Payouts</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('merchant.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Wallet</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <!-- Stats Cards -->
            <div class="row">
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3>{{ $walletSummary['currency'] }} {{ number_format($walletSummary['total_earnings'], 2) }}</h3>
                            <p>Total Earnings</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3>{{ $walletSummary['currency'] }} {{ number_format($walletSummary['available_balance'], 2) }}</h3>
                            <p>Available Balance</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <a href="{{ route('merchant.wallet.payout') }}" class="small-box-footer">
                            Request Payout <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3>{{ $walletSummary['currency'] }} {{ number_format($walletSummary['pending_balance'], 2) }}</h3>
                            <p>Pending Balance</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-secondary">
                        <div class="inner">
                            <h3>{{ $walletSummary['currency'] }} {{ number_format($walletSummary['total_payouts'], 2) }}</h3>
                            <p>Total Payouts</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Transactions -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Recent Transactions</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Transaction ID</th>
                                            <th>Amount</th>
                                            <th>Payment Method</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($recentTransactions as $transaction)
                                        <tr>
                                            <td>
                                                <span class="text-muted">#{{ substr($transaction->transaction_id, -8) }}</span>
                                            </td>
                                            <td>
                                                <strong>{{ $walletSummary['currency'] }} {{ number_format($transaction->merchant_share, 2) }}</strong>
                                                <small class="text-muted d-block">(Total: {{ $walletSummary['currency'] }} {{ number_format($transaction->total_amount, 2) }})</small>
                                            </td>
                                            <td>
                                                <span class="badge badge-info">{{ ucfirst($transaction->payment_method) }}</span>
                                            </td>
                                            <td>{{ $transaction->paid_at->format('M d, Y H:i') }}</td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">No transactions found</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Payouts -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Recent Payouts</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Reference</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                             
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($recentPayouts as $payout)
                                        <tr>
                                            <td>
                                                <span class="text-muted">{{ $payout->payout_reference }}</span>
                                            </td>
                                            <td>
                                                <strong>{{ $walletSummary['currency'] }} {{ number_format($payout->amount, 2) }}</strong>
                                                @if($payout->fee > 0)
                                                <small class="text-muted d-block">Fee: {{ $walletSummary['currency'] }} {{ number_format($payout->fee, 2) }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge {{ $payout->status_badge_class }}">
                                                    {{ $payout->status_label }}
                                                </span>
                                            </td>
                                            <td>{{ $payout->created_at->format('M d, Y') }}</td>
                                          
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">No payouts found</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payout Instructions -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Payout Information</h3>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <strong>Important Information:</strong>
                                <ul class="mb-0 mt-2">
                                    <li>Minimum payout amount is $10.00</li>
                                    <li>Maximum payout amount is $100,000.00 per transaction</li>
                                    <li>Payouts are processed within 3-5 business days</li>
                                    <li>A processing fee of 2% (minimum $1) applies to each payout</li>
                                    <li>Bank transfers may take additional 2-3 days depending on your bank</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<style>
    .small-box {
        border-radius: 10px;
        transition: all 0.3s ease;
    }
    
    .small-box:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .table td, .table th {
        vertical-align: middle;
    }
    
    .badge {
        padding: 5px 10px;
        font-weight: 500;
    }
</style>
@endsection