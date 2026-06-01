@extends('merchant.layouts.main')

@section('title', 'Order #' . $order->order_number)
@section('content-header', 'Order Details')
@section('content-actions')
@if($order->status == 'completed')
<button type="button" class="btn btn-info" data-toggle="modal" data-target="#receiptModal" onclick="loadReceipt()">
    <i class="fas fa-receipt"></i> View Receipt
</button>
@endif

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

                    @if($order->payment_method == 'pos_mobile')
                    <dt class="col-sm-5">Tax Amount:</dt>
                    <dd class="col-sm-7">{{ $order->tax_amount ?? 'N/A' }}</dd>

                    <dt class="col-sm-5">Grand Total:</dt>
                    <dd class="col-sm-7">{{ $order->total_amount + $order->tax_amount ?? 'N/A' }} (incl. tax)</dd>

                    @elseif($order->payment_method == 'hybrid')
                    <dt class="col-sm-5">Card Amount:</dt>
                    <dd class="col-sm-7">{{ $order->card_amount + $order->tax_amount ?? 'N/A' }} (incl. tax)</dd>

                    <dt class="col-sm-5">Token Amount:</dt>
                    <dd class="col-sm-7">{{ $order->token_amount ?? 'N/A' }} (excl. tax)</dd>

                    <dt class="col-sm-5">Tax Amount:</dt>
                    <dd class="col-sm-7">{{ $order->tax_amount ?? 'N/A' }}</dd>

                    <dt class="col-sm-5">Grand Total:</dt>
                    <dd class="col-sm-7">{{ $order->total_amount + $order->tax_amount ?? 'N/A' }} (incl. tax)</dd>

                    @elseif($order->payment_method == 'card')
                    <dt class="col-sm-5">Tax Amount:</dt>
                    <dd class="col-sm-7">{{ $order->tax_amount ?? 'N/A' }}</dd>

                    <dt class="col-sm-5">Grand Total:</dt>
                    <dd class="col-sm-7">{{ $order->total_amount + $order->tax_amount ?? 'N/A' }} (incl. tax)</dd>

                    @elseif($order->payment_method == 'cash')
                    <dt class="col-sm-5">Tax Amount:</dt>
                    <dd class="col-sm-7">{{ $order->tax_amount ?? 'N/A' }}</dd>

                    <dt class="col-sm-5">Grand Total:</dt>
                    <dd class="col-sm-7">{{ $order->total_amount + $order->tax_amount ?? 'N/A' }} (incl. tax)</dd>

                    @elseif($order->payment_method == 'cyclos_tokens')
                    <dt class="col-sm-5">Tax Amount:</dt>
                    <dd class="col-sm-7">{{ $order->tax_amount ?? 'N/A' }}</dd>

                    <dt class="col-sm-5">Grand Total:</dt>
                    <dd class="col-sm-7">{{ $order->total_amount + $order->tax_amount ?? 'N/A' }} (incl. tax)</dd>
                    @endif

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

<!-- Receipt Modal -->
<div class="modal fade" id="receiptModal" tabindex="-1" role="dialog" aria-labelledby="receiptModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="receiptModalLabel">
                    <i class="fas fa-receipt"></i> Order Receipt #{{ $order->order_number }}
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="padding: 0;">
                <div class="text-center p-3" id="receiptLoader">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <p>Loading receipt...</p>
                </div>
                <iframe id="receiptIframe" style="width: 100%; height: 70vh; display: none;" frameborder="0"></iframe>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Close
                </button>
                <button type="button" class="btn btn-warning" onclick="printReceipt()">
                    <i class="fas fa-print"></i> Print Receipt
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('js')
<script>
    function loadReceipt() {
        const iframe = document.getElementById('receiptIframe');
        const loader = document.getElementById('receiptLoader');
        const receiptUrl = "{{ route('merchant.orders.receipt', $order->id) }}?format=web";

        // Show loader, hide iframe
        loader.style.display = 'block';
        iframe.style.display = 'none';

        // Load receipt in iframe
        iframe.src = receiptUrl;

        iframe.onload = function() {
            // Hide loader, show iframe
            loader.style.display = 'none';
            iframe.style.display = 'block';
        };

        iframe.onerror = function() {
            loader.innerHTML = '<i class="fas fa-exclamation-triangle text-danger"></i><p>Error loading receipt. Please try again.</p>';
        };
    }

    function printReceipt() {
        const iframe = document.getElementById('receiptIframe');

        if (iframe && iframe.contentWindow) {
            // Focus the iframe and trigger print
            iframe.contentWindow.focus();
            iframe.contentWindow.print();
        } else {
            alert('Please wait for the receipt to load completely before printing.');
        }
    }

    // Reset modal state when closed
    $('#receiptModal').on('hidden.bs.modal', function() {
        const iframe = document.getElementById('receiptIframe');
        const loader = document.getElementById('receiptLoader');

        iframe.src = 'about:blank';
        iframe.style.display = 'none';
        loader.style.display = 'block';
        loader.innerHTML = '<i class="fas fa-spinner fa-spin fa-2x"></i><p>Loading receipt...</p>';
    });

</script>
@endsection
