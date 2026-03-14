@extends('favor-member.layouts.main')

@section('content-header')
    Dashboard
@endsection

@push('styles')
<style>
    .payment-method-input {
        display: none;
    }

    .payment-method-label {
        cursor: pointer;
        display: block;
    }

    .payment-method-card {
        height: 100%;
    }

    .payment-option {
        border-radius: 12px;
        transition: all 0.2s ease-in-out;
        border: 2px solid #dee2e6 !important;
        min-height: 240px;
        height: 100%;
    }

    .payment-option:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08);
    }

    .payment-method-input:checked + .payment-method-label .payment-option {
        border-color: #28a745 !important;
        box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.15);
        background-color: #f8fff9;
    }

    .payment-logo-wrap {
        height: 90px;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 16px;
    }

    .payment-logo {
        max-height: 70px;
        max-width: 180px;
        width: auto;
        height: auto;
        object-fit: contain;
        display: block;
    }

    .wallet-balance-value {
        word-break: break-word;
    }

    @media (max-width: 767.98px) {
        .payment-option {
            min-height: 200px;
        }

        .payment-logo-wrap {
            height: 75px;
        }

        .payment-logo {
            max-height: 55px;
            max-width: 150px;
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            <h5><i class="icon fas fa-check"></i> Success!</h5>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            <h5><i class="icon fas fa-ban"></i> Error!</h5>
            {{ session('error') }}
        </div>
    @endif

    @if(isset($error) && $error)
        <div class="alert alert-warning alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            <h5><i class="icon fas fa-exclamation-triangle"></i> Notice</h5>
            {{ $error }}
        </div>
    @endif

    <div class="row">
        <div class="col-md-12">
            <div class="card card-primary card-outline">
                <div class="card-body">
                    <div class="text-center">
                        <h3 class="text-uppercase text-muted mb-3">Wallet Balance</h3>
                        <h1 class="display-1 font-weight-bold wallet-balance-value" id="wallet-balance">
                            {{ number_format($balance, 2) }}
                        </h1>
                        <p class="text-muted">{{ $currencySymbol ?? 'TT' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card card-success">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-plus-circle mr-2"></i>
                        Add Funds to Wallet
                    </h3>
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        Add {{ $currencySymbol ?? 'TT' }} to your Cyclos wallet using your preferred payment method.
                    </p>

                    <form id="addFundsForm" action="{{ route('favor-member.wallet.add-funds') }}" method="POST">
                        @csrf

                        <div class="form-group">
                            <label for="amount">Amount ({{ $currencySymbol ?? 'TT' }})</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">{{ $currencySymbol ?? 'TT' }}</span>
                                </div>
                                <input type="number"
                                       class="form-control @error('amount') is-invalid @enderror form-control-lg"
                                       id="amount"
                                       name="amount"
                                       min="1"
                                       step="0.01"
                                       value="{{ old('amount') }}"
                                       placeholder="Enter amount"
                                       required>
                            </div>
                            @error('amount')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group mt-4">
                            <label class="d-block mb-3">Select Payment Method</label>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="payment-method-card">
                                        <input class="payment-method-input @error('payment_method') is-invalid @enderror"
                                               type="radio"
                                               id="payment_authorize"
                                               name="payment_method"
                                               value="authorize"
                                               {{ old('payment_method', 'authorize') === 'authorize' ? 'checked' : '' }}>

                                        <label for="payment_authorize" class="payment-method-label w-100 mb-0">
                                            <div class="card payment-option">
                                                <div class="card-body text-center d-flex flex-column justify-content-center align-items-center">
                                                    <div class="payment-logo-wrap">
                                                        <img
                                                            src="{{ asset('images/authorize.jpeg') }}"
                                                            alt="Authorize.net"
                                                            class="payment-logo">
                                                    </div>
                                                    <small class="text-muted d-block px-2">
                                                        Pay using credit / debit card
                                                    </small>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <div class="payment-method-card">
                                        <input class="payment-method-input @error('payment_method') is-invalid @enderror"
                                               type="radio"
                                               id="payment_paypal"
                                               name="payment_method"
                                               value="paypal"
                                               {{ old('payment_method') === 'paypal' ? 'checked' : '' }}>

                                        <label for="payment_paypal" class="payment-method-label w-100 mb-0">
                                            <div class="card payment-option">
                                                <div class="card-body text-center d-flex flex-column justify-content-center align-items-center">
                                                    <div class="payment-logo-wrap">
                                                        <img
                                                            src="{{ asset('images/paypal.jpeg') }}"
                                                            alt="PayPal"
                                                            class="payment-logo">
                                                    </div>
                                                    <small class="text-muted d-block px-2">
                                                        Pay using PayPal
                                                    </small>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            @error('payment_method')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="alert alert-info mt-3" id="taxPreview" style="display: none;">
                            <h6 class="text-uppercase mb-3">Payment Summary</h6>
                            <div class="row">
                                <div class="col-6">Net Amount:</div>
                                <div class="col-6 text-right font-weight-bold" id="netAmount">0.00 {{ $currencySymbol ?? 'TT' }}</div>

                                <div class="col-6">Tax (5%):</div>
                                <div class="col-6 text-right text-danger" id="taxAmount">0.00 {{ $currencySymbol ?? 'TT' }}</div>

                                <div class="col-6">Processing Fee (2.9% + $0.30):</div>
                                <div class="col-6 text-right text-danger" id="processingAmount">0.00 {{ $currencySymbol ?? 'TT' }}</div>

                                <div class="col-12"><hr class="my-2"></div>

                                <div class="col-6"><strong>Total to be charged:</strong></div>
                                <div class="col-6 text-right">
                                    <strong class="text-primary" id="totalAmount">0.00 {{ $currencySymbol ?? 'TT' }}</strong>
                                </div>

                                <div class="col-12 mt-2">
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        <span id="walletCredit">0.00</span> {{ $currencySymbol ?? 'TT' }} will be added to your wallet
                                    </small>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success btn-lg btn-block mt-3" id="submitBtn">
                            <i class="fas fa-arrow-right mr-2"></i>
                            Continue
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-history mr-2"></i>
                        Recent Transactions
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('favor-member.wallet.transactions') }}" class="btn btn-sm btn-info">
                            View All <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if(isset($recentTransactions) && count($recentTransactions) > 0)
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Description</th>
                                        <th class="text-right">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentTransactions as $transaction)
                                        <tr>
                                            <td>{{ $transaction['date'] ?? 'N/A' }}</td>
                                            <td>{{ \Illuminate\Support\Str::limit($transaction['description'] ?? 'Transaction', 30) }}</td>
                                            <td class="text-right">
                                                <span class="badge bg-{{ ($transaction['type'] ?? 'credit') == 'credit' ? 'success' : 'danger' }} p-2">
                                                    {{ ($transaction['type'] ?? 'credit') == 'credit' ? '+' : '-' }}{{ number_format($transaction['amount'] ?? 0, 2) }} {{ $currencySymbol ?? 'TT' }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center p-5">
                            <i class="fas fa-exchange-alt fa-3x text-muted mb-3"></i>
                            <p class="text-muted mb-1">No recent transactions</p>
                            <p class="text-muted small">Your transactions will appear here</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Quick Actions</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 col-6">
                            <a href="{{ route('favor-member.wallet.transactions') }}" class="btn btn-app btn-block">
                                <i class="fas fa-history"></i> History
                            </a>
                        </div>

                        <div class="col-md-3 col-6">
                            <a href="#" class="btn btn-app btn-block" onclick="refreshBalance(); return false;">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </a>
                        </div>

                        <div class="col-md-3 col-6">
                            <a href="#" class="btn btn-app btn-block" data-toggle="modal" data-target="#helpModal">
                                <i class="fas fa-question-circle"></i> Help
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="helpModal" tabindex="-1" role="dialog" aria-labelledby="helpModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="helpModalLabel">Help & Information</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <h6>Adding Funds</h6>
                <p>To add funds to your wallet:</p>
                <ol>
                    <li>Enter the amount you want to add</li>
                    <li>Select your payment method</li>
                    <li>Review the fee breakdown (5% tax + 2.9% processing fee + $0.30)</li>
                    <li>Proceed with payment</li>
                    <li>Funds will be added to your wallet after successful payment</li>
                </ol>

                <h6 class="mt-3">Currency</h6>
                <p>Your wallet uses <strong>{{ $currencySymbol ?? 'TT' }} ({{ $currencyName ?? 'TradeTokens' }})</strong></p>

                <h6 class="mt-3">Need Help?</h6>
                <p>Contact support at <a href="mailto:support@example.com">support@example.com</a></p>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.getElementById('amount')?.addEventListener('input', function (e) {
        const amount = parseFloat(e.target.value) || 0;
        const taxRate = 5;
        const processingRate = 2.9;
        const processingFixed = 0.30;

        const taxAmount = amount * (taxRate / 100);
        const processingPercentage = amount * (processingRate / 100);
        const processingTotal = processingPercentage + processingFixed;
        const totalAmount = amount + taxAmount + processingTotal;

        if (amount > 0) {
            document.getElementById('taxPreview').style.display = 'block';
            document.getElementById('netAmount').textContent = amount.toFixed(2) + ' {{ $currencySymbol ?? 'TT' }}';
            document.getElementById('taxAmount').textContent = taxAmount.toFixed(2) + ' {{ $currencySymbol ?? 'TT' }}';
            document.getElementById('processingAmount').textContent = processingTotal.toFixed(2) + ' {{ $currencySymbol ?? 'TT' }}';
            document.getElementById('totalAmount').textContent = totalAmount.toFixed(2) + ' {{ $currencySymbol ?? 'TT' }}';
            document.getElementById('walletCredit').textContent = amount.toFixed(2);
        } else {
            document.getElementById('taxPreview').style.display = 'none';
        }
    });

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

    setInterval(refreshBalance, 30000);

    document.addEventListener('DOMContentLoaded', function () {
        refreshBalance();
    });

    document.getElementById('addFundsForm')?.addEventListener('submit', function () {
        const btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
    });
</script>
@endpush