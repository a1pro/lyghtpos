@extends('merchant.layouts.main')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">My Vouchers</h3>
                    <div class="card-tools">
                        <a href="{{ route('merchant.vouchers.create') }}" class="btn btn-primary btn-sm">
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

                    @if(isset($merchant_name))
                    <div class="alert alert-info alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert">×</button>
                        <i class="fas fa-user mr-2"></i>
                        Welcome, <strong>{{ $merchant_name }}</strong> (Cyclos: {{ $cyclos_username }})
                    </div>
                    @endif

                    <!-- Status Filter Tabs -->
                    @if(isset($statusCounts) && $statusCounts['TOTAL'] > 0)
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card card-outline card-primary">
                                <div class="card-header">
                                    <h3 class="card-title">Filter by Status</h3>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex flex-wrap gap-2">
                                        <a href="{{ route('merchant.vouchers.index', ['status' => '']) }}" 
                                           class="btn btn-default {{ !request('status') ? 'active' : '' }}">
                                            All <span class="badge badge-secondary ml-1">{{ $statusCounts['TOTAL'] ?? 0 }}</span>
                                        </a>
                                        
                                        <a href="{{ route('merchant.vouchers.index', ['status' => 'ACTIVE']) }}" 
                                           class="btn btn-success {{ request('status') == 'ACTIVE' ? 'active' : '' }}">
                                            <i class="fas fa-check-circle mr-1"></i>
                                            Active <span class="badge badge-light ml-1">{{ $statusCounts['ACTIVE'] ?? 0 }}</span>
                                        </a>
                                        
                                        <a href="{{ route('merchant.vouchers.index', ['status' => 'REDEEMED']) }}" 
                                           class="btn btn-secondary {{ request('status') == 'REDEEMED' ? 'active' : '' }}">
                                            <i class="fas fa-check-double mr-1"></i>
                                            Redeemed <span class="badge badge-light ml-1">{{ $statusCounts['REDEEMED'] ?? 0 }}</span>
                                        </a>
                                        
                                        <a href="{{ route('merchant.vouchers.index', ['status' => 'EXPIRED']) }}" 
                                           class="btn btn-warning {{ request('status') == 'EXPIRED' ? 'active' : '' }}">
                                            <i class="fas fa-exclamation-triangle mr-1"></i>
                                            Expired <span class="badge badge-light ml-1">{{ $statusCounts['EXPIRED'] ?? 0 }}</span>
                                        </a>
                                        
                                        <a href="{{ route('merchant.vouchers.index', ['status' => 'CANCELED']) }}" 
                                           class="btn btn-danger {{ request('status') == 'CANCELED' ? 'active' : '' }}">
                                            <i class="fas fa-times-circle mr-1"></i>
                                            Canceled <span class="badge badge-light ml-1">{{ $statusCounts['CANCELED'] ?? 0 }}</span>
                                        </a>
                                        
                                        <a href="{{ route('merchant.vouchers.index', ['status' => 'SCHEDULED']) }}" 
                                           class="btn btn-info {{ request('status') == 'SCHEDULED' ? 'active' : '' }}">
                                            <i class="fas fa-clock mr-1"></i>
                                            Scheduled <span class="badge badge-light ml-1">{{ $statusCounts['SCHEDULED'] ?? 0 }}</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Clear Filter Button -->
                    @if(request('status'))
                    <div class="mb-3">
                        <a href="{{ route('merchant.vouchers.index') }}" class="btn btn-default btn-sm">
                            <i class="fas fa-times"></i> Clear Status Filter
                        </a>
                    </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover">
                            <thead>
                                <tr>
                                    <th width="50">#</th>
                                    <th>Voucher Code</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Created Date</th>
                                    <th>Expiration Date</th>
                                    <th width="120">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($vouchers as $index => $voucher)
                                    @php
                                        $status = $voucher['status'] ?? 'unknown';
                                        $statusUpper = strtoupper($status);
                                        $statusClass = [
                                            'ACTIVE' => 'success',
                                            'REDEEMED' => 'secondary',
                                            'EXPIRED' => 'warning',
                                            'CANCELED' => 'danger',
                                            'SCHEDULED' => 'info',
                                            'PENDING' => 'primary'
                                        ][$statusUpper] ?? 'secondary';
                                        
                                        $statusIcons = [
                                            'ACTIVE' => 'check-circle',
                                            'REDEEMED' => 'check-double',
                                            'EXPIRED' => 'exclamation-triangle',
                                            'CANCELED' => 'times-circle',
                                            'SCHEDULED' => 'clock',
                                            'PENDING' => 'hourglass-half'
                                        ];
                                        $icon = $statusIcons[$statusUpper] ?? 'info-circle';
                                    @endphp
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <code>{{ $voucher['code'] ?? $voucher['id'] ?? 'N/A' }}</code>
                                        </td>
                                        <td><strong>${{ number_format($voucher['amount'] ?? 0, 2) }}</strong></td>
                                        <td>
                                            <span class="badge badge-{{ $statusClass }} p-2" style="font-size: 0.9rem; min-width: 100px;">
                                                <i class="fas fa-{{ $icon }} mr-1"></i>
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
                                                @if($statusUpper == 'ACTIVE' && $daysLeft <= 7)
                                                    <br>
                                                    <span class="badge badge-{{ $daysLeft <= 3 ? 'danger' : 'warning' }} mt-1">
                                                        <i class="fas fa-clock mr-1"></i>
                                                        {{ $daysLeft }} day{{ $daysLeft != 1 ? 's' : '' }} left
                                                    </span>
                                                @endif
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                @if(in_array($statusUpper, ['ACTIVE', 'SCHEDULED']))
                                                    <button type="button" 
                                                            class="btn btn-sm btn-warning" 
                                                            onclick="changeExpiration('{{ $voucher['id'] ?? $voucher['code'] }}')" 
                                                            title="Change Expiration">
                                                        <i class="fas fa-calendar-alt"></i>
                                                    </button>
                                                    
                                                    <button type="button" 
                                                            class="btn btn-sm btn-danger" 
                                                            onclick="cancelVoucher('{{ $voucher['id'] ?? $voucher['code'] }}')" 
                                                            title="Cancel Voucher">
                                                        <i class="fas fa-ban"></i>
                                                    </button>
                                                @elseif($statusUpper == 'REDEEMED')
                                                    <span class="badge badge-secondary p-2" style="font-size: 0.9rem;">
                                                        <i class="fas fa-check-circle"></i> Redeemed
                                                    </span>
                                                @elseif($statusUpper == 'EXPIRED')
                                                    <span class="badge badge-warning p-2" style="font-size: 0.9rem;">
                                                        <i class="fas fa-exclamation-triangle"></i> Expired
                                                    </span>
                                                @elseif($statusUpper == 'CANCELED')
                                                    <span class="badge badge-danger p-2" style="font-size: 0.9rem;">
                                                        <i class="fas fa-times-circle"></i> Canceled
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">
                                            @if(isset($error) && $error)
                                                <span class="text-danger">{{ $error }}</span>
                                            @else
                                                <div class="py-5">
                                                    <i class="fas fa-ticket-alt fa-4x text-muted mb-3"></i>
                                                    <h4 class="text-muted">No vouchers found</h4>
                                                    @if(request('status'))
                                                        <p class="text-muted">No {{ request('status') }} vouchers available</p>
                                                        <a href="{{ route('merchant.vouchers.index') }}" class="btn btn-default mt-2">
                                                            <i class="fas fa-times"></i> Clear Filter
                                                        </a>
                                                    @else
                                                        <p class="text-muted">You haven't created any vouchers yet</p>
                                                        <a href="{{ route('merchant.vouchers.create') }}" class="btn btn-primary mt-2">
                                                            <i class="fas fa-plus"></i> Create Your First Voucher
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

                    @if(isset($statusCounts) && $statusCounts['TOTAL'] > 0)
                    <div class="mt-3">
                        <div class="row">
                            <div class="col-md-6">
                                <small class="text-muted">
                                    <i class="fas fa-chart-pie mr-1"></i>
                                    Showing {{ count($vouchers) }} of {{ $statusCounts['TOTAL'] }} total vouchers
                                </small>
                            </div>
                            <div class="col-md-6 text-right">
                                <div class="btn-group btn-group-sm">
                                    <span class="btn btn-default disabled">Summary:</span>
                                    <span class="btn btn-success disabled">
                                        <i class="fas fa-check-circle"></i> {{ $statusCounts['ACTIVE'] ?? 0 }}
                                    </span>
                                    <span class="btn btn-secondary disabled">
                                        <i class="fas fa-check-double"></i> {{ $statusCounts['REDEEMED'] ?? 0 }}
                                    </span>
                                    <span class="btn btn-warning disabled">
                                        <i class="fas fa-exclamation-triangle"></i> {{ $statusCounts['EXPIRED'] ?? 0 }}
                                    </span>
                                    <span class="btn btn-danger disabled">
                                        <i class="fas fa-times-circle"></i> {{ $statusCounts['CANCELED'] ?? 0 }}
                                    </span>
                                </div>
                            </div>
                        </div>
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
        font-size: 0.9rem;
    }
    .gap-2 {
        gap: 0.5rem;
    }
    .btn.active {
        box-shadow: 0 0 0 0.2rem rgba(0,123,255,.5);
        font-weight: bold;
    }
    .table td {
        vertical-align: middle;
    }
    .status-summary .btn.disabled {
        opacity: 0.8;
        cursor: default;
    }
</style>
@endpush

@push('scripts')
<script>
function changeExpiration(voucherId) {
    const modal = $('#expirationModal');
    const form = $('#expirationForm');
    form.attr('action', `{{ url('merchant/vouchers') }}/${voucherId}/change-expiration`);
    modal.modal('show');
}

function cancelVoucher(voucherId) {
    if (confirm('⚠️ Are you sure you want to cancel this voucher?\n\nThis action cannot be undone.')) {
        $.ajax({
            url: `{{ url('merchant/vouchers') }}/${voucherId}/cancel`,
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