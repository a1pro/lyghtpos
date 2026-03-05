@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">All Vouchers</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.vouchers.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Create New Vouchers
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert">×</button>
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(isset($error) && $error)
                        <div class="alert alert-danger alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert">×</button>
                            {{ $error }}
                        </div>
                    @endif

                    <!-- Status Filter Tabs -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card card-outline card-primary">
                                <div class="card-header">
                                    <h3 class="card-title">Filter by Status</h3>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex flex-wrap gap-2">
                                        <a href="{{ route('admin.vouchers.index', array_merge(request()->except('status'), ['status' => ''])) }}" 
                                           class="btn btn-default {{ !request('status') ? 'active' : '' }}">
                                            All <span class="badge badge-secondary ml-1">{{ $statusCounts['TOTAL'] ?? 0 }}</span>
                                        </a>
                                        
                                        <a href="{{ route('admin.vouchers.index', array_merge(request()->except('status'), ['status' => 'ACTIVE'])) }}" 
                                           class="btn btn-success {{ request('status') == 'ACTIVE' ? 'active' : '' }}">
                                            Active <span class="badge badge-light ml-1">{{ $statusCounts['ACTIVE'] ?? 0 }}</span>
                                        </a>
                                        
                                        <a href="{{ route('admin.vouchers.index', array_merge(request()->except('status'), ['status' => 'REDEEMED'])) }}" 
                                           class="btn btn-secondary {{ request('status') == 'REDEEMED' ? 'active' : '' }}">
                                            Redeemed <span class="badge badge-light ml-1">{{ $statusCounts['REDEEMED'] ?? 0 }}</span>
                                        </a>
                                        
                                        <a href="{{ route('admin.vouchers.index', array_merge(request()->except('status'), ['status' => 'EXPIRED'])) }}" 
                                           class="btn btn-warning {{ request('status') == 'EXPIRED' ? 'active' : '' }}">
                                            Expired <span class="badge badge-light ml-1">{{ $statusCounts['EXPIRED'] ?? 0 }}</span>
                                        </a>
                                        
                                        <a href="{{ route('admin.vouchers.index', array_merge(request()->except('status'), ['status' => 'CANCELED'])) }}" 
                                           class="btn btn-danger {{ request('status') == 'CANCELED' ? 'active' : '' }}">
                                            Canceled <span class="badge badge-light ml-1">{{ $statusCounts['CANCELED'] ?? 0 }}</span>
                                        </a>
                                        
                                        <a href="{{ route('admin.vouchers.index', array_merge(request()->except('status'), ['status' => 'SCHEDULED'])) }}" 
                                           class="btn btn-info {{ request('status') == 'SCHEDULED' ? 'active' : '' }}">
                                            Scheduled <span class="badge badge-light ml-1">{{ $statusCounts['SCHEDULED'] ?? 0 }}</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filter by Merchant -->
                    @if(count($merchants) > 0)
                    <form method="GET" class="mb-4">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">Filter by Merchant</span>
                                    </div>
                                    <select name="merchant_id" class="form-control" onchange="this.form.submit()">
                                        <option value="">All Merchants</option>
                                        @foreach($merchants as $merchant)
                                            <option value="{{ $merchant->id }}" {{ isset($selectedMerchantId) && $selectedMerchantId == $merchant->id ? 'selected' : '' }}>
                                                {{ $merchant->first_name }} {{ $merchant->last_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            @if(isset($selectedMerchantId) && $selectedMerchantId || isset($selectedStatus) && $selectedStatus)
                            <div class="col-md-2">
                                <a href="{{ route('admin.vouchers.index') }}" class="btn btn-default">Clear All Filters</a>
                            </div>
                            @endif
                        </div>
                        <!-- Preserve status filter when changing merchant -->
                        @if(isset($selectedStatus) && $selectedStatus)
                            <input type="hidden" name="status" value="{{ $selectedStatus }}">
                        @endif
                    </form>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover">
                            <thead>
                                <tr>
                                    <th width="50">#</th>
                                    <th>Voucher Code</th>
                                    <th>Merchant</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Created Date</th>
                                    <th>Expiration Date</th>
                                    <th width="150">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($vouchers as $index => $voucher)
                                    @php
                                        $status = $voucher['status'] ?? 'unknown';
                                        $statusClass = [
                                            'ACTIVE' => 'success',
                                            'REDEEMED' => 'secondary',
                                            'EXPIRED' => 'warning',
                                            'CANCELED' => 'danger',
                                            'SCHEDULED' => 'info',
                                            'PENDING' => 'primary'
                                        ][strtoupper($status)] ?? 'secondary';
                                    @endphp
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <code>{{ $voucher['code'] ?? $voucher['id'] ?? 'N/A' }}</code>
                                        </td>
                                        <td>
                                            @if(isset($voucher['merchant_info']))
                                                <strong>{{ $voucher['merchant_info']['first_name'] ?? '' }} {{ $voucher['merchant_info']['last_name'] ?? '' }}</strong><br>
                                                <small class="text-muted">{{ $voucher['merchant_info']['cyclos_username'] }}</small>
                                            @else
                                                <span class="text-muted">{{ $voucher['owner']['display'] ?? 'Unknown' }}</span>
                                            @endif
                                        </td>
                                        <td><strong>${{ number_format($voucher['amount'] ?? 0, 2) }}</strong></td>
                                        <td>
                                            <span class="badge badge-{{ $statusClass }} p-2" style="font-size: 0.9rem;">
                                                <i class="fas fa-{{ $statusClass == 'success' ? 'check-circle' : ($statusClass == 'warning' ? 'exclamation-triangle' : ($statusClass == 'danger' ? 'times-circle' : 'info-circle')) }} mr-1"></i>
                                                {{ $status }}
                                            </span>
                                        </td>
                                        <td>{{ isset($voucher['creationDate']) ? date('Y-m-d H:i', strtotime($voucher['creationDate'])) : 'N/A' }}</td>
                                        <td>
                                            @if(isset($voucher['expirationDate']))
                                                @php
                                                    $expirationDate = strtotime($voucher['expirationDate']);
                                                    $now = time();
                                                    $daysLeft = round(($expirationDate - $now) / (60 * 60 * 24));
                                                @endphp
                                                {{ date('Y-m-d H:i', $expirationDate) }}
                                                @if($status == 'ACTIVE' && $daysLeft <= 7)
                                                    <br><span class="badge badge-warning mt-1">{{ $daysLeft }} days left</span>
                                                @endif
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="{{ route('admin.vouchers.show', $voucher['id'] ?? $voucher['code']) }}" 
                                                   class="btn btn-sm btn-info" 
                                                   title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                
                                                @if(in_array(strtoupper($status), ['ACTIVE', 'OPEN', 'EXPIRED']))
                                                    <a href="{{ route('admin.vouchers.edit-expiration', $voucher['id'] ?? $voucher['code']) }}" 
                                                       class="btn btn-sm btn-warning" 
                                                       title="Change Expiration">
                                                        <i class="fas fa-calendar-alt"></i>
                                                    </a>
                                                @endif
                                                
                                                {{-- @if(in_array(strtoupper($status), ['ACTIVE', 'OPEN']))
                                                    <button type="button" 
                                                            class="btn btn-sm btn-danger" 
                                                            onclick="cancelVoucher('{{ $voucher['id'] ?? $voucher['code'] }}')" 
                                                            title="Cancel Voucher">
                                                        <i class="fas fa-ban"></i>
                                                    </button>
                                                @endif --}}
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">
                                            @if(isset($error) && $error)
                                                <span class="text-danger">{{ $error }}</span>
                                            @else
                                                <div class="py-4">
                                                    <i class="fas fa-ticket-alt fa-3x text-muted mb-3"></i>
                                                    <h5>No vouchers found</h5>
                                                    @if(request('status') || request('merchant_id'))
                                                        <p class="text-muted">Try clearing your filters</p>
                                                        <a href="{{ route('admin.vouchers.index') }}" class="btn btn-default">
                                                            <i class="fas fa-times"></i> Clear Filters
                                                        </a>
                                                    @endif
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    @if(isset($total_vouchers) && $total_vouchers > 0)
                    <div class="mt-3">
                        <small class="text-muted">
                            <i class="fas fa-chart-pie mr-1"></i>
                            Showing {{ count($vouchers) }} of {{ $total_vouchers }} total vouchers
                        </small>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Change Expiration Modal -->
<div class="modal fade" id="expirationModal" tabindex="-1" role="dialog" aria-labelledby="expirationModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="expirationForm" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="expirationModalLabel">
                        <i class="fas fa-calendar-alt text-warning mr-2"></i>
                        Change Expiration Date
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="expirationDate">New Expiration Date</label>
                        <input type="datetime-local" 
                               name="expirationDate" 
                               id="expirationDate" 
                               class="form-control" 
                               min="{{ date('Y-m-d\TH:i') }}"
                               required>
                        <small class="form-text text-muted">
                            <i class="fas fa-info-circle"></i>
                            Select a future date for the voucher expiration
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save"></i> Update Expiration
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .btn-group .btn {
        margin: 0 2px;
    }
    .badge {
        font-weight: 500;
    }
    .gap-2 {
        gap: 0.5rem;
    }
    .btn.active {
        box-shadow: 0 0 0 0.2rem rgba(0,123,255,.5);
    }
</style>
@endpush

@push('scripts')
<script>
function changeExpiration(voucherId) {
    const modal = $('#expirationModal');
    const form = $('#expirationForm');
    form.attr('action', `{{ url('admin/vouchers') }}/${voucherId}/change-expiration`);
    modal.modal('show');
}

function cancelVoucher(voucherId) {
    if (confirm('⚠️ Are you sure you want to cancel this voucher?\n\nThis action cannot be undone.')) {
        $.ajax({
            url: `{{ url('admin/vouchers') }}/${voucherId}/cancel`,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            beforeSend: function() {
                // Show loading state
            },
            success: function(response) {
                alert('✓ ' + response.message);
                location.reload();
            },
            error: function(xhr) {
                alert('✗ Error: ' + (xhr.responseJSON?.error || 'Failed to cancel voucher'));
            }
        });
    }
}

$('#expirationForm').on('submit', function(e) {
    e.preventDefault();
    
    const form = $(this);
    const submitBtn = form.find('button[type="submit"]');
    submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Updating...');
    
    $.ajax({
        url: form.attr('action'),
        type: 'POST',
        data: form.serialize(),
        success: function(response) {
            $('#expirationModal').modal('hide');
            alert('✓ ' + response.message);
            location.reload();
        },
        error: function(xhr) {
            alert('✗ Error: ' + (xhr.responseJSON?.error || 'Failed to update expiration'));
            submitBtn.prop('disabled', false).html('<i class="fas fa-save"></i> Update Expiration');
        }
    });
});
</script>
@endpush