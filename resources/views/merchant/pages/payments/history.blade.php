@extends('merchant.layouts.main')

@section('title', 'Payment History')

@section('content')
<div class="card">
    <div class="card-header">
        <h4>Payment History</h4>
    </div>
    <div class="card-body">
        @if($payments->isEmpty())
            <p class="text-center">No payments found.</p>
        @else
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Transaction ID</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Card</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payments as $payment)
                        <tr>
                            <td>{{ $payment->transaction_id }}</td>
                            <td>${{ number_format($payment->amount, 2) }}</td>
                            <td>
                                <span class="badge bg-{{ $payment->status == 'completed' ? 'success' : ($payment->status == 'failed' ? 'danger' : 'warning') }}">
                                    {{ ucfirst($payment->status) }}
                                </span>
                            </td>
                            <td>**** **** **** {{ $payment->card_last_four }} ({{ ucfirst($payment->card_type) }})</td>
                            <td>{{ $payment->created_at->format('M j, Y g:i A') }}</td>
                            <td>
                                <a href="{{ route('merchant.payments.show', $payment->id) }}" class="btn btn-sm btn-info">View</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection