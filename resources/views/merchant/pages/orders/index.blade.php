@extends('merchant.layouts.main')

@section('title', __('order.Orders_List'))
@section('content-header', __('order.Orders_List'))
@section('content-actions')
<a href="{{ route('merchant.cart.index') }}" class="btn btn-primary">{{ __('cart.title') }}</a>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>{{ __('Order Number') }}</th>
                        <th>{{ __('Customer ID') }}</th>
                        <th>{{ __('Total') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Payment Method') }}</th>
                        <th>{{ __('Created At') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                    <tr>
                        <td>{{ $orders->firstItem() + $loop->index }}</td>
                        <td><code>{{ $order->order_number }}</code></td>
                        <td>{{ $order->customer_cyclos_id ?? 'N/A' }}</td>
                        <td><strong>{{ config('settings.currency_symbol', '$') }} {{ number_format($order->total_amount, 2) }}</strong></td>
                        <td>
                            @php
                            $statusClass = match($order->status) {
                            'completed' => 'badge-success',
                            'pending' => 'badge-warning',
                            'cancelled' => 'badge-danger',
                            default => 'badge-secondary'
                            };
                            @endphp
                            <span class="badge {{ $statusClass }}">{{ ucfirst($order->status) }}</span>
                        </td>
                        <td>
                            @php
                            $methodLabels = [
                            'cash' => 'Cash',
                            'unyti' => 'Unyti Credits',
                            'pax' => 'PAX',
                            'pos_mobile' => 'POS Mobile',
                            'cyclos_tokens' => 'Cyclos Tokens',
                            'card' => 'Card',
                            'hybrid' => 'Hybrid (Card + Tokens)',
                            ];
                            @endphp
                            {{ $methodLabels[$order->payment_method] ?? ucfirst($order->payment_method) }}
                        </td>
                        <td>{{ $order->created_at->format('Y-m-d H:i') }}</td>
                        <td>
                            <a href="{{ route('merchant.orders.show', $order) }}" class="btn btn-sm btn-info">
                                <i class="fas fa-eye"></i> View
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted">No orders found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="row mt-3">
            <div class="col-md-6">
                <p class="text-muted">Showing {{ $orders->firstItem() ?? 0 }} to {{ $orders->lastItem() ?? 0 }} of {{ $orders->total() }} orders</p>
            </div>
            <div class="col-md-6">
                {{ $orders->links() }}
            </div>
        </div>
    </div>
</div>
@endsection