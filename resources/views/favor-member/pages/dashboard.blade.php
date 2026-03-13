@extends('favor-member.layouts.main')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="mb-0">Favor Member Dashboard</h3>
                        <div>
                            <span class="badge bg-info text-white p-3" style="font-size: 1.2rem;">
                                <i class="fas fa-wallet me-2"></i>
                                Balance: <span id="wallet-balance">{{ number_format($balance ?? 0, 2) }}</span> TT
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if(isset($error) && $error)
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            {{ $error }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <!-- Add Funds Card -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <i class="fas fa-plus-circle me-2"></i>
                                        Add Funds to Wallet
                                    </h5>
                                    <p class="card-text">Add TradeTokens to your Cyclos wallet using credit card.</p>
                                    
                                    <form id="addFundsForm" action="{{ route('favor-member.wallet.add-funds') }}" method="POST">
                                        @csrf
                                        <div class="mb-3">
                                            <label for="amount" class="form-label">Amount (TT)</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-white">TT</span>
                                                <input type="number" 
                                                       class="form-control @error('amount') is-invalid @enderror" 
                                                       id="amount" 
                                                       name="amount" 
                                                       min="1" 
                                                       step="0.01" 
                                                       value="{{ old('amount') }}"
                                                       required>
                                                @error('amount')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        
                                        <!-- Tax Preview -->
                                        <div class="small mb-3" id="taxPreview" style="display: none;">
                                            <hr class="bg-white">
                                            <div class="row">
                                                <div class="col-6">Tax (5%):</div>
                                                <div class="col-6 text-end" id="taxAmount">0.00 TT</div>
                                                <div class="col-6 fw-bold">Total:</div>
                                                <div class="col-6 text-end fw-bold" id="totalAmount">0.00 TT</div>
                                            </div>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-light w-100">
                                            <i class="fas fa-arrow-right me-2"></i>
                                            Proceed to Payment
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Transactions Card -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-history me-2"></i>
                                        Recent Transactions
                                    </h5>
                                </div>
                                <div class="card-body">
                                    @if(isset($recentTransactions) && count($recentTransactions) > 0)
                                        <div class="list-group">
                                            @foreach($recentTransactions as $transaction)
                                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <small class="text-muted">{{ $transaction['date'] ?? 'N/A' }}</small>
                                                        <p class="mb-0">{{ $transaction['description'] ?? 'Transaction' }}</p>
                                                        @if(isset($transaction['reference']))
                                                            <small class="text-muted">Ref: {{ $transaction['reference'] }}</small>
                                                        @endif
                                                    </div>
                                                    <span class="badge bg-{{ ($transaction['type'] ?? 'credit') == 'credit' ? 'success' : 'danger' }} rounded-pill">
                                                        {{ ($transaction['type'] ?? 'credit') == 'credit' ? '+' : '-' }}{{ number_format($transaction['amount'] ?? 0, 2) }} TT
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>
                                        <div class="mt-3 text-center">
                                            <a href="{{ route('favor-member.wallet.transactions') }}" class="btn btn-sm btn-outline-primary">
                                                View All Transactions
                                            </a>
                                        </div>
                                    @else
                                        <p class="text-muted text-center mb-0">No recent transactions</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Tax calculation preview
    document.getElementById('amount')?.addEventListener('input', function(e) {
        const amount = parseFloat(e.target.value) || 0;
        const taxRate = 5;
        const taxAmount = amount * (taxRate / 100);
        const totalAmount = amount + taxAmount;
        
        if (amount > 0) {
            document.getElementById('taxPreview').style.display = 'block';
            document.getElementById('taxAmount').textContent = taxAmount.toFixed(2) + ' TT';
            document.getElementById('totalAmount').textContent = totalAmount.toFixed(2) + ' TT';
        } else {
            document.getElementById('taxPreview').style.display = 'none';
        }
    });

    // Function to refresh balance
    function refreshBalance() {
        fetch('{{ route("favor-member.wallet.balance") }}')
            .then(response => response.json())
            .then(data => {
                if (data.balance !== undefined) {
                    document.getElementById('wallet-balance').textContent = parseFloat(data.balance).toFixed(2);
                }
            })
            .catch(error => console.error('Error refreshing balance:', error));
    }

    // Refresh balance every 30 seconds
    setInterval(refreshBalance, 30000);

    // Initial balance fetch on page load
    document.addEventListener('DOMContentLoaded', function() {
        refreshBalance();
    });
</script>
@endpush
@endsection