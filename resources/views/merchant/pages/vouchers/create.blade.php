{{-- resources/views/merchant/pages/vouchers/create.blade.php --}}
@extends('merchant.layouts.main')

@section('title', __('Generate Voucher in Cyclos'))
@section('content-header', __('Generate Voucher in Cyclos'))

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Generate New Voucher in Cyclos</h3>
        </div>
        <div class="card-body">
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">×</button>
                    {{ session('error') }}
                </div>
            @endif

            <form action="{{ route('merchant.vouchers.generate') }}" method="POST">
                @csrf

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="count">Number of Vouchers *</label>
                            <input type="number" 
                                   name="count" 
                                   id="count" 
                                   class="form-control @error('count') is-invalid @enderror" 
                                   value="{{ old('count', 1) }}" 
                                   min="1" 
                                   max="100" 
                                   required>
                            <small class="text-muted">How many vouchers to generate (max 100)</small>
                            @error('count')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="amount">Amount per Voucher *</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">$</span>
                                </div>
                                <input type="number" 
                                       step="0.01" 
                                       name="amount" 
                                       id="amount" 
                                       class="form-control @error('amount') is-invalid @enderror" 
                                       value="{{ old('amount') }}" 
                                       min="1" 
                                       required>
                            </div>
                            @error('amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            Vouchers will be generated in Cyclos for user: 
                            <strong>{{ auth()->user()->cyclos_username ?? 'Not linked to Cyclos' }}</strong>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-ticket-alt"></i> Generate in Cyclos
                    </button>
                    <a href="{{ route('merchant.vouchers.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection