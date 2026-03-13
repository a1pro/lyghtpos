@extends('favor-member.layouts.main')

@section('content-header')
    Add Funds
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-credit-card mr-2"></i>
                        Complete Payment
                    </h3>
                </div>

                <div class="card-body">
                    <!-- Fee Breakdown Card -->
                    <div class="card bg-light mb-4">
                        <div class="card-header bg-transparent">
                            <h5 class="card-title">Payment Summary</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td>Wallet Credit ({{ number_format($net_amount, 2) }} TT)</td>
                                    <td class="text-right">{{ number_format($net_amount, 2) }} TT</td>
                                </tr>
                                <tr>
                                    <td>Tax ({{ $tax_rate }}%)</td>
                                    <td class="text-right text-danger">+ {{ number_format($tax_amount, 2) }} TT</td>
                                </tr>
                                <tr>
                                    <td>Processing Fee ({{ $processing_rate }}% + ${{ number_format($processing_fixed, 2) }})</td>
                                    <td class="text-right text-danger">+ {{ number_format($processing_total, 2) }} TT</td>
                                </tr>
                                <tr class="font-weight-bold">
                                    <td>Total to be charged:</td>
                                    <td class="text-right text-primary">{{ number_format($total_amount, 2) }} TT</td>
                                </tr>
                                <tr>
                                    <td colspan="2" class="text-muted small">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        {{ number_format($wallet_credit, 2) }} TT will be added to your wallet
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <form action="{{ route('favor-member.wallet.process-payment') }}" method="POST" id="paymentForm">
                        @csrf
                        
                        <!-- Card Number -->
                        <div class="form-group">
                            <label for="card_number">Card Number</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-credit-card"></i></span>
                                </div>
                                <input type="text" 
                                       class="form-control @error('card_number') is-invalid @enderror" 
                                       id="card_number" 
                                       name="card_number" 
                                       placeholder="1234 5678 9012 3456"
                                       maxlength="19"
                                       value="{{ old('card_number') }}"
                                       required>
                            </div>
                            @error('card_number')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="row">
                            <!-- Expiry Month -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="expiry_month">Expiry Month</label>
                                    <select class="form-control @error('expiry_month') is-invalid @enderror" 
                                            id="expiry_month" 
                                            name="expiry_month" 
                                            required>
                                        <option value="">Month</option>
                                        @for($i = 1; $i <= 12; $i++)
                                            <option value="{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}" 
                                                {{ old('expiry_month') == str_pad($i, 2, '0', STR_PAD_LEFT) ? 'selected' : '' }}>
                                                {{ str_pad($i, 2, '0', STR_PAD_LEFT) }}
                                            </option>
                                        @endfor
                                    </select>
                                    @error('expiry_month')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Expiry Year -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="expiry_year">Expiry Year</label>
                                    <select class="form-control @error('expiry_year') is-invalid @enderror" 
                                            id="expiry_year" 
                                            name="expiry_year" 
                                            required>
                                        <option value="">Year</option>
                                        @for($i = date('Y'); $i <= date('Y') + 10; $i++)
                                            <option value="{{ $i }}" {{ old('expiry_year') == $i ? 'selected' : '' }}>
                                                {{ $i }}
                                            </option>
                                        @endfor
                                    </select>
                                    @error('expiry_year')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <!-- CVV -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="cvv">CVV</label>
                                    <input type="text" 
                                           class="form-control @error('cvv') is-invalid @enderror" 
                                           id="cvv" 
                                           name="cvv" 
                                           placeholder="123"
                                           maxlength="4"
                                           value="{{ old('cvv') }}"
                                           required>
                                    @error('cvv')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Billing Information -->
                        <h5 class="mt-4">Billing Information</h5>
                        <hr>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="first_name">First Name</label>
                                    <input type="text" 
                                           class="form-control @error('first_name') is-invalid @enderror" 
                                           id="first_name" 
                                           name="first_name" 
                                           value="{{ old('first_name', auth()->user()->first_name) }}"
                                           required>
                                    @error('first_name')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="last_name">Last Name</label>
                                    <input type="text" 
                                           class="form-control @error('last_name') is-invalid @enderror" 
                                           id="last_name" 
                                           name="last_name" 
                                           value="{{ old('last_name', auth()->user()->last_name) }}"
                                           required>
                                    @error('last_name')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="address">Address</label>
                            <input type="text" 
                                   class="form-control @error('address') is-invalid @enderror" 
                                   id="address" 
                                   name="address" 
                                   value="{{ old('address') }}"
                                   required>
                            @error('address')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="city">City</label>
                                    <input type="text" 
                                           class="form-control @error('city') is-invalid @enderror" 
                                           id="city" 
                                           name="city" 
                                           value="{{ old('city') }}"
                                           required>
                                    @error('city')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="state">State</label>
                                    <input type="text" 
                                           class="form-control @error('state') is-invalid @enderror" 
                                           id="state" 
                                           name="state" 
                                           value="{{ old('state') }}"
                                           required>
                                    @error('state')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="zip">ZIP Code</label>
                                    <input type="text" 
                                           class="form-control @error('zip') is-invalid @enderror" 
                                           id="zip" 
                                           name="zip" 
                                           value="{{ old('zip') }}"
                                           required>
                                    @error('zip')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-6">
                                <a href="{{ route('favor-member.dashboard') }}" class="btn btn-secondary btn-block">
                                    Cancel
                                </a>
                            </div>
                            <div class="col-md-6">
                                <button type="submit" class="btn btn-primary btn-block" id="submitBtn">
                                    <i class="fas fa-lock mr-2"></i>
                                    Pay {{ number_format($total_amount, 2) }} TT
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Format card number
    document.getElementById('card_number').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        let formattedValue = '';
        
        for (let i = 0; i < value.length; i++) {
            if (i > 0 && i % 4 === 0) {
                formattedValue += ' ';
            }
            formattedValue += value[i];
        }
        
        e.target.value = formattedValue;
    });

    // Prevent double submission
    document.getElementById('paymentForm').addEventListener('submit', function(e) {
        const btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
    });
</script>
@endpush