@extends('merchant.layouts.main')

@section('title', __('order.Orders_List'))
@section('content-header', __('order.Orders_List'))
@section('content-actions')
<a href="{{ route('merchant.cart.index') }}" class="btn btn-primary">{{ __('cart.title') }}</a>
@endsection
@section('content')

<div class="card">
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-7">
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="refreshOrders">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="thead-light">
                    <tr>
                        <th>{{ __('Serial No.') }}</th>
                        <th>{{ __('Order ID') }}</th>
                        <th>{{ __('Customer_Name') }}</th>
                        <th>{{ __('Total') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Created_At') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            <code>{{ $order['number'] ?? 'N/A' }}</code>
                        </td>
                        <td>{{ $order['customer']['name'] ?? 'N/A' }}</td>
                        <td>
                            <strong>{{ $order['total_price_formatted'] ?? '0.00' }}</strong>
                        </td>
                        <td>
                            <span class="badge {{ $order['status_badge_class'] ?? 'badge-secondary' }}">
                                {{ $order['status_label'] ?? ucfirst($order['status'] ?? 'Unknown') }}
                            </span>
                        </td>
                        <td>{{ $order['creation_date_formatted'] ?? 'N/A' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted">
                            No orders found
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(isset($headers['x-total-count']) && $headers['x-total-count'] > 0)
        <div class="row mt-3">
            <div class="col-md-6">
                <p class="text-muted">
                    Showing {{ count($orders) }} of {{ $headers['x-total-count'] }} orders
                </p>
            </div>
            <div class="col-md-6">
                <!-- Pagination can be added here if needed -->
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Order Details Modal -->
<div class="modal fade" id="orderDetailsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Order Details</h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="orderDetailsContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                        <p class="mt-2">Loading order details...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        const currencySymbol = '{{ config("settings.currency_symbol", "TT") }}';

        // Refresh orders list
        $('#refreshOrders').on('click', function() {
            location.reload();
        });
    });

</script>
@endsection
