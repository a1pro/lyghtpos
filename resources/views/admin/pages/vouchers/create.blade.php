@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Create Vouchers for Merchant</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.vouchers.index') }}" class="btn btn-default btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                            {{ session('error') }}
                        </div>
                    @endif

                    <form action="{{ route('admin.vouchers.generate') }}" method="POST">
                        @csrf
                        
                        <div class="form-group">
                            <label for="merchant_id">Select Merchant <span class="text-danger">*</span></label>
                            <select name="merchant_id" id="merchant_id" class="form-control @error('merchant_id') is-invalid @enderror" required>
                                <option value="">-- Select Merchant --</option>
                                @foreach($merchants as $merchant)
                                    <option value="{{ $merchant->id }}" {{ old('merchant_id') == $merchant->id ? 'selected' : '' }}>
                                        {{ $merchant->first_name }} {{ $merchant->last_name }} ({{ $merchant->email }}) - {{ $merchant->cyclos_username }}
                                    </option>
                                @endforeach
                            </select>
                            @error('merchant_id')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                            @if($merchants->isEmpty())
                                <small class="text-danger">No merchants found with Cyclos accounts.</small>
                            @endif
                        </div>

                        <div class="form-group">
                            <label for="count">Number of Vouchers <span class="text-danger">*</span></label>
                            <input type="number" 
                                   name="count" 
                                   id="count" 
                                   class="form-control @error('count') is-invalid @enderror" 
                                   value="{{ old('count', 1) }}" 
                                   min="1" 
                                   max="100" 
                                   required>
                            <small class="form-text text-muted">Maximum 100 vouchers at a time</small>
                            @error('count')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="amount">Amount per Voucher <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">$</span>
                                </div>
                                <input type="number" 
                                       name="amount" 
                                       id="amount" 
                                       class="form-control @error('amount') is-invalid @enderror" 
                                       value="{{ old('amount') }}" 
                                       step="0.01" 
                                       min="1" 
                                       max="10000" 
                                       required>
                            </div>
                            @error('amount')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-ticket-alt"></i> Generate Vouchers
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection