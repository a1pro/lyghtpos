{{-- resources/views/admin/pages/payouts/show.blade.php --}}
@extends('layouts.admin')

@section('content')
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Payout Details</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.payouts.index') }}">Payouts</a></li>
                        <li class="breadcrumb-item active">Payout #{{ $payout->id }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-8">
                    <!-- Payout Details Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Payout Information</h3>
                            <div class="card-tools">
                                <span class="badge {{ $payout->status_badge_class }} p-2">
                                    {{ $payout->status_label }}
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <dl class="row">
                                        <dt class="col-sm-5">Payout Reference:</dt>
                                        <dd class="col-sm-7"><strong>{{ $payout->payout_reference }}</strong></dd>

                                        <dt class="col-sm-5">Requested Amount:</dt>
                                        <dd class="col-sm-7">${{ number_format($payout->amount, 2) }}</dd>

 
                                        <dt class="col-sm-5">Payment Method:</dt>
                                        <dd class="col-sm-7">
                                            @if($payout->payment_method == 'bank_transfer')
                                                <i class="fas fa-university"></i> Bank Transfer
                                            @elseif($payout->payment_method == 'paypal')
                                                <i class="fab fa-paypal"></i> PayPal
                                            @else
                                                <i class="fab fa-stripe"></i> Stripe
                                            @endif
                                        </dd>

                                        <dt class="col-sm-5">Requested Date:</dt>
                                        <dd class="col-sm-7">{{ $payout->requested_at ? $payout->requested_at->format('F d, Y H:i:s') : 'N/A' }}</dd>
                                    </dl>
                                </div>
                                <div class="col-md-6">
                                    <dl class="row">
                                        <dt class="col-sm-5">Status:</dt>
                                        <dd class="col-sm-7">
                                            <span class="badge {{ $payout->status_badge_class }}">
                                                {{ $payout->status_label }}
                                            </span>
                                        </dd>

                                        @if($payout->processed_at)
                                        <dt class="col-sm-5">Processed Date:</dt>
                                        <dd class="col-sm-7">{{ $payout->processed_at->format('F d, Y H:i:s') }}</dd>
                                        @endif

                                        @if($payout->completed_at)
                                        <dt class="col-sm-5">Completed Date:</dt>
                                        <dd class="col-sm-7">{{ $payout->completed_at->format('F d, Y H:i:s') }}</dd>
                                        @endif

                                        @if($payout->processed_by)
                                        <dt class="col-sm-5">Processed By:</dt>
                                        <dd class="col-sm-7">{{ $payout->processor->name ?? $payout->processor->first_name ?? 'N/A' }}</dd>
                                        @endif
                                    </dl>
                                </div>
                            </div>

                            @if($payout->notes)
                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="alert alert-info">
                                        <strong>Notes from Merchant:</strong><br>
                                        {{ $payout->notes }}
                                    </div>
                                </div>
                            </div>
                            @endif

                            @if($payout->failure_reason)
                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="alert alert-danger">
                                        <strong>Failure Reason:</strong><br>
                                        {{ $payout->failure_reason }}
                                    </div>
                                </div>
                            </div>
                            @endif

                            @if($payout->processed_notes)
                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="alert alert-secondary">
                                        <strong>Processing Notes:</strong><br>
                                        {{ $payout->processed_notes }}
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                        <div class="card-footer">
                            @if(in_array($payout->status, ['pending', 'processing']))
                            <button type="button" class="btn btn-primary update-status-btn" 
                                    data-id="{{ $payout->id }}"
                                    data-reference="{{ $payout->payout_reference }}"
                                    data-amount="{{ $payout->amount }}">
                                <i class="fas fa-edit"></i> Update Status
                            </button>
                            @endif
                            <a href="{{ route('admin.payouts.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to List
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <!-- Merchant Information Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Merchant Information</h3>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-3">
                                <div class="avatar-circle mb-2">
                                    <span class="initials">
                                        {{ strtoupper(substr($payout->merchant->first_name ?? 'U', 0, 1)) }}
                                        {{ strtoupper(substr($payout->merchant->last_name ?? 'R', 0, 1)) }}
                                    </span>
                                </div>
                                <h5>{{ $payout->merchant->first_name ?? 'N/A' }} {{ $payout->merchant->last_name ?? '' }}</h5>
                                <p class="text-muted mb-1">{{ $payout->merchant->email ?? 'N/A' }}</p>
                                @if($payout->merchant->phone)
                                <p class="text-muted">{{ $payout->merchant->phone }}</p>
                                @endif
                            </div>
                            <hr>
                            <div class="info-box bg-light">
                                <div class="info-box-content">
                                    <span class="info-box-text">Current Wallet Balance</span>
                                    <span class="info-box-number text-success">
                                        ${{ number_format($payout->merchant->wallet_amount ?? 0, 2) }}
                                    </span>
                                    <small class="text-muted">(Read Only - Cannot be edited)</small>
                                </div>
                            </div>
                            <hr>
                            <dl class="row mb-0">
                                <dt class="col-sm-6">Total Payouts:</dt>
                                <dd class="col-sm-6">
                                    @php
                                        $totalPayouts = \App\Models\Payout::where('merchant_id', $payout->merchant_id)->count();
                                    @endphp
                                    {{ $totalPayouts }}
                                </dd>
                                
                                <dt class="col-sm-6">Total Amount:</dt>
                                <dd class="col-sm-6">
                                    @php
                                        $totalAmount = \App\Models\Payout::where('merchant_id', $payout->merchant_id)
                                            ->where('status', 'completed')
                                            ->sum('amount');
                                    @endphp
                                    ${{ number_format($totalAmount, 2) }}
                                </dd>
                                
                                <dt class="col-sm-6">Merchant Since:</dt>
                                <dd class="col-sm-6">{{ $payout->merchant->created_at ? $payout->merchant->created_at->format('M d, Y') : 'N/A' }}</dd>
                            </dl>
                        </div>
                    </div>

                    <!-- Payment Details Card -->
                    @if($payout->payment_details)
                    <div class="card mt-3">
                        <div class="card-header">
                            <h3 class="card-title">Payment Details</h3>
                        </div>
                        <div class="card-body">
                            @if($payout->payment_method == 'bank_transfer')
                                <dl class="row mb-0">
                                    <dt class="col-sm-5">Account Name:</dt>
                                    <dd class="col-sm-7">{{ $payout->payment_details['account_name'] ?? 'N/A' }}</dd>
                                    
                                    <dt class="col-sm-5">Account Number:</dt>
                                    <dd class="col-sm-7">****{{ substr($payout->payment_details['account_number'] ?? '', -4) }}</dd>
                                    
                                    <dt class="col-sm-5">Bank Name:</dt>
                                    <dd class="col-sm-7">{{ $payout->payment_details['bank_name'] ?? 'N/A' }}</dd>
                                    
                                    @if(!empty($payout->payment_details['routing_number']))
                                    <dt class="col-sm-5">Routing Number:</dt>
                                    <dd class="col-sm-7">{{ $payout->payment_details['routing_number'] }}</dd>
                                    @endif
                                </dl>
                            @elseif($payout->payment_method == 'paypal')
                                <dl class="row mb-0">
                                    <dt class="col-sm-5">PayPal Email:</dt>
                                    <dd class="col-sm-7">{{ $payout->payment_details['email'] ?? 'N/A' }}</dd>
                                </dl>
                            @elseif($payout->payment_method == 'stripe')
                                <dl class="row mb-0">
                                    <dt class="col-sm-5">Stripe Account ID:</dt>
                                    <dd class="col-sm-7">{{ $payout->payment_details['stripe_account_id'] ?? 'N/A' }}</dd>
                                </dl>
                            @endif
                            
                            @if(!empty($payout->payment_details['transaction_id']))
                            <hr>
                            <dl class="row mb-0">
                                <dt class="col-sm-5">Transaction ID:</dt>
                                <dd class="col-sm-7"><code>{{ $payout->payment_details['transaction_id'] }}</code></dd>
                            </dl>
                            @endif
                            
                            @if(!empty($payout->payment_details['completed_by']))
                            <dl class="row mb-0">
                                <dt class="col-sm-5">Completed By:</dt>
                                <dd class="col-sm-7">{{ $payout->payment_details['completed_by'] }}</dd>
                            </dl>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Update Status Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Payout Status</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Payout Reference (read-only) -->
                <div class="form-group">
                    <label>Payout Reference</label>
                    <input type="text" id="modalReference" class="form-control" readonly>
                </div>

                <!-- Payout Amount (read-only) -->
                <div class="form-group">
                    <label>Payout Amount</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">$</span>
                        </div>
                        <input type="text" id="modalAmount" class="form-control" readonly style="font-weight: bold;">
                    </div>
                </div>

                <!-- Field 1: Status -->
                <div class="form-group">
                    <label>Status *</label>
                    <select name="status" id="modalStatus" class="form-control" required>
                        <option value="processing">Processing</option>
                        <option value="completed">Completed</option>
                        <option value="failed">Failed</option>
                    </select>
                </div>

                <!-- Field 2: Transaction ID (shown only for completed) -->
                <div class="form-group" id="transactionIdGroup" style="display: none;">
                    <label>Transaction ID / Reference *</label>
                    <input type="text" name="transaction_id" id="transaction_id" class="form-control" placeholder="Enter transaction ID or reference number">
                    <small class="text-muted">e.g., PayPal Transaction ID, Bank Transfer Reference, etc.</small>
                </div>

                <!-- Failure Reason (shown only for failed) -->
                <div class="form-group" id="failureReasonGroup" style="display: none;">
                    <label>Failure Reason *</label>
                    <textarea name="failure_reason" id="failure_reason" class="form-control" rows="3" placeholder="Enter reason for failure"></textarea>
                </div>

                <!-- Field 3: Notes (Optional) -->
                <div class="form-group">
                    <label>Notes (Optional)</label>
                    <textarea name="processed_notes" id="processed_notes" class="form-control" rows="2" placeholder="Add any additional notes about this payout"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="submitStatusBtn">
                    <i class="fas fa-save"></i> Update Status
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .avatar-circle {
        width: 80px;
        height: 80px;
        background-color: #007bff;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
    }
    
    .initials {
        font-size: 32px;
        font-weight: bold;
        color: white;
    }
    
    .info-box {
        margin-bottom: 0;
    }
    
    .info-box .info-box-number {
        font-size: 24px;
    }
    
    .badge {
        padding: 5px 10px;
        font-size: 12px;
    }
    
    dl dt {
        font-weight: 600;
        color: #555;
    }
    
    dl dd {
        margin-bottom: 10px;
    }
    
    .modal-backdrop {
        z-index: 1040 !important;
    }
    
    .modal {
        z-index: 1050 !important;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<script>
let currentPayoutId = null;
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

// Update status button click
document.querySelectorAll('.update-status-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        currentPayoutId = this.dataset.id;
        document.getElementById('modalReference').value = this.dataset.reference;
        document.getElementById('modalAmount').value = parseFloat(this.dataset.amount).toFixed(2);
        
        // Reset form
        document.getElementById('modalStatus').value = 'processing';
        document.getElementById('transactionIdGroup').style.display = 'none';
        document.getElementById('failureReasonGroup').style.display = 'none';
        document.getElementById('transaction_id').value = '';
        document.getElementById('failure_reason').value = '';
        document.getElementById('processed_notes').value = '';
        
        // Clear required attributes
        document.getElementById('transaction_id').required = false;
        document.getElementById('failure_reason').required = false;
        
        // Show modal using jQuery
        $('#updateStatusModal').modal('show');
    });
});

// Show/hide fields based on status
const statusSelect = document.getElementById('modalStatus');
if (statusSelect) {
    statusSelect.addEventListener('change', function() {
        const transactionIdGroup = document.getElementById('transactionIdGroup');
        const failureReasonGroup = document.getElementById('failureReasonGroup');
        const transactionIdInput = document.getElementById('transaction_id');
        const failureReasonInput = document.getElementById('failure_reason');
        
        if (this.value === 'completed') {
            transactionIdGroup.style.display = 'block';
            failureReasonGroup.style.display = 'none';
            transactionIdInput.required = true;
            failureReasonInput.required = false;
        } else if (this.value === 'failed') {
            transactionIdGroup.style.display = 'none';
            failureReasonGroup.style.display = 'block';
            transactionIdInput.required = false;
            failureReasonInput.required = true;
        } else {
            transactionIdGroup.style.display = 'none';
            failureReasonGroup.style.display = 'none';
            transactionIdInput.required = false;
            failureReasonInput.required = false;
        }
    });
}

// Handle form submission via button click
document.getElementById('submitStatusBtn').addEventListener('click', function(e) {
    e.preventDefault();
    
    const status = document.getElementById('modalStatus').value;
    const transactionId = document.getElementById('transaction_id').value;
    const failureReason = document.getElementById('failure_reason').value;
    const processedNotes = document.getElementById('processed_notes').value;
    
    // Validate based on status
    if (status === 'completed' && !transactionId) {
        Swal.fire({
            icon: 'error',
            title: 'Validation Error',
            text: 'Please enter Transaction ID for completed payout'
        });
        return;
    }
    
    if (status === 'failed' && !failureReason) {
        Swal.fire({
            icon: 'error',
            title: 'Validation Error',
            text: 'Please enter failure reason for failed payout'
        });
        return;
    }
    
    const formData = {
        status: status,
        transaction_id: transactionId,
        failure_reason: failureReason,
        processed_notes: processedNotes
    };
    
    Swal.fire({
        title: 'Confirm Status Update',
        text: `Are you sure you want to mark this payout as ${status}?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Update',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#dc3545'
    }).then((result) => {
        if (result.isConfirmed) {
            const submitBtn = document.getElementById('submitStatusBtn');
            const originalHtml = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
            submitBtn.disabled = true;
            
            // Send POST request to update status
            fetch(`/admin/payouts/${currentPayoutId}/status`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: data.message,
                        confirmButtonColor: '#28a745'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message,
                        confirmButtonColor: '#dc3545'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to update payout status. Please try again.',
                    confirmButtonColor: '#dc3545'
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

// Close modal on backdrop click and reset
$(document).ready(function() {
    $('#updateStatusModal').on('hidden.bs.modal', function () {
        // Reset form when modal is closed
        document.getElementById('transactionIdGroup').style.display = 'none';
        document.getElementById('failureReasonGroup').style.display = 'none';
        document.getElementById('transaction_id').value = '';
        document.getElementById('failure_reason').value = '';
        document.getElementById('processed_notes').value = '';
        document.getElementById('transaction_id').required = false;
        document.getElementById('failure_reason').required = false;
    });
});

// Add CSRF token meta tag if not exists
if (!document.querySelector('meta[name="csrf-token"]')) {
    const meta = document.createElement('meta');
    meta.name = 'csrf-token';
    meta.content = '{{ csrf_token() }}';
    document.head.appendChild(meta);
}
</script>
@endsection