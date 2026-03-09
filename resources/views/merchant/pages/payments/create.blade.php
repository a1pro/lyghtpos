@extends('merchant.layouts.main')

@section('title', 'Process Payment')

@push('styles')
<style>
    .payment-form {
        max-width: 600px;
        margin: 0 auto;
    }
    .card-preview {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 15px;
        padding: 20px;
        color: white;
        margin-bottom: 30px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        min-height: 200px;
        position: relative;
        transition: all 0.3s;
    }
    .card-preview .card-type {
        text-align: right;
        font-size: 1.2rem;
        margin-bottom: 30px;
    }
    .card-preview .card-number {
        font-size: 1.4rem;
        letter-spacing: 2px;
        margin-bottom: 20px;
        font-family: 'Courier New', monospace;
    }
    .card-preview .card-details {
        display: flex;
        justify-content: space-between;
        font-size: 0.9rem;
    }
    .card-preview .card-holder {
        text-transform: uppercase;
    }
    .form-section {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
    }
    .form-section h5 {
        color: #495057;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 1px solid #dee2e6;
    }
    .amount-display {
        font-size: 2rem;
        font-weight: bold;
        color: #28a745;
        text-align: center;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 10px;
        margin-bottom: 20px;
        border: 2px solid #28a745;
    }
    .security-badge {
        background: #e9ecef;
        border-radius: 5px;
        padding: 10px;
        text-align: center;
        font-size: 0.9rem;
        color: #6c757d;
    }
    .security-badge i {
        color: #28a745;
    }
    .readonly-field {
        background-color: #f0f0f0 !important;
        cursor: not-allowed;
        font-weight: bold !important;
        color: #495057 !important;
    }
    .fixed-amount-info {
        background-color: #fff3cd;
        border: 1px solid #ffeeba;
        color: #856404;
        padding: 8px 12px;
        border-radius: 5px;
        margin-top: 5px;
        font-size: 0.9rem;
    }
    .fixed-amount-info i {
        color: #856404;
    }
    .input-group-text {
        font-weight: bold;
    }
</style>
@endpush

@section('content')
@php
    // Try multiple ways to get the amount
    $amount = 0;
    
    // 1. Try from URL parameter
    if (request()->has('amount') && request()->get('amount') > 0) {
        $amount = request()->get('amount');
    }
    // 2. Try from old input
    elseif (old('amount') > 0) {
        $amount = old('amount');
    }
    // 3. Try from session
    elseif (session('payment_amount') > 0) {
        $amount = session('payment_amount');
    }
    // 4. Try to get from representation directly
    else {
        try {
            $firstEntry = \App\Models\Representation::where('user_id', auth()->id())
                ->orderBy('created_at', 'asc')
                ->first();
            if ($firstEntry && $firstEntry->pledge_value > 0) {
                $amount = $firstEntry->pledge_value;
            }
        } catch (\Exception $e) {
            // Log error if needed
        }
    }
    
    // Ensure amount is numeric and positive
    $amount = is_numeric($amount) && $amount > 0 ? floatval($amount) : 0;
@endphp

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <!-- Page Header -->
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h2 class="mb-0">Process Payment</h2>
                <a href="{{ route('merchant.dashboard') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>

            <!-- Amount Display -->
            <div class="amount-display" id="amountDisplay">
                ${{ number_format($amount, 2) }}
            </div>

            <!-- Card Preview -->
            <div class="card-preview" id="cardPreview">
                <div class="card-type" id="cardTypeIcon">
                    <i class="fas fa-credit-card"></i>
                </div>
                <div class="card-number" id="displayCardNumber">
                    **** **** **** ****
                </div>
                <div class="card-details">
                    <div class="card-holder" id="displayCardHolder">
                        CARD HOLDER NAME
                    </div>
                    <div class="card-expiry" id="displayCardExpiry">
                        MM/YY
                    </div>
                </div>
            </div>

            <!-- Payment Form -->
            <form action="{{ route('merchant.payments.process') }}" method="POST" id="payment-form" class="payment-form">
                @csrf

                <!-- Payment Amount Section -->
                <div class="form-section">
                    <h5>
                        <i class="fas fa-dollar-sign text-primary"></i>
                        Payment Details
                    </h5>
                    
                    <div class="mb-3">
                        <label for="amount" class="form-label">Amount (USD) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="text" 
                                   class="form-control readonly-field" 
                                   id="amount_display" 
                                   value="{{ number_format($amount, 2) }}" 
                                   readonly
                                   disabled
                                   style="font-weight: bold; font-size: 1.2rem; background-color: #f0f0f0;">
                            <input type="hidden" name="amount" value="{{ $amount }}" id="amount_hidden">
                        </div>
                        @error('amount')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <div class="fixed-amount-info">
                            <i class="fas fa-lock me-2"></i> 
                            This amount is fixed based on your pledge value and cannot be changed
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="order_description" class="form-label">Order Description (Optional)</label>
                        <textarea class="form-control @error('order_description') is-invalid @enderror" 
                                  id="order_description" 
                                  name="order_description" 
                                  rows="2" 
                                  placeholder="Enter description for this payment">{{ old('order_description') }}</textarea>
                        @error('order_description')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Card Information Section -->
                <div class="form-section">
                    <h5>
                        <i class="fas fa-credit-card text-primary"></i>
                        Card Information
                    </h5>

                    <div class="mb-3">
                        <label for="card_number" class="form-label">Card Number <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control @error('card_number') is-invalid @enderror" 
                               id="card_number" 
                               name="card_number" 
                               placeholder="1234 5678 9012 3456" 
                               value="{{ old('card_number') }}" 
                               maxlength="19"
                               required>
                        @error('card_number')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <div class="mt-2">
                            <small class="text-muted me-2">Accepted Cards:</small>
                            <i class="fab fa-cc-visa fa-2x me-1" style="color: #1a1f71;"></i>
                            <i class="fab fa-cc-mastercard fa-2x me-1" style="color: #eb001b;"></i>
                            <i class="fab fa-cc-amex fa-2x me-1" style="color: #2e77bb;"></i>
                            <i class="fab fa-cc-discover fa-2x" style="color: #ff6000;"></i>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="expiry_month" class="form-label">Expiry Month <span class="text-danger">*</span></label>
                            <select class="form-select @error('expiry_month') is-invalid @enderror" 
                                    id="expiry_month" 
                                    name="expiry_month" 
                                    required>
                                <option value="">MM</option>
                                @for($i = 1; $i <= 12; $i++)
                                    <option value="{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}" 
                                        {{ old('expiry_month') == str_pad($i, 2, '0', STR_PAD_LEFT) ? 'selected' : '' }}>
                                        {{ str_pad($i, 2, '0', STR_PAD_LEFT) }}
                                    </option>
                                @endfor
                            </select>
                            @error('expiry_month')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="expiry_year" class="form-label">Expiry Year <span class="text-danger">*</span></label>
                            <select class="form-select @error('expiry_year') is-invalid @enderror" 
                                    id="expiry_year" 
                                    name="expiry_year" 
                                    required>
                                <option value="">YYYY</option>
                                @for($i = date('Y'); $i <= date('Y') + 10; $i++)
                                    <option value="{{ $i }}" {{ old('expiry_year') == $i ? 'selected' : '' }}>
                                        {{ $i }}
                                    </option>
                                @endfor
                            </select>
                            @error('expiry_year')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="cvv" class="form-label">CVV <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('cvv') is-invalid @enderror" 
                                   id="cvv" 
                                   name="cvv" 
                                   placeholder="123" 
                                   maxlength="4" 
                                   value="{{ old('cvv') }}" 
                                   required>
                            @error('cvv')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">
                                <i class="fas fa-question-circle"></i> 
                                3-4 digits on card back
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Billing Information Section -->
                <div class="form-section">
                    <h5>
                        <i class="fas fa-address-card text-primary"></i>
                        Billing Information (Optional)
                    </h5>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="billing_first_name" class="form-label">First Name</label>
                            <input type="text" 
                                   class="form-control @error('billing.first_name') is-invalid @enderror" 
                                   id="billing_first_name" 
                                   name="billing[first_name]" 
                                   value="{{ old('billing.first_name') }}"
                                   placeholder="John">
                            @error('billing.first_name')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="billing_last_name" class="form-label">Last Name</label>
                            <input type="text" 
                                   class="form-control @error('billing.last_name') is-invalid @enderror" 
                                   id="billing_last_name" 
                                   name="billing[last_name]" 
                                   value="{{ old('billing.last_name') }}"
                                   placeholder="Doe">
                            @error('billing.last_name')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="billing_address" class="form-label">Address</label>
                        <input type="text" 
                               class="form-control @error('billing.address') is-invalid @enderror" 
                               id="billing_address" 
                               name="billing[address]" 
                               value="{{ old('billing.address') }}"
                               placeholder="123 Main St">
                        @error('billing.address')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="billing_city" class="form-label">City</label>
                            <input type="text" 
                                   class="form-control @error('billing.city') is-invalid @enderror" 
                                   id="billing_city" 
                                   name="billing[city]" 
                                   value="{{ old('billing.city') }}"
                                   placeholder="New York">
                            @error('billing.city')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="billing_state" class="form-label">State</label>
                            <input type="text" 
                                   class="form-control @error('billing.state') is-invalid @enderror" 
                                   id="billing_state" 
                                   name="billing[state]" 
                                   value="{{ old('billing.state') }}"
                                   placeholder="NY">
                            @error('billing.state')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="billing_zip" class="form-label">ZIP Code</label>
                            <input type="text" 
                                   class="form-control @error('billing.zip') is-invalid @enderror" 
                                   id="billing_zip" 
                                   name="billing[zip]" 
                                   value="{{ old('billing.zip') }}"
                                   placeholder="10001">
                            @error('billing.zip')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="billing_country" class="form-label">Country</label>
                        <input type="text" 
                               class="form-control @error('billing.country') is-invalid @enderror" 
                               id="billing_country" 
                               name="billing[country]" 
                               value="{{ old('billing.country') }}"
                               placeholder="USA">
                        @error('billing.country')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Security Badge -->
                <div class="security-badge mb-4">
                    <i class="fas fa-lock"></i> 
                    Your payment information is secure and encrypted
                </div>

                <!-- Form Actions -->
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                        <i class="fas fa-lock"></i> Pay ${{ number_format($amount, 2) }}
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="window.location='{{ route('merchant.dashboard') }}'">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        // Try to get amount from sessionStorage as backup
        let storedAmount = sessionStorage.getItem('payment_amount');
        if (storedAmount && parseFloat(storedAmount) > 0) {
            let currentAmount = parseFloat($('#amount_hidden').val());
            if (currentAmount === 0) {
                $('#amount_hidden').val(storedAmount);
                $('#amount_display').val(parseFloat(storedAmount).toFixed(2));
                $('#amountDisplay').text('$' + parseFloat(storedAmount).toFixed(2));
                $('#submitBtn').html('<i class="fas fa-lock"></i> Pay $' + parseFloat(storedAmount).toFixed(2));
            }
        }

        // Format card number with spaces and update preview
        $('#card_number').on('input', function() {
            let value = $(this).val().replace(/\s/g, '').replace(/\D/g, '');
            let formatted = '';
            for (let i = 0; i < value.length; i++) {
                if (i > 0 && i % 4 === 0) {
                    formatted += ' ';
                }
                formatted += value[i];
            }
            $(this).val(formatted);
            
            // Update card preview
            let displayNumber = value;
            if (displayNumber.length > 0) {
                let lastFour = displayNumber.slice(-4);
                displayNumber = '**** **** **** ' + lastFour;
            } else {
                displayNumber = '**** **** **** ****';
            }
            $('#displayCardNumber').text(displayNumber);
            
            // Detect card type
            if (value.length > 0) {
                let firstDigit = value.charAt(0);
                let icon = '<i class="fas fa-credit-card"></i>';
                if (firstDigit === '4') {
                    icon = '<i class="fab fa-cc-visa" style="color: #1a1f71;"></i>';
                } else if (firstDigit === '5') {
                    icon = '<i class="fab fa-cc-mastercard" style="color: #eb001b;"></i>';
                } else if (firstDigit === '3') {
                    icon = '<i class="fab fa-cc-amex" style="color: #2e77bb;"></i>';
                } else if (firstDigit === '6') {
                    icon = '<i class="fab fa-cc-discover" style="color: #ff6000;"></i>';
                }
                $('#cardTypeIcon').html(icon);
            }
        });

        // Update expiry in card preview
        $('#expiry_month, #expiry_year').on('change', function() {
            let month = $('#expiry_month').val() || 'MM';
            let year = $('#expiry_year').val() ? $('#expiry_year').val().slice(-2) : 'YY';
            $('#displayCardExpiry').text(month + '/' + year);
        });

        // Update card holder name
        $('#billing_first_name, #billing_last_name').on('input', function() {
            let firstName = $('#billing_first_name').val() || '';
            let lastName = $('#billing_last_name').val() || '';
            let fullName = (firstName + ' ' + lastName).trim().toUpperCase() || 'CARD HOLDER NAME';
            $('#displayCardHolder').text(fullName);
        });

        // Prevent form submission on enter key
        $('#payment-form').on('keyup keypress', function(e) {
            var keyCode = e.keyCode || e.which;
            if (keyCode === 13) { 
                e.preventDefault();
                return false;
            }
        });

        // Confirm before submitting
        $('#payment-form').on('submit', function(e) {
            let amount = $('#amount_hidden').val();
            if (!confirm(`Are you sure you want to process a payment of $${parseFloat(amount).toFixed(2)}?`)) {
                e.preventDefault();
                return false;
            }
            
            // Disable submit button
            $('#submitBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
            return true;
        });

        // CVV validation
        $('#cvv').on('input', function() {
            $(this).val($(this).val().replace(/\D/g, ''));
        });
    });
</script>
@endsection