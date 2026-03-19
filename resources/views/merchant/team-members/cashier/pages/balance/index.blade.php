{{-- resources/views/merchant/team-members/cashier/pages/balance/index.blade.php --}}
@extends('merchant.team-members.cashier.layouts.main')

@section('content')
<div class="container-fluid">
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="mb-0">Merchant Balance</h4>
                <div class="page-title-right">
                    <button class="btn btn-primary" onclick="refreshBalance()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- end page title -->

    @if(isset($error))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ $error }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Cashier Info -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar-lg me-3">
                            <span class="avatar-title bg-info rounded-circle">
                                {{ strtoupper(substr($cashierInfo['name'], 0, 1)) }}
                            </span>
                        </div>
                        <div>
                            <h5 class="mb-1">{{ $cashierInfo['name'] }}</h5>
                            <p class="text-muted mb-0">{{ $cashierInfo['email'] }} | {{ $cashierInfo['role'] }}</p>
                            <p class="text-muted mb-0">Viewing balance for: <strong>{{ $merchantName }}</strong></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Balance Cards -->
    <div class="row">
        <div class="col-xl-4 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar-lg me-3">
                            <span class="avatar-title bg-success rounded-circle">
                                <i class="fas fa-wallet fa-2x"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="text-muted mb-1">Current Balance</h5>
                            <h3 class="mb-0 balance-amount">{{ $formattedBalance ?? $currencySymbol . ' 0.00' }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar-lg me-3">
                            <span class="avatar-title bg-info rounded-circle">
                                <i class="fas fa-exchange-alt fa-2x"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="text-muted mb-1">Total Transactions</h5>
                            <h3 class="mb-0">{{ count($transactions) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar-lg me-3">
                            <span class="avatar-title bg-warning rounded-circle">
                                <i class="fas fa-building fa-2x"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="text-muted mb-1">Currency</h5>
                            <h3 class="mb-0">{{ $currencySymbol }} ({{ $accountDetails['currency_code'] ?? 'TT' }})</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Transactions</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Description</th>
                                    <th>Reference</th>
                                    <th>Amount</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentTransactions as $transaction)
                                    <tr>
                                        <td>{{ $transaction['date'] }}</td>
                                        <td>{{ $transaction['description'] }}</td>
                                        <td>{{ $transaction['reference'] }}</td>
                                        <td>
                                            <span class="{{ $transaction['type'] == 'credit' ? 'text-success' : 'text-danger' }}">
                                                {{ $transaction['type'] == 'credit' ? '+' : '-' }}
                                                {{ $currencySymbol }} {{ number_format($transaction['amount'], 2) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($transaction['type'] == 'credit')
                                                <span class="badge bg-success">Credit</span>
                                            @else
                                                <span class="badge bg-danger">Debit</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($transaction['status'] == 'completed')
                                                <span class="badge bg-success">Completed</span>
                                            @else
                                                <span class="badge bg-warning">{{ $transaction['status'] }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <p class="mb-0">No transactions found</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if(count($transactions) > 5)
                        <div class="text-center mt-3">
                            <button class="btn btn-outline-primary" onclick="loadAllTransactions()">
                                View All Transactions
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Transactions Modal -->
<div class="modal fade" id="transactionsModal" tabindex="-1" aria-labelledby="transactionsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="transactionsModalLabel">All Transactions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="transactionsTable">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Reference</th>
                                <th>Amount</th>
                                <th>Type</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="transactionsBody">
                            <!-- Transactions will be loaded here via AJAX -->
                        </tbody>
                    </table>
                </div>
                <div class="text-center mt-3" id="transactionsLoading" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function refreshBalance() {
        fetch('{{ route("merchant.cashier.balance.get") }}')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.querySelector('.balance-amount').textContent = data.formatted_balance;
                    
                    // Show success toast
                    toastr.success('Balance updated successfully');
                }
            })
            .catch(error => {
                console.error('Error refreshing balance:', error);
                toastr.error('Failed to refresh balance');
            });
    }

    function loadAllTransactions() {
        const modal = new bootstrap.Modal(document.getElementById('transactionsModal'));
        const tbody = document.getElementById('transactionsBody');
        const loading = document.getElementById('transactionsLoading');
        
        modal.show();
        loading.style.display = 'block';
        tbody.innerHTML = '';
        
        fetch('{{ route("merchant.cashier.balance.transactions") }}')
            .then(response => response.json())
            .then(data => {
                loading.style.display = 'none';
                
                if (data.success && data.transactions.length > 0) {
                    let html = '';
                    const currencySymbol = '{{ $currencySymbol }}';
                    
                    data.transactions.forEach(transaction => {
                        const amountClass = transaction.type === 'credit' ? 'text-success' : 'text-danger';
                        const amountSign = transaction.type === 'credit' ? '+' : '-';
                        const badgeClass = transaction.type === 'credit' ? 'bg-success' : 'bg-danger';
                        const statusClass = transaction.status === 'completed' ? 'bg-success' : 'bg-warning';
                        
                        html += `<tr>
                            <td>${transaction.date}</td>
                            <td>${transaction.description}</td>
                            <td>${transaction.reference}</td>
                            <td class="${amountClass}">${amountSign} ${currencySymbol} ${transaction.amount.toFixed(2)}</td>
                            <td><span class="badge ${badgeClass}">${transaction.type.charAt(0).toUpperCase() + transaction.type.slice(1)}</span></td>
                            <td><span class="badge ${statusClass}">${transaction.status.charAt(0).toUpperCase() + transaction.status.slice(1)}</span></td>
                        </tr>`;
                    });
                    
                    tbody.innerHTML = html;
                } else {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4">No transactions found</td></tr>';
                }
            })
            .catch(error => {
                loading.style.display = 'none';
                console.error('Error loading transactions:', error);
                tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-danger">Failed to load transactions</td></tr>';
            });
    }

    // Auto-refresh balance every 60 seconds
    setInterval(refreshBalance, 60000);
</script>
@endpush