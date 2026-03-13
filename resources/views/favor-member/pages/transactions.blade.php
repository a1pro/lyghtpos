@extends('favor-member.layouts.main')

@section('content-header')
    Transaction History
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card card-info card-outline">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Current Balance</h5>
                            <h2 class="text-primary">{{ number_format($balance ?? 0, 2) }} {{ $currencySymbol }}</h2>
                        </div>
                        <div class="col-md-6 text-right">
                            <a href="{{ route('favor-member.wallet.payment-form') }}" class="btn btn-success">
                                <i class="fas fa-plus-circle mr-2"></i>
                                Add Funds
                            </a>
                            <a href="{{ route('favor-member.dashboard') }}" class="btn btn-secondary ml-2">
                                <i class="fas fa-arrow-left mr-2"></i>
                                Back
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            @if(isset($error) && $error)
                <div class="alert alert-warning alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">×</button>
                    <h5><i class="icon fas fa-exclamation-triangle"></i> Notice</h5>
                    {{ $error }}
                </div>
            @endif

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Transaction History</h3>
                    <div class="card-tools">
                        <span class="badge badge-info">{{ count($transactions) }} Transactions</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if(count($transactions) > 0)
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Description</th>
                                        <th>Reference</th>
                                        <th>Status</th>
                                        <th class="text-right">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($transactions as $transaction)
                                        <tr>
                                            <td>{{ $transaction['date'] ?? 'N/A' }}</td>
                                            <td>{{ $transaction['description'] ?? 'Transaction' }}</td>
                                            <td><small class="text-muted">{{ $transaction['reference'] ?? 'N/A' }}</small></td>
                                            <td>
                                                @if(($transaction['status'] ?? 'completed') == 'completed')
                                                    <span class="badge badge-success">Completed</span>
                                                @else
                                                    <span class="badge badge-warning">{{ $transaction['status'] }}</span>
                                                @endif
                                            </td>
                                            <td class="text-right">
                                                <span class="badge bg-{{ ($transaction['type'] ?? 'credit') == 'credit' ? 'success' : 'danger' }} p-2">
                                                    {{ ($transaction['type'] ?? 'credit') == 'credit' ? '+' : '-' }}{{ number_format($transaction['amount'] ?? 0, 2) }} {{ $currencySymbol }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center p-5">
                            <i class="fas fa-exchange-alt fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted">No Transactions Found</h5>
                            <p class="text-muted mb-4">Your transaction history will appear here</p>
                             
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection