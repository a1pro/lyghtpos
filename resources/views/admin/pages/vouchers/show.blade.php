@extends('layouts.admin')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Voucher Details</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.vouchers.index') }}" class="btn btn-default btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tr>
                                    <th width="200">Voucher Code</th>
                                    <td><code>{{ $voucher['code'] ?? $voucher['id'] ?? 'N/A' }}</code></td>
                                </tr>
                                <tr>
                                    <th>Amount</th>
                                    <td>${{ number_format($voucher['amount'] ?? 0, 2) }}</td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td>
                                        @php
                                            $status = $voucher['status'] ?? 'unknown';
                                            $statusClass = [
                                                'ACTIVE' => 'success',
                                                'REDEEMED' => 'secondary',
                                                'EXPIRED' => 'warning',
                                                'CANCELED' => 'danger',
                                                'SCHEDULED' => 'info'
                                            ][$status] ?? 'secondary';
                                        @endphp
                                        <span class="badge badge-{{ $statusClass }}">{{ $status }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Created Date</th>
                                    <td>{{ isset($voucher['creationDate']) ? date('Y-m-d H:i:s', strtotime($voucher['creationDate'])) : 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Expiration Date</th>
                                    <td>{{ isset($voucher['expirationDate']) ? date('Y-m-d H:i:s', strtotime($voucher['expirationDate'])) : 'N/A' }}</td>
                                </tr>
                            </table>
                        </div>
                        
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tr>
                                    <th width="200">Merchant Information</th>
                                    <td>
                                        @if(isset($voucher['merchant_info']))
                                            <strong>{{ $voucher['merchant_info']['name'] }}</strong><br>
                                            Email: {{ $voucher['merchant_info']['email'] }}<br>
                                            Cyclos: {{ $voucher['merchant_info']['cyclos_username'] }}
                                        @else
                                            <span class="text-muted">{{ $voucher['owner']['display'] ?? 'Unknown' }}</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Created By</th>
                                    <td>{{ $voucher['createdBy']['display'] ?? 'N/A' }}</td>
                                </tr>
                                @if(isset($voucher['redemptionDate']))
                                <tr>
                                    <th>Redemption Date</th>
                                    <td>{{ date('Y-m-d H:i:s', strtotime($voucher['redemptionDate'])) }}</td>
                                </tr>
                                @endif
                                @if(isset($voucher['redeemedBy']))
                                <tr>
                                    <th>Redeemed By</th>
                                    <td>{{ $voucher['redeemedBy']['display'] ?? 'N/A' }}</td>
                                </tr>
                                @endif
                            </table>
                        </div>
                    </div>

                    @if(in_array($voucher['status'] ?? '', ['ACTIVE', 'SCHEDULED']))
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="btn-group">
                                <button type="button" class="btn btn-warning" onclick="changeExpiration('{{ $voucher['id'] ?? $voucher['code'] }}')">
                                    <i class="fas fa-calendar-alt"></i> Change Expiration
                                </button>
                                <button type="button" class="btn btn-danger" onclick="cancelVoucher('{{ $voucher['id'] ?? $voucher['code'] }}')">
                                    <i class="fas fa-ban"></i> Cancel Voucher
                                </button>
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
<div class="modal fade" id="expirationModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="expirationForm" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Change Expiration Date</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>New Expiration Date</label>
                        <input type="datetime-local" name="expirationDate" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function changeExpiration(voucherId) {
    const modal = $('#expirationModal');
    const form = $('#expirationForm');
    form.attr('action', `{{ url('admin/vouchers') }}/${voucherId}/change-expiration`);
    modal.modal('show');
}

function cancelVoucher(voucherId) {
    if (confirm('Are you sure you want to cancel this voucher? This action cannot be undone.')) {
        $.ajax({
            url: `{{ url('admin/vouchers') }}/${voucherId}/cancel`,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                alert(response.message);
                window.location.href = '{{ route('admin.vouchers.index') }}';
            },
            error: function(xhr) {
                alert('Error: ' + (xhr.responseJSON?.error || 'Unknown error'));
            }
        });
    }
}

$('#expirationForm').on('submit', function(e) {
    e.preventDefault();
    $.ajax({
        url: $(this).attr('action'),
        type: 'POST',
        data: $(this).serialize(),
        success: function(response) {
            $('#expirationModal').modal('hide');
            alert(response.message);
            location.reload();
        },
        error: function(xhr) {
            alert('Error: ' + (xhr.responseJSON?.error || 'Unknown error'));
        }
    });
});
</script>
@endpush