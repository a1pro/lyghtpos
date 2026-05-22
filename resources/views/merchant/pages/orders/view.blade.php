@extends('merchant.layouts.main')

@section('title', 'Order #' . $order->order_number)
@section('content-header', 'Order Details')
@section('content-actions')
<a href="{{ route('merchant.orders.index') }}" class="btn btn-secondary">
    <i class="fas fa-arrow-left"></i> Back to Orders
</a>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">Order Items</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Price</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $item)
                            <tr>
                                <td>{{ $item->product_name }}</td>
                                <td>{{ $item->quantity }}</td>
                                <td>{{ config('settings.currency_symbol', '$') }} {{ number_format($item->price, 2) }}</td>
                                <td>{{ config('settings.currency_symbol', '$') }} {{ number_format($item->price * $item->quantity, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">Order Summary</h5>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Customer name:</dt>
                    <dd class="col-sm-7"><code>{{ $customer->first_name }} {{ $customer->last_name }}</code></dd>

                    <dt class="col-sm-5">Order Number:</dt>
                    <dd class="col-sm-7"><code>{{ $order->order_number }}</code></dd>

                    <dt class="col-sm-5">Tax Amount:</dt>
                    <dd class="col-sm-7">{{ $order->tax_amount }}</dd>

                    <dt class="col-sm-5">Status:</dt>
                    <dd class="col-sm-7">
                        @php
                        $statusClass = match($order->status) {
                        'completed' => 'badge-success',
                        'pending' => 'badge-warning',
                        'cancelled' => 'badge-danger',
                        default => 'badge-secondary'
                        };
                        @endphp
                        <span class="badge {{ $statusClass }}">{{ ucfirst($order->status) }}</span>
                    </dd>

                    <dt class="col-sm-5">Payment Method:</dt>
                    <dd class="col-sm-7">
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
                    </dd>

                    <dt class="col-sm-5">Payment Status:</dt>
                    <dd class="col-sm-7">
                        <span class="badge {{ $order->payment_status === 'paid' ? 'badge-success' : 'badge-warning' }}">
                            {{ ucfirst($order->payment_status) }}
                        </span>
                    </dd>

                    <dt class="col-sm-5">Created At:</dt>
                    <dd class="col-sm-7">{{ $order->created_at->format('Y-m-d H:i:s') }}</dd>

                    @if($order->notes)
                    <dt class="col-sm-5">Notes:</dt>
                    <dd class="col-sm-7">{{ nl2br(e($order->notes)) }}</dd>
                    @endif
                </dl>
            </div>
        </div>
    </div>
</div>
@endsection
