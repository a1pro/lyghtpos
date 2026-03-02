@extends('merchant.layouts.main')

@section('title', __('My Cyclos Vouchers'))
@section('content-header', __('My Cyclos Vouchers'))

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-ticket-alt"></i> My Vouchers (Cyclos)
                
            </h3>
            <div class="card-tools">
                <a href="{{ route('merchant.vouchers.create') }}" class="btn btn-success btn-sm">
                    <i class="fas fa-plus"></i> Generate New Voucher
                </a>
            </div>
        </div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <i class="fas fa-exclamation-triangle"></i> {{ session('error') }}
                </div>
            @endif

            @if(isset($error) && $error)
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> {{ $error }}
                </div>
            @endif

            <!-- Debug Info - Remove in production -->
           

            @if(empty($vouchers))
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> 
                    No vouchers found for your account. 
                    <a href="{{ route('merchant.vouchers.create') }}" class="btn btn-primary btn-sm mt-2">
                        <i class="fas fa-plus"></i> Generate Your First Voucher
                    </a>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover">
                        <thead class="thead-dark">
                            <tr>
                                <th width="50">#</th>
                                <th>Voucher Code</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Expiry Date</th>
                                <th>Created Date</th>
                                {{-- <th width="200">Actions</th> --}}
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($vouchers as $index => $voucher)
                                @php
                                    $voucherId = $voucher['id'] ?? '';
                                    $code = $voucher['token'] ?? $voucher['code'] ?? 'N/A';
                                    $amount = $voucher['amount'] ?? $voucher['value'] ?? 0;
                                    $status = strtolower($voucher['status'] ?? 'unknown');
                                    $expiry = $voucher['expirationDate'] ?? $voucher['expiryDate'] ?? $voucher['validTo'] ?? null;
                                    $created = $voucher['creationDate'] ?? $voucher['createdDate'] ?? $voucher['issueDate'] ?? null;
                                    
                                    $badgeClass = match($status) {
                                        'open', 'active', 'new' => 'success',
                                        'redeemed', 'used' => 'secondary',
                                        'expired' => 'danger',
                                        'canceled', 'cancelled' => 'dark',
                                        default => 'warning'
                                    };
                                    
                                    $canEdit = in_array($status, ['open', 'active', 'expired']);
                                    $canCancel = in_array($status, ['open', 'active', 'expired', 'pending']);
                                @endphp
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <code class="bg-light p-1 rounded" style="font-size: 0.9rem; font-family: monospace;">
                                            {{ $code }}
                                        </code>
                                    </td>
                                    <td>
                                        <span class="font-weight-bold text-primary">
                                            ${{ number_format($amount, 2) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $badgeClass }} p-2" style="font-size: 0.9rem;">
                                            {{ ucfirst($status) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($expiry)
                                            <i class="far fa-calendar-alt text-muted"></i> 
                                            {{ date('d-m-Y', strtotime($expiry)) }}
                                        @else
                                            <span class="text-muted font-italic">No expiry</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($created)
                                            <i class="far fa-clock text-muted"></i> 
                                            {{ date('d-m-Y H:i', strtotime($created)) }}
                                        @else
                                            <span class="text-muted font-italic">N/A</span>
                                        @endif
                                    </td>
                                    {{-- <td>
                                        <div class="btn-group" role="group">
                                            @if($canEdit)
                                                <button type="button" class="btn btn-sm btn-warning" 
                                                        onclick="editVoucher('{{ $voucherId }}', '{{ $expiry }}')"
                                                        data-toggle="modal" data-target="#editVoucherModal"
                                                        title="Edit Expiration Date">
                                                    <i class="fas fa-calendar-alt"></i> Edit Expiry
                                                </button>
                                            @endif
                                            
                                            @if($canCancel)
                                                <button type="button" class="btn btn-sm btn-danger" 
                                                        onclick="cancelVoucher('{{ $voucherId }}', '{{ $code }}')"
                                                        title="Cancel Voucher">
                                                    <i class="fas fa-ban"></i> Cancel
                                                </button>
                                            @endif
                                            
                                            @if(!$canEdit && !$canCancel)
                                                <span class="text-muted small">No actions</span>
                                            @endif
                                        </div>
                                    </td> --}}
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-3 p-2 bg-light rounded">
                    <small class="text-muted">
                        <i class="fas fa-info-circle"></i> 
                        Showing <strong>{{ count($vouchers) }}</strong> voucher(s) created by 
                        <strong class="text-primary">{{ $cyclos_username ?? 'you' }}</strong>
                    </small>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Edit Expiration Date Modal -->
<div class="modal fade" id="editVoucherModal" tabindex="-1" role="dialog" aria-labelledby="editVoucherModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="editVoucherModalLabel">
                    <i class="fas fa-calendar-alt"></i> Edit Voucher Expiration Date
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="editVoucherForm">
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="edit_voucher_id" name="voucher_id">
                    
                    <div class="form-group">
                        <label for="edit_expiry">Expiration Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="edit_expiry" name="expirationDate" required min="{{ date('Y-m-d') }}">
                        <small class="text-muted">Change the expiration date for this voucher. Must be a future date.</small>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> 
                        Note: You can change the expiration date for vouchers with status: <strong>Open, Active, or Expired</strong>.
                    </div>
                    
                    <div id="editError" class="alert alert-danger" style="display: none;"></div>
                    <div id="editSuccess" class="alert alert-success" style="display: none;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning" id="saveEditBtn">
                        <i class="fas fa-save"></i> Update Expiration Date
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Cancel Voucher Confirmation Modal -->
<div class="modal fade" id="cancelVoucherModal" tabindex="-1" role="dialog" aria-labelledby="cancelVoucherModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="cancelVoucherModalLabel">
                    <i class="fas fa-exclamation-triangle"></i> Confirm Cancel Voucher
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to cancel this voucher? This action will change the status to <strong>Canceled</strong>.</p>
                <div class="card bg-light p-3 mb-3">
                    <p><strong>Voucher Code:</strong> <span id="cancel_voucher_code" class="font-weight-bold"></span></p>
                    <p><strong>Status:</strong> Will be changed to <span class="badge bg-dark">Canceled</span></p>
                </div>
                <p class="text-warning">
                    <i class="fas fa-exclamation-triangle"></i> 
                    <strong>Warning:</strong> This action cannot be undone. If the voucher was bought, the buyer will be refunded.
                </p>
                <input type="hidden" id="cancel_voucher_id">
                
                <div id="cancelError" class="alert alert-danger" style="display: none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Keep Voucher</button>
                <button type="button" class="btn btn-danger" id="confirmCancelBtn">
                    <i class="fas fa-ban"></i> Yes, Cancel Voucher
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .table th {
        background-color: #f4f6f9;
    }
    .badge {
        font-size: 0.9rem;
        padding: 0.5rem 0.8rem;
    }
    .btn-group .btn {
        margin: 0 2px;
    }
    code {
        background-color: #f8f9fa;
        padding: 4px 8px;
        border-radius: 4px;
        border: 1px solid #dee2e6;
        display: inline-block;
        font-size: 0.9rem;
        font-family: monospace;
    }
    .modal-header.bg-warning {
        color: #856404;
    }
    .modal-header.bg-danger {
        color: white;
    }
</style>
@endpush

@push('scripts')
<script>
// Edit Voucher - Change Expiration Date
function editVoucher(voucherId, currentExpiry) {
    if (!voucherId) return;
    
    document.getElementById('edit_voucher_id').value = voucherId;
    document.getElementById('editError').style.display = 'none';
    document.getElementById('editSuccess').style.display = 'none';
    
    // Format current expiry date for input field
    if (currentExpiry && currentExpiry !== 'null' && currentExpiry !== 'No expiry') {
        try {
            const date = new Date(currentExpiry);
            if (!isNaN(date.getTime())) {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                document.getElementById('edit_expiry').value = `${year}-${month}-${day}`;
            } else {
                document.getElementById('edit_expiry').value = '';
            }
        } catch (e) {
            document.getElementById('edit_expiry').value = '';
        }
    } else {
        // Set default to 1 year from now if no expiry
        const date = new Date();
        date.setFullYear(date.getFullYear() + 1);
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        document.getElementById('edit_expiry').value = `${year}-${month}-${day}`;
    }
}

// Handle Edit Form Submit - Change Expiration
document.getElementById('editVoucherForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const voucherId = document.getElementById('edit_voucher_id').value;
    const expiry = document.getElementById('edit_expiry').value;
    
    if (!expiry) {
        document.getElementById('editError').style.display = 'block';
        document.getElementById('editError').innerHTML = '<i class="fas fa-exclamation-circle"></i> Expiration date is required';
        return;
    }
    
    // Validate that expiry date is in the future
    const selectedDate = new Date(expiry);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    if (selectedDate < today) {
        document.getElementById('editError').style.display = 'block';
        document.getElementById('editError').innerHTML = '<i class="fas fa-exclamation-circle"></i> Expiration date must be today or in the future';
        return;
    }
    
    const formData = new FormData();
    formData.append('expirationDate', expiry);
    
    document.getElementById('saveEditBtn').disabled = true;
    document.getElementById('saveEditBtn').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
    
    fetch(`/merchant/vouchers/${voucherId}/change-expiration`, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('saveEditBtn').disabled = false;
        document.getElementById('saveEditBtn').innerHTML = '<i class="fas fa-save"></i> Update Expiration Date';
        
        if (data.success) {
            document.getElementById('editSuccess').style.display = 'block';
            document.getElementById('editSuccess').innerHTML = '<i class="fas fa-check-circle"></i> ' + data.message;
            
            // Close modal after 1.5 seconds and reload
            setTimeout(() => {
                $('#editVoucherModal').modal('hide');
                location.reload();
            }, 1500);
        } else {
            document.getElementById('editError').style.display = 'block';
            document.getElementById('editError').innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + data.error;
        }
    })
    .catch(error => {
        document.getElementById('saveEditBtn').disabled = false;
        document.getElementById('saveEditBtn').innerHTML = '<i class="fas fa-save"></i> Update Expiration Date';
        document.getElementById('editError').style.display = 'block';
        document.getElementById('editError').innerHTML = '<i class="fas fa-exclamation-circle"></i> Network error: ' + error.message;
    });
});

// Cancel Voucher
function cancelVoucher(voucherId, voucherCode) {
    if (!voucherId) return;
    
    document.getElementById('cancel_voucher_id').value = voucherId;
    document.getElementById('cancel_voucher_code').innerText = voucherCode || 'Unknown';
    document.getElementById('cancelError').style.display = 'none';
    
    $('#cancelVoucherModal').modal('show');
}

// Confirm Cancel
document.getElementById('confirmCancelBtn').addEventListener('click', function() {
    const voucherId = document.getElementById('cancel_voucher_id').value;
    
    this.disabled = true;
    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Canceling...';
    
    fetch(`/merchant/vouchers/${voucherId}/cancel`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        this.disabled = false;
        this.innerHTML = '<i class="fas fa-ban"></i> Yes, Cancel Voucher';
        
        if (data.success) {
            $('#cancelVoucherModal').modal('hide');
            
            // Show success message and reload
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-success alert-dismissible';
            alertDiv.innerHTML = `
                <button type="button" class="close" data-dismiss="alert">×</button>
                <i class="fas fa-check-circle"></i> ${data.message}
            `;
            document.querySelector('.card-body').insertBefore(alertDiv, document.querySelector('.card-body').firstChild);
            
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            document.getElementById('cancelError').style.display = 'block';
            document.getElementById('cancelError').innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + data.error;
            
            // Re-enable button
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-ban"></i> Yes, Cancel Voucher';
        }
    })
    .catch(error => {
        this.disabled = false;
        this.innerHTML = '<i class="fas fa-ban"></i> Yes, Cancel Voucher';
        document.getElementById('cancelError').style.display = 'block';
        document.getElementById('cancelError').innerHTML = '<i class="fas fa-exclamation-circle"></i> Network error: ' + error.message;
    });
});

// Reset modals when closed
$('#editVoucherModal').on('hidden.bs.modal', function () {
    document.getElementById('editError').style.display = 'none';
    document.getElementById('editSuccess').style.display = 'none';
    document.getElementById('saveEditBtn').disabled = false;
    document.getElementById('saveEditBtn').innerHTML = '<i class="fas fa-save"></i> Update Expiration Date';
});

$('#cancelVoucherModal').on('hidden.bs.modal', function () {
    document.getElementById('cancelError').style.display = 'none';
    const cancelBtn = document.getElementById('confirmCancelBtn');
    cancelBtn.disabled = false;
    cancelBtn.innerHTML = '<i class="fas fa-ban"></i> Yes, Cancel Voucher';
});

// Helper function to format date for display
function formatDisplayDate(dateString) {
    if (!dateString) return 'N/A';
    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return dateString;
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    } catch (e) {
        return dateString;
    }
}
</script>
@endpush