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

                    @if($order->failure_message)
                    <dt class="col-sm-5">Failure Reason:</dt>
                    <dd class="col-sm-7">
                       <span class="text-danger">{{$order->failure_message}}</span>
                    </dd>
                    @endif

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

<!-- Handpoint Terminal Logs Section -->
@if(isset($handpointLogs) && $handpointLogs->count() > 0)
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="fas fa-terminal"></i> Handpoint Terminal Logs
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Transaction ID</th>
                                <th>Status</th>
                                <th>Amount</th>
                                <th>Card Details</th>
                                <th>RRN</th>
                                <th>Auth Code</th>
                                <th>Device Serial</th>
                                <th>Date/Time</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($handpointLogs as $log)
                            <tr>
                                <td>
                                    <code>{{ $log->transaction_id ?? 'N/A' }}</code>
                                    @if(!empty($log->transaction_reference))
                                    <br><small class="text-muted">Ref: {{ $log->transaction_reference }}</small>
                                    @endif
                                </td>
                                <td>
                                    @php
                                    $statusBadgeClass = match($log->fin_status ?? '') {
                                    'AUTHORISED' => 'badge-success',
                                    'DECLINED' => 'badge-danger',
                                    'PENDING' => 'badge-warning',
                                    default => 'badge-secondary'
                                    };
                                    @endphp
                                    <span class="badge {{ $statusBadgeClass }}">{{ $log->fin_status ?? $log->status ?? 'N/A' }}</span>
                                    @if(!empty($log->status_message))
                                    <br><small>{{ $log->status_message }}</small>
                                    @endif
                                </td>
                                <td>
                                    {{ $log->currency ?? 'USD' }} {{ number_format($log->amount ?? 0, 2) }}
                                    @if(!empty($log->tip_amount))
                                    <br><small>Tip: {{ $log->currency ?? 'USD' }} {{ number_format($log->tip_amount, 2) }}</small>
                                    @endif
                                    @if($log->is_partial_approval ?? false)
                                    <br><small class="text-warning">Partial Approval</small>
                                    @endif
                                </td>
                                <td>
                                    @if(!empty($log->card_brand) || !empty($log->card_last4))
                                    <strong>{{ $log->card_brand ?? 'Card' }}</strong>
                                    @if(!empty($log->card_last4))
                                    <br>**** {{ $log->card_last4 }}
                                    @endif
                                    @if(!empty($log->card_expiry))
                                    <br><small>Exp: {{ $log->card_expiry }}</small>
                                    @endif
                                    @else
                                    N/A
                                    @endif
                                </td>
                                <td>
                                    <code>{{ $log->rrn ?? 'N/A' }}</code>
                                </td>
                                <td>
                                    <code>{{ $log->authorization_code ?? 'N/A' }}</code>
                                </td>
                                <td>
                                    <code>{{ $log->device_serial ?? 'N/A' }}</code>
                                    @if(!empty($log->device_battery_status))
                                    <br><small>Battery: {{ $log->device_battery_status }}%</small>
                                    @endif
                                </td>
                                <td>
                                    {{ isset($log->transaction_timestamp) ? $log->transaction_timestamp->format('Y-m-d H:i:s') : (isset($log->created_at) ? $log->created_at->format('Y-m-d H:i:s') : 'N/A') }}
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-info" data-toggle="modal" data-target="#handpointModal{{ $log->id ?? '' }}">
                                        <i class="fas fa-eye"></i> Details
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Handpoint Log Detail Modals -->
@foreach($handpointLogs as $log)
<div class="modal fade" id="handpointModal{{ $log->id ?? '' }}" tabindex="-1" role="dialog" aria-labelledby="handpointModalLabel{{ $log->id ?? '' }}" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="handpointModalLabel{{ $log->id ?? '' }}">
                    <i class="fas fa-terminal"></i> Handpoint Transaction Details
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Transaction Information</h6>
                        <dl class="row">
                            <dt class="col-sm-4">Transaction ID:</dt>
                            <dd class="col-sm-8"><code>{{ $log->transaction_id ?? 'N/A' }}</code></dd>

                            <dt class="col-sm-4">Reference:</dt>
                            <dd class="col-sm-8"><code>{{ $log->transaction_reference ?? 'N/A' }}</code></dd>

                            <dt class="col-sm-4">Status:</dt>
                            <dd class="col-sm-8">{{ $log->fin_status ?? $log->status ?? 'N/A' }}</dd>

                            <dt class="col-sm-4">Status Message:</dt>
                            <dd class="col-sm-8">{{ $log->status_message ?? 'N/A' }}</dd>

                            <dt class="col-sm-4">Tender Type:</dt>
                            <dd class="col-sm-8">{{ $log->tender_type ?? 'N/A' }}</dd>
                        </dl>
                    </div>
                    <div class="col-md-6">
                        <h6>Amount Details</h6>
                        <dl class="row">
                            <dt class="col-sm-4">Amount:</dt>
                            <dd class="col-sm-8">{{ $log->currency ?? 'USD' }} {{ number_format($log->amount ?? 0, 2) }}</dd>

                            <dt class="col-sm-4">Tip Amount:</dt>
                            <dd class="col-sm-8">{{ isset($log->tip_amount) ? ($log->currency ?? 'USD') . ' ' . number_format($log->tip_amount, 2) : 'N/A' }}</dd>

                            <dt class="col-sm-4">Approved Amount:</dt>
                            <dd class="col-sm-8">{{ isset($log->approved_amount) ? ($log->currency ?? 'USD') . ' ' . number_format($log->approved_amount, 2) : 'N/A' }}</dd>

                            <dt class="col-sm-4">Requested Amount:</dt>
                            <dd class="col-sm-8">{{ isset($log->requested_amount) ? ($log->currency ?? 'USD') . ' ' . number_format($log->requested_amount, 2) : 'N/A' }}</dd>
                        </dl>
                    </div>
                </div>

                <hr>

                <div class="row">
                    <div class="col-md-6">
                        <h6>EMV & Card Details</h6>
                        <dl class="row">
                            <dt class="col-sm-4">Card Brand:</dt>
                            <dd class="col-sm-8">{{ $log->card_brand ?? 'N/A' }}</dd>

                            <dt class="col-sm-4">Card Last4:</dt>
                            <dd class="col-sm-8">{{ $log->card_last4 ?? 'N/A' }}</dd>

                            <dt class="col-sm-4">Masked Card:</dt>
                            <dd class="col-sm-8">{{ $log->masked_card_number ?? 'N/A' }}</dd>

                            <dt class="col-sm-4">Card Expiry:</dt>
                            <dd class="col-sm-8">{{ $log->card_expiry ?? 'N/A' }}</dd>

                            <dt class="col-sm-4">AID:</dt>
                            <dd class="col-sm-8"><code>{{ $log->aid ?? 'N/A' }}</code></dd>

                            <dt class="col-sm-4">ARC:</dt>
                            <dd class="col-sm-8"><code>{{ $log->arc ?? 'N/A' }}</code></dd>

                            <dt class="col-sm-4">Issuer Response:</dt>
                            <dd class="col-sm-8"><code>{{ $log->issuer_response_code ?? 'N/A' }}</code></dd>
                        </dl>
                    </div>
                    <div class="col-md-6">
                        <h6>Authorization Details</h6>
                        <dl class="row">
                            <dt class="col-sm-4">RRN:</dt>
                            <dd class="col-sm-8"><code>{{ $log->rrn ?? 'N/A' }}</code></dd>

                            <dt class="col-sm-4">Auth Code:</dt>
                            <dd class="col-sm-8"><code>{{ $log->authorization_code ?? 'N/A' }}</code></dd>

                            <dt class="col-sm-4">Partial Approval:</dt>
                            <dd class="col-sm-8">{{ ($log->is_partial_approval ?? false) ? 'Yes' : 'No' }}</dd>

                            <dt class="col-sm-4">Recovered:</dt>
                            <dd class="col-sm-8">{{ ($log->is_recovered ?? false) ? 'Yes' : 'No' }}</dd>

                            <dt class="col-sm-4">Reversal Attempted:</dt>
                            <dd class="col-sm-8">{{ ($log->is_reversal_attempted ?? false) ? 'Yes' : 'No' }}</dd>
                        </dl>

                        <h6 class="mt-3">Terminal Details</h6>
                        <dl class="row">
                            <dt class="col-sm-4">Device Serial:</dt>
                            <dd class="col-sm-8"><code>{{ $log->device_serial ?? 'N/A' }}</code></dd>

                            <dt class="col-sm-4">Device Status:</dt>
                            <dd class="col-sm-8">{{ $log->device_status_message ?? 'N/A' }}</dd>

                            <dt class="col-sm-4">Battery Status:</dt>
                            <dd class="col-sm-8">{{ isset($log->device_battery_status) ? $log->device_battery_status . '%' : 'N/A' }}</dd>
                        </dl>
                    </div>
                </div>

                @if(!empty($log->merchant_receipt_url) || !empty($log->customer_receipt_url))
                <hr>
                <div class="row">
                    <div class="col-12">
                        <h6>Receipt Links</h6>
                        @if(!empty($log->merchant_receipt_url))
                        <a href="{{ $log->merchant_receipt_url }}" target="_blank" class="btn btn-sm btn-info">
                            <i class="fas fa-store"></i> Merchant Receipt
                        </a>
                        @endif
                        @if(!empty($log->customer_receipt_url))
                        <a href="{{ $log->customer_receipt_url }}" target="_blank" class="btn btn-sm btn-success">
                            <i class="fas fa-user"></i> Customer Receipt
                        </a>
                        @endif
                    </div>
                </div>
                @endif

                @if(!empty($log->raw_response))
                <hr>
                <div class="row">
                    <div class="col-12">
                        <h6>Raw Response</h6>
                        <pre style="max-height: 200px; overflow-y: auto; background: #f5f5f5; padding: 10px; border-radius: 5px;"><code>{{ json_encode($log->raw_response, JSON_PRETTY_PRINT) }}</code></pre>
                    </div>
                </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Close
                </button>
            </div>
        </div>
    </div>
</div>
@endforeach
@endif


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
