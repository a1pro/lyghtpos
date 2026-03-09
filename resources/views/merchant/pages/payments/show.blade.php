@extends('merchant.layouts.main')

@section('title', 'Payment Details')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h4>Payment Details</h4>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-sm-4 fw-bold">Transaction ID:</div>
                    <div class="col-sm-8">{{ $payment->transaction_id }}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4 fw-bold">Amount:</div>
                    <div class="col-sm-8">${{ number_format($payment->amount, 2) }}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4 fw-bold">Currency:</div>
                    <div class="col-sm-8">{{ $payment->currency }}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4 fw-bold">Status:</div>
                    <div class="col-sm-8">
                        <span class="badge bg-{{ $payment->status == 'completed' ? 'success' : ($payment->status == 'failed' ? 'danger' : 'warning') }}">
                            {{ ucfirst($payment->status) }}
                        </span>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4 fw-bold">Card Number:</div>
                    <div class="col-sm-8">**** **** **** {{ $payment->card_last_four }}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4 fw-bold">Card Type:</div>
                    <div class="col-sm-8">{{ ucfirst($payment->card_type) }}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4 fw-bold">Date:</div>
                    <div class="col-sm-8">{{ $payment->created_at->format('F j, Y g:i A') }}</div>
                </div>
                @if($payment->response_message)
                <div class="row mb-3">
                    <div class="col-sm-4 fw-bold">Response:</div>
                    <div class="col-sm-8">{{ $payment->response_message }}</div>
                </div>
                @endif

                <div class="d-grid gap-2">
                    <a href="{{ route('merchant.payments.history') }}" class="btn btn-outline-secondary">Back to History</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection