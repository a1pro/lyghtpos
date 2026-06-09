@extends('merchant.layouts.main')

@section('title', 'Handpoint Transaction Details')
@section('content-header', 'Handpoint Transaction Details')
@section('content-actions')
<a href="{{ route('merchant.handpoint-logs.index') }}" class="btn btn-secondary">
    <i class="fas fa-arrow-left"></i> Back to Logs
</a>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <!-- Transaction Overview -->
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle"></i> Transaction Overview
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <dl class="row mb-0">
                            <dt class="col-sm-4">Transaction ID:</dt>
                            <dd class="col-sm-8"><code>{{ $log->transaction_id ?? 'N/A' }}</code></dd>

                            <dt class="col-sm-4">Transaction Reference:</dt>
                            <dd class="col-sm-8"><code>{{ $log->transaction_reference ?? 'N/A' }}</code></dd>

                            <dt class="col-sm-4">Order Number:</dt>
                            <dd class="col-sm-8">
                                @if($log->order)
                                <a href="{{ route('merchant.orders.show', $log->order->id) }}">
                                    #{{ $log->order->order_number }}
                                </a>
                                @else
                                N/A
                                @endif
                            </dd>

                            <dt class="col-sm-4">Status:</dt>
                            <dd class="col-sm-8">
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
                                <p class="mt-2 mb-0">{{ $log->status_message }}</p>
                                @endif
                            </dd>

                            <dt class="col-sm-4">Tender Type:</dt>
                            <dd class="col-sm-8">{{ $log->tender_type ?? 'N/A' }}</dd>

                            <dt class="col-sm-4">Date/Time:</dt>
                            <dd class="col-sm-8">{{ isset($log->transaction_timestamp) ? $log->transaction_timestamp->format('Y-m-d H:i:s') : (isset($log->created_at) ? $log->created_at->format('Y-m-d H:i:s') : 'N/A') }}</dd>
                        </dl>
                    </div>
                    <div class="col-md-6">
                        <dl class="row mb-0">
                            <dt class="col-sm-4">Amount:</dt>
                            <dd class="col-sm-8">
                                <h4 class="mb-0">{{ $log->currency ?? 'USD' }} {{ number_format($log->amount ?? 0, 2) }}</h4>
                            </dd>

                            <dt class="col-sm-4">Tip Amount:</dt>
                            <dd class="col-sm-8">{{ isset($log->tip_amount) ? ($log->currency ?? 'USD') . ' ' . number_format($log->tip_amount, 2) : 'N/A' }}</dd>

                            <dt class="col-sm-4">Approved Amount:</dt>
                            <dd class="col-sm-8">{{ isset($log->approved_amount) ? ($log->currency ?? 'USD') . ' ' . number_format($log->approved_amount, 2) : 'N/A' }}</dd>

                            <dt class="col-sm-4">Requested Amount:</dt>
                            <dd class="col-sm-8">{{ isset($log->requested_amount) ? ($log->currency ?? 'USD') . ' ' . number_format($log->requested_amount, 2) : 'N/A' }}</dd>

                            <dt class="col-sm-4">Partial Approval:</dt>
                            <dd class="col-sm-8">{{ ($log->is_partial_approval ?? false) ? 'Yes' : 'No' }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card & EMV Details -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-credit-card"></i> Card Details
                        </h5>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-4">Card Brand:</dt>
                            <dd class="col-sm-8">{{ $log->card_brand ?? 'N/A' }}</dd>

                            <dt class="col-sm-4">Card Last4:</dt>
                            <dd class="col-sm-8">{{ $log->card_last4 ?? 'N/A' }}</dd>

                            <dt class="col-sm-4">Masked Card:</dt>
                            <dd class="col-sm-8">{{ $log->masked_card_number ?? 'N/A' }}</dd>

                            <dt class="col-sm-4">Card Expiry:</dt>
                            <dd class="col-sm-8">{{ $log->card_expiry ?? 'N/A' }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-microchip"></i> EMV Details
                        </h5>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-4">AID:</dt>
                            <dd class="col-sm-8"><code>{{ $log->aid ?? 'N/A' }}</code></dd>

                            <dt class="col-sm-4">ARC:</dt>
                            <dd class="col-sm-8"><code>{{ $log->arc ?? 'N/A' }}</code></dd>

                            <dt class="col-sm-4">Issuer Response:</dt>
                            <dd class="col-sm-8"><code>{{ $log->issuer_response_code ?? 'N/A' }}</code></dd>

                            <dt class="col-sm-4">RRN:</dt>
                            <dd class="col-sm-8"><code>{{ $log->rrn ?? 'N/A' }}</code></dd>

                            <dt class="col-sm-4">Auth Code:</dt>
                            <dd class="col-sm-8"><code>{{ $log->authorization_code ?? 'N/A' }}</code></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Terminal Details -->
        <div class="row mt-3">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-tachometer-alt"></i> Terminal Details
                        </h5>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-4">Device Serial:</dt>
                            <dd class="col-sm-8"><code>{{ $log->device_serial ?? 'N/A' }}</code></dd>

                            <dt class="col-sm-4">Device Status:</dt>
                            <dd class="col-sm-8">{{ $log->device_status_message ?? 'N/A' }}</dd>

                            <dt class="col-sm-4">Battery Status:</dt>
                            <dd class="col-sm-8">{{ isset($log->device_battery_status) ? $log->device_battery_status . '%' : 'N/A' }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-flag-checkered"></i> Transaction Flags
                        </h5>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-4">Partial Approval:</dt>
                            <dd class="col-sm-8">{{ ($log->is_partial_approval ?? false) ? 'Yes' : 'No' }}</dd>

                            <dt class="col-sm-4">Recovered:</dt>
                            <dd class="col-sm-8">{{ ($log->is_recovered ?? false) ? 'Yes' : 'No' }}</dd>

                            <dt class="col-sm-4">Reversal Attempted:</dt>
                            <dd class="col-sm-8">{{ ($log->is_reversal_attempted ?? false) ? 'Yes' : 'No' }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Receipt Links -->
        @if(!empty($log->merchant_receipt_url) || !empty($log->customer_receipt_url))
        <div class="row mt-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-receipt"></i> Receipt Links
                        </h5>
                    </div>
                    <div class="card-body">
                        @if(!empty($log->merchant_receipt_url))
                        <a href="{{ $log->merchant_receipt_url }}" target="_blank" class="btn btn-info mr-2">
                            <i class="fas fa-store"></i> View Merchant Receipt
                        </a>
                        @endif
                        @if(!empty($log->customer_receipt_url))
                        <a href="{{ $log->customer_receipt_url }}" target="_blank" class="btn btn-success">
                            <i class="fas fa-user"></i> View Customer Receipt
                        </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Raw Response -->
        @if(!empty($log->raw_response))
        <div class="row mt-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-code"></i> Raw Response
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <pre style="max-height: 400px; overflow-y: auto; background: #f5f5f5; padding: 15px; margin: 0; border-radius: 0;"><code>{{ json_encode($log->raw_response, JSON_PRETTY_PRINT) }}</code></pre>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Additional Data -->
        @if(!empty($log->additional_data))
        <div class="row mt-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-database"></i> Additional Data
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <pre style="max-height: 300px; overflow-y: auto; background: #f5f5f5; padding: 15px; margin: 0; border-radius: 0;"><code>{{ json_encode($log->additional_data, JSON_PRETTY_PRINT) }}</code></pre>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
