@extends('merchant.layouts.main')

@section('title', 'Handpoint Terminal Logs')
@section('content-header', 'Handpoint Terminal Logs')

@section('content')
<div class="row">
    <div class="col-12">
        <!-- Logs Table -->
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Transaction ID</th>
                                <th>Order #</th>
                                <th>Status</th>
                                <th>Amount</th>
                                <th>Card Details</th>
                                <th>RRN</th>
                                <th>Device Serial</th>
                                <th>Date/Time</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $log)
                            <tr>
                                <td>
                                    <code>{{ $log->transaction_id ?? 'N/A' }}</code>
                                    @if($log->transaction_reference)
                                    <br><small class="text-muted">{{ $log->transaction_reference }}</small>
                                    @endif
                                </td>
                                <td>
                                    @if($log->order)
                                    <a href="{{ route('merchant.orders.show', $log->order->id) }}">
                                        #{{ $log->order->order_number ?? 'N/A' }}
                                    </a>
                                    @else
                                    N/A
                                    @endif
                                </td>
                                <td>
                                    @php
                                    $statusClass = match($log->fin_status ?? '') {
                                    'AUTHORISED' => 'badge-success',
                                    'DECLINED' => 'badge-danger',
                                    'PENDING' => 'badge-warning',
                                    default => 'badge-secondary'
                                    };
                                    @endphp
                                    <span class="badge {{ $statusClass }}">{{ $log->fin_status ?? $log->status ?? 'N/A' }}</span>
                                    @if($log->status_message)
                                    <br><small>{{ Str::limit($log->status_message, 30) }}</small>
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ $log->currency ?? 'USD' }} {{ number_format($log->amount ?? 0, 2) }}</strong>
                                    @if($log->tip_amount)
                                    <br><small>Tip: {{ $log->currency ?? 'USD' }} {{ number_format($log->tip_amount, 2) }}</small>
                                    @endif
                                    @if($log->is_partial_approval)
                                    <br><span class="badge badge-warning">Partial</span>
                                    @endif
                                </td>
                                <td>
                                    @if($log->card_brand || $log->card_last4)
                                    <strong>{{ $log->card_brand ?? 'Card' }}</strong>
                                    @if($log->card_last4)
                                    <br>**** {{ $log->card_last4 }}
                                    @endif
                                    @else
                                    N/A
                                    @endif
                                </td>
                                <td>
                                    <code>{{ $log->rrn ?? 'N/A' }}</code>
                                    @if($log->authorization_code)
                                    <br><small>Auth: {{ $log->authorization_code }}</small>
                                    @endif
                                </td>
                                <td>
                                    <code>{{ $log->device_serial ?? 'N/A' }}</code>
                                    @if($log->device_battery_status)
                                    <br><small>Battery: {{ $log->device_battery_status }}%</small>
                                    @endif
                                </td>
                                <td>
                                    {{ isset($log->transaction_timestamp) ? $log->transaction_timestamp->format('Y-m-d H:i:s') : (isset($log->created_at) ? $log->created_at->format('Y-m-d H:i:s') : 'N/A') }}
                                </td>
                                <td>
                                    <a href="{{ route('merchant.handpoint-logs.show', $log->id) }}" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i> Details
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted">
                                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                    No transaction logs found
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($logs->hasPages())
            <div class="card-footer">
                {{ $logs->appends(request()->query())->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    // Auto-submit on filter change (optional)
    $('select[name="status"], select[name="device_serial"]').change(function() {
        $('#filterForm').submit();
    });

</script>
@endsection
