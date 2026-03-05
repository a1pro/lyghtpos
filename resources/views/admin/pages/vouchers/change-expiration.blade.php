@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-calendar-alt text-warning mr-2"></i>
                        Change Voucher Expiration Date
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.vouchers.show', $voucher['id'] ?? $voucher['code']) }}" class="btn btn-default btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Voucher Details
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert">×</button>
                            {{ session('error') }}
                        </div>
                    @endif

                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert">×</button>
                            {{ session('success') }}
                        </div>
                    @endif

                    <div class="row">
                        <div class="col-md-8 offset-md-2">
                            <!-- Voucher Summary Card -->
                            <div class="card card-outline card-warning mb-4">
                                <div class="card-header">
                                    <h3 class="card-title">Voucher Summary</h3>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <table class="table table-sm">
                                                <tr>
                                                    <th>Voucher Code:</th>
                                                    <td><code>{{ $voucher['code'] ?? $voucher['id'] ?? 'N/A' }}</code></td>
                                                </tr>
                                                <tr>
                                                    <th>Token:</th>
                                                    <td><code>{{ $voucher['token'] ?? 'N/A' }}</code></td>
                                                </tr>
                                                <tr>
                                                    <th>Amount:</th>
                                                    <td><strong>${{ number_format($voucher['amount'] ?? 0, 2) }}</strong></td>
                                                </tr>
                                                <tr>
                                                    <th>Status:</th>
                                                    <td>
                                                        @php
                                                            $status = $voucher['status'] ?? 'unknown';
                                                            $statusClass = [
                                                                'ACTIVE' => 'success',
                                                                'OPEN' => 'success',
                                                                'REDEEMED' => 'secondary',
                                                                'EXPIRED' => 'warning',
                                                                'CANCELED' => 'danger',
                                                                'SCHEDULED' => 'info'
                                                            ][strtoupper($status)] ?? 'secondary';
                                                        @endphp
                                                        <span class="badge badge-{{ $statusClass }} p-2">
                                                            {{ $status }}
                                                        </span>
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                        <div class="col-md-6">
                                            <table class="table table-sm">
                                                <tr>
                                                    <th>Current Expiration:</th>
                                                    <td>
                                                        @if(isset($voucher['expirationDate']))
                                                            <strong class="text-warning">{{ date('Y-m-d H:i:s', strtotime($voucher['expirationDate'])) }}</strong>
                                                            <br>
                                                            @php
                                                                $expirationDate = strtotime($voucher['expirationDate']);
                                                                $now = time();
                                                                $daysLeft = round(($expirationDate - $now) / (60 * 60 * 24));
                                                            @endphp
                                                            @if($expirationDate > $now)
                                                                <span class="badge badge-info mt-1">{{ $daysLeft }} days remaining</span>
                                                            @else
                                                                <span class="badge badge-danger mt-1">Expired</span>
                                                            @endif
                                                        @else
                                                            <span class="text-muted">No expiration set</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Created Date:</th>
                                                    <td>{{ isset($voucher['creationDate']) ? date('Y-m-d H:i', strtotime($voucher['creationDate'])) : 'N/A' }}</td>
                                                </tr>
                                                @if(isset($voucher['merchant_info']))
                                                <tr>
                                                    <th>Merchant:</th>
                                                    <td>{{ $voucher['merchant_info']['full_name'] }}<br>
                                                        <small class="text-muted">{{ $voucher['merchant_info']['cyclos_username'] }}</small>
                                                    </td>
                                                </tr>
                                                @endif
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Change Expiration Form -->
                            <div class="card card-outline card-primary">
                                <div class="card-header">
                                    <h3 class="card-title">Set New Expiration Date</h3>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle"></i>
                                        Only admins with proper permissions can change voucher expiration dates. 
                                        The new date must be in the future.
                                    </div>

                                    <form action="{{ route('admin.vouchers.change-expiration', $voucher['id'] ?? $voucher['code']) }}" 
                                          method="POST" 
                                          id="expirationForm">
                                        @csrf
                                        
                                        <div class="form-group">
                                            <label for="expirationDate">
                                                New Expiration Date <span class="text-danger">*</span>
                                            </label>
                                            <input type="datetime-local" 
                                                   name="expirationDate" 
                                                   id="expirationDate" 
                                                   class="form-control @error('expirationDate') is-invalid @enderror" 
                                                   value="{{ old('expirationDate', isset($voucher['expirationDate']) ? date('Y-m-d\TH:i', strtotime($voucher['expirationDate'])) : '') }}"
                                                   min="{{ date('Y-m-d\TH:i', strtotime('+1 day')) }}"
                                                   required>
                                            @error('expirationDate')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                            <small class="form-text text-muted">
                                                <i class="fas fa-clock"></i>
                                                Select a future date (must be after today)
                                            </small>
                                        </div>

                                        <div class="form-group">
                                            <label for="comments">Comments / Reason for Change</label>
                                            <textarea name="comments" 
                                                      id="comments" 
                                                      class="form-control @error('comments') is-invalid @enderror" 
                                                      rows="3" 
                                                      placeholder="Enter reason for changing expiration date...">{{ old('comments') }}</textarea>
                                            @error('comments')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                            <small class="form-text text-muted">
                                                Max 500 characters. This will be logged for audit purposes.
                                            </small>
                                        </div>

                                        <div class="form-group">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" 
                                                       name="send_notification" 
                                                       class="custom-control-input" 
                                                       id="sendNotification" 
                                                       value="1"
                                                       {{ old('send_notification', true) ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="sendNotification">
                                                    Send notification to merchant about this change
                                                </label>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <button type="submit" class="btn btn-warning" id="submitBtn">
                                                <i class="fas fa-save"></i> Update Expiration Date
                                            </button>
                                            <a href="{{ route('admin.vouchers.show', $voucher['id'] ?? $voucher['code']) }}" 
                                               class="btn btn-default">
                                                Cancel
                                            </a>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- Quick Extend Options -->
                            {{-- <div class="card card-outline card-success mt-4">
                                <div class="card-header">
                                    <h3 class="card-title">Quick Extend Options</h3>
                                </div>
                                <div class="card-body">
                                    <p>Quickly extend the expiration by a set number of days:</p>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-success" onclick="setExpirationDays(7)">
                                            <i class="fas fa-plus"></i> 7 Days
                                        </button>
                                        <button type="button" class="btn btn-success" onclick="setExpirationDays(30)">
                                            <i class="fas fa-plus"></i> 30 Days
                                        </button>
                                        <button type="button" class="btn btn-success" onclick="setExpirationDays(90)">
                                            <i class="fas fa-plus"></i> 90 Days
                                        </button>
                                        <button type="button" class="btn btn-success" onclick="setExpirationDays(365)">
                                            <i class="fas fa-plus"></i> 1 Year
                                        </button>
                                    </div>
                                </div>
                            </div> --}}

                            <!-- Important Notes -->
                            {{-- <div class="alert alert-warning mt-4">
                                <h5><i class="fas fa-exclamation-triangle"></i> Important Notes:</h5>
                                <ul class="mb-0">
                                    <li>Changing expiration date will affect when this voucher can be redeemed</li>
                                    <li>The merchant will be able to see the new expiration date in their dashboard</li>
                                    <li>This action is logged for audit purposes</li>
                                    <li>You cannot set an expiration date in the past</li>
                                    <li>Only vouchers with status <strong>OPEN</strong> or <strong>EXPIRED</strong> can have their expiration changed</li>
                                </ul>
                            </div> --}}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function setExpirationDays(days) {
    const date = new Date();
    date.setDate(date.getDate() + days);
    
    // Format to YYYY-MM-DDTHH:mm
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    
    const formattedDate = `${year}-${month}-${day}T${hours}:${minutes}`;
    document.getElementById('expirationDate').value = formattedDate;
}

document.getElementById('expirationForm').addEventListener('submit', function(e) {
    const submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
});
</script>
@endpush