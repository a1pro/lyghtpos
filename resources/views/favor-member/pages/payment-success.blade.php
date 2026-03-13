@extends('favor-member.layouts.main')

@section('content-header')
    Payment Successful
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card card-success">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-check-circle mr-2"></i>
                        Payment Successful
                    </h3>
                </div>
                <div class="card-body text-center py-5">
                    <i class="fas fa-check-circle text-success" style="font-size: 5rem;"></i>
                    
                    <h2 class="mt-4 mb-4">Thank You!</h2>
                    
                    <div class="card bg-light d-inline-block mx-auto mb-4" style="max-width: 400px;">
                        <div class="card-body text-left">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td>Wallet Credit:</td>
                                    <td class="text-right">{{ number_format($amount, 2) }} {{ $currencySymbol }}</td>
                                </tr>
                                <tr>
                                    <td>Tax ({{ $taxRate }}%):</td>
                                    <td class="text-right text-danger">+ {{ number_format($taxAmount, 2) }} {{ $currencySymbol }}</td>
                                </tr>
                                <tr>
                                    <td>Processing Fee ({{ $processingRate }}%):</td>
                                    <td class="text-right text-danger">+ {{ number_format($processingFee, 2) }} {{ $currencySymbol }}</td>
                                </tr>
                                <tr class="font-weight-bold">
                                    <td>Total Charged:</td>
                                    <td class="text-right text-primary">{{ number_format($totalAmount, 2) }} {{ $currencySymbol }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="alert alert-info d-inline-block">
                        <i class="fas fa-info-circle mr-2"></i>
                        Transaction ID: <strong>{{ $transactionId }}</strong>
                    </div>

                    <div class="mt-4">
                        <a href="{{ route('favor-member.dashboard') }}" class="btn btn-primary">
                            <i class="fas fa-home mr-2"></i>
                            Go to Dashboard
                        </a>
                        <a href="{{ route('favor-member.wallet.transactions') }}" class="btn btn-outline-secondary ml-2">
                            <i class="fas fa-history mr-2"></i>
                            View Transactions
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection