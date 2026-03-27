{{-- resources/views/admin/pages/payouts/index.blade.php --}}
@extends('layouts.admin')

@section('content')
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Payout Management</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Payouts</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <!-- Statistics Cards -->
            <div class="row">
                <div class="col-lg-2 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3>{{ $statistics['total_payouts'] }}</h3>
                            <p>Total Payouts</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3>{{ $statistics['pending_payouts'] }}</h3>
                            <p>Pending</p>
                            <small>${{ number_format($statistics['total_amount_pending'], 2) }}</small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3>{{ $statistics['processing_payouts'] }}</h3>
                            <p>Processing</p>
                            <small>${{ number_format($statistics['total_amount_processing'], 2) }}</small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3>{{ $statistics['completed_payouts'] }}</h3>
                            <p>Completed</p>
                            <small>${{ number_format($statistics['total_amount_completed'], 2) }}</small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-6">
                    <div class="small-box bg-danger">
                        <div class="inner">
                            <h3>{{ $statistics['failed_payouts'] }}</h3>
                            <p>Failed</p>
                            <small>${{ number_format($statistics['total_amount_failed'], 2) }}</small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-6">
                    <div class="small-box bg-secondary">
                        <div class="inner">
                            <h3>${{ number_format($statistics['total_fees_collected'], 2) }}</h3>
                            <p>Fees Collected</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Filters</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.payouts.index') }}" id="filterForm">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Status</label>
                                    <select name="status" class="form-control">
                                        <option value="">All</option>
                                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                        <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>Processing</option>
                                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                                        <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Payment Method</label>
                                    <select name="payment_method" class="form-control">
                                        <option value="">All</option>
                                        <option value="bank_transfer" {{ request('payment_method') == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                        <option value="paypal" {{ request('payment_method') == 'paypal' ? 'selected' : '' }}>PayPal</option>
                                        <option value="stripe" {{ request('payment_method') == 'stripe' ? 'selected' : '' }}>Stripe</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Date From</label>
                                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Date To</label>
                                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Search</label>
                                    <input type="text" name="search" class="form-control" placeholder="Reference, Name, Email" value="{{ request('search') }}">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary">Apply Filters</button>
                                <a href="{{ route('admin.payouts.index') }}" class="btn btn-secondary">Reset</a>
                                <a href="{{ route('admin.payouts.export') }}?{{ http_build_query(request()->except('page')) }}" class="btn btn-success float-right">
                                    <i class="fas fa-download"></i> Export CSV
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Payouts Table -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Payout Requests</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-sm btn-primary" id="bulkUpdateBtn" style="display: none;">
                            <i class="fas fa-edit"></i> Bulk Update
                        </button>
                    </div>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th width="30">
                                    <input type="checkbox" id="selectAll">
                                </th>
                                <th>ID</th>
                                <th>Reference</th>
                                <th>Merchant</th>
                                <th>Amount</th>
                                <th>Fee</th>
                                <th>Net Amount</th>
                                <th>Payment Method</th>
                                <th>Status</th>
                                <th>Requested Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($payouts as $payout)
                            <tr>
                                <td>
                                    <input type="checkbox" class="payout-checkbox" value="{{ $payout->id }}" data-status="{{ $payout->status }}">
                                </td>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <a href="{{ route('admin.payouts.show', $payout->id) }}">
                                        {{ $payout->payout_reference }}
                                    </a>
                                </td>
                                <td>
                                    <strong>{{ $payout->merchant->first_name ?? 'N/A' }} {{ $payout->merchant->last_name ?? '' }}</strong><br>
                                    <small class="text-muted">{{ $payout->merchant->email ?? 'N/A' }}</small>
                                </td>
                                <td class="text-right">
                                    <strong>${{ number_format($payout->amount, 2) }}</strong>
                                </td>
                                <td class="text-right text-muted">
                                    ${{ number_format($payout->fee, 2) }}
                                </td>
                                <td class="text-right text-success">
                                    ${{ number_format($payout->net_amount, 2) }}
                                </td>
                                <td>
                                    @if($payout->payment_method == 'bank_transfer')
                                        <i class="fas fa-university"></i> Bank Transfer
                                    @elseif($payout->payment_method == 'paypal')
                                        <i class="fab fa-paypal"></i> PayPal
                                    @else
                                        <i class="fab fa-stripe"></i> Stripe
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $payout->status_badge_class }}">
                                        {{ $payout->status_label }}
                                    </span>
                                </td>
                                <td>{{ $payout->requested_at ? $payout->requested_at->format('M d, Y H:i') : 'N/A' }}</td>
                                <td>
                                    <a href="{{ route('admin.payouts.show', $payout->id) }}" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                     
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="11" class="text-center text-muted">No payouts found</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer clearfix">
                    {{ $payouts->appends(request()->query())->links() }}
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Update Status Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="updateStatusForm">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Update Payout Status</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Payout Reference</label>
                        <input type="text" id="modalReference" class="form-control" readonly>
                    </div>
                    <div class="form-group">
                        <label>Amount</label>
                        <input type="text" id="modalAmount" class="form-control" readonly>
                    </div>
                    <div class="form-group">
                        <label>Status *</label>
                        <select name="status" id="modalStatus" class="form-control" required>
                            <option value="processing">Processing</option>
                            <option value="completed">Completed</option>
                            <option value="failed">Failed</option>
                        </select>
                    </div>
                    <div class="form-group" id="transactionRefGroup" style="display: none;">
                        <label>Transaction Reference *</label>
                        <input type="text" name="transaction_reference" class="form-control" placeholder="Enter transaction reference">
                    </div>
                    <div class="form-group" id="failureReasonGroup" style="display: none;">
                        <label>Failure Reason *</label>
                        <textarea name="failure_reason" class="form-control" rows="3" placeholder="Enter reason for failure"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Processing Notes</label>
                        <textarea name="processed_notes" class="form-control" rows="2" placeholder="Optional notes about this payout"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
let currentPayoutId = null;

// Show/hide status-specific fields
document.getElementById('modalStatus').addEventListener('change', function() {
    const transactionRefGroup = document.getElementById('transactionRefGroup');
    const failureReasonGroup = document.getElementById('failureReasonGroup');
    
    if (this.value === 'completed') {
        transactionRefGroup.style.display = 'block';
        failureReasonGroup.style.display = 'none';
        document.querySelector('input[name="transaction_reference"]').required = true;
        document.querySelector('textarea[name="failure_reason"]').required = false;
    } else if (this.value === 'failed') {
        transactionRefGroup.style.display = 'none';
        failureReasonGroup.style.display = 'block';
        document.querySelector('input[name="transaction_reference"]').required = false;
        document.querySelector('textarea[name="failure_reason"]').required = true;
    } else {
        transactionRefGroup.style.display = 'none';
        failureReasonGroup.style.display = 'none';
        document.querySelector('input[name="transaction_reference"]').required = false;
        document.querySelector('textarea[name="failure_reason"]').required = false;
    }
});

// Update status button click
document.querySelectorAll('.update-status-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        currentPayoutId = this.dataset.id;
        document.getElementById('modalReference').value = this.dataset.reference;
        document.getElementById('modalAmount').value = '$' + parseFloat(this.dataset.amount).toFixed(2);
        document.getElementById('modalStatus').value = 'processing';
        document.getElementById('updateStatusForm').reset();
        document.getElementById('modalStatus').dispatchEvent(new Event('change'));
        
        $('#updateStatusModal').modal('show');
    });
});

// Handle form submission
document.getElementById('updateStatusForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const status = document.getElementById('modalStatus').value;
    
    Swal.fire({
        title: 'Confirm Status Update',
        text: `Are you sure you want to mark this payout as ${status}?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Update',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalHtml = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
            submitBtn.disabled = true;
            
            fetch(`/admin/payouts/${currentPayoutId}/status`, {
                method: 'PUT',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(Object.fromEntries(formData))
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: data.message
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to update payout status'
                });
            })
            .finally(() => {
                submitBtn.innerHTML = originalHtml;
                submitBtn.disabled = false;
                $('#updateStatusModal').modal('hide');
            });
        }
    });
});

// Select all checkboxes
document.getElementById('selectAll').addEventListener('change', function() {
    document.querySelectorAll('.payout-checkbox').forEach(checkbox => {
        checkbox.checked = this.checked;
    });
    updateBulkButton();
});

document.querySelectorAll('.payout-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', updateBulkButton);
});

function updateBulkButton() {
    const selected = document.querySelectorAll('.payout-checkbox:checked');
    document.getElementById('bulkUpdateBtn').style.display = selected.length > 0 ? 'inline-block' : 'none';
}
</script>

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
    body:not(.sidebar-mini-md):not(.sidebar-mini-xs):not(.layout-top-nav) .content-wrapper, body:not(.sidebar-mini-md):not(.sidebar-mini-xs):not(.layout-top-nav) .main-footer, body:not(.sidebar-mini-md):not(.sidebar-mini-xs):not(.layout-top-nav) .main-header {
        
        margin-left: 125px !important;
    }
</style>
@endsection