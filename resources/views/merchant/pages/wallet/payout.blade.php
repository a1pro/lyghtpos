{{-- resources/views/merchant/pages/wallet/payout.blade.php --}}
@extends('merchant.layouts.main')

@section('content')
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Request Payout</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('merchant.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('merchant.wallet.index') }}">Wallet</a></li>
                        <li class="breadcrumb-item active">Request Payout</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Payout Details</h3>
                        </div>
                        <form id="payoutForm" action="{{ route('merchant.wallet.request-payout') }}" method="POST">
                            @csrf
                            <div class="card-body">
                                <!-- Current Balance -->
                                <div class="form-group">
                                    <label>Available Balance</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">{{ config('app.currency_symbol', '$') }}</span>
                                        </div>
                                        <input type="text" class="form-control" value="{{ number_format($currentBalance, 2) }}" readonly disabled>
                                    </div>
                                    <small class="text-muted">Maximum payout amount: {{ config('app.currency_symbol', '$') }} {{ number_format(min($currentBalance, 100000), 2) }}</small>
                                </div>

                                <!-- Amount -->
                                <div class="form-group">
                                    <label for="amount">Payout Amount *</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">{{ config('app.currency_symbol', '$') }}</span>
                                        </div>
                                        <input type="number" 
                                               step="0.01" 
                                               name="amount" 
                                               id="amount" 
                                               class="form-control @error('amount') is-invalid @enderror" 
                                               placeholder="0.00"
                                               min="10"
                                               max="{{ min($currentBalance, 100000) }}"
                                               value="{{ old('amount') }}"
                                               required>
                                    </div>
                                    @error('amount')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                    <small class="text-muted" id="fee-preview"></small>
                                </div>

                                <!-- Payment Method -->
                                <div class="form-group">
                                    <label>Payment Method *</label>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-check">
                                                <input type="radio" 
                                                       name="payment_method" 
                                                       id="bank_transfer" 
                                                       value="bank_transfer" 
                                                       class="form-check-input @error('payment_method') is-invalid @enderror"
                                                       {{ old('payment_method') == 'bank_transfer' ? 'checked' : '' }}
                                                       required>
                                                <label class="form-check-label" for="bank_transfer">
                                                    <i class="fas fa-university"></i> Bank Transfer
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check">
                                                <input type="radio" 
                                                       name="payment_method" 
                                                       id="paypal" 
                                                       value="paypal" 
                                                       class="form-check-input"
                                                       {{ old('payment_method') == 'paypal' ? 'checked' : '' }}>
                                                <label class="form-check-label" for="paypal">
                                                    <i class="fab fa-paypal"></i> PayPal
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check">
                                                <input type="radio" 
                                                       name="payment_method" 
                                                       id="stripe" 
                                                       value="stripe" 
                                                       class="form-check-input"
                                                       {{ old('payment_method') == 'stripe' ? 'checked' : '' }}>
                                                <label class="form-check-label" for="stripe">
                                                    <i class="fab fa-stripe"></i> Stripe
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    @error('payment_method')
                                        <span class="invalid-feedback d-block">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Bank Transfer Fields -->
                                <div id="bank-fields" class="payment-fields" style="display: none;">
                                    <div class="card card-outline card-primary mt-3">
                                        <div class="card-header">
                                            <h5 class="card-title">Bank Account Details</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="form-group">
                                                <label for="account_name">Account Name *</label>
                                                <input type="text" 
                                                       name="account_name" 
                                                       id="account_name" 
                                                       class="form-control @error('account_name') is-invalid @enderror" 
                                                       value="{{ old('account_name') }}">
                                                @error('account_name')
                                                    <span class="invalid-feedback">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div class="form-group">
                                                <label for="account_number">Account Number *</label>
                                                <input type="text" 
                                                       name="account_number" 
                                                       id="account_number" 
                                                       class="form-control @error('account_number') is-invalid @enderror" 
                                                       value="{{ old('account_number') }}">
                                                @error('account_number')
                                                    <span class="invalid-feedback">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div class="form-group">
                                                <label for="bank_name">Bank Name *</label>
                                                <input type="text" 
                                                       name="bank_name" 
                                                       id="bank_name" 
                                                       class="form-control @error('bank_name') is-invalid @enderror" 
                                                       value="{{ old('bank_name') }}">
                                                @error('bank_name')
                                                    <span class="invalid-feedback">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div class="form-group">
                                                <label for="routing_number">Routing Number</label>
                                                <input type="text" 
                                                       name="routing_number" 
                                                       id="routing_number" 
                                                       class="form-control" 
                                                       value="{{ old('routing_number') }}">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- PayPal Fields -->
                                <div id="paypal-fields" class="payment-fields" style="display: none;">
                                    <div class="card card-outline card-primary mt-3">
                                        <div class="card-header">
                                            <h5 class="card-title">PayPal Details</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="form-group">
                                                <label for="paypal_email">PayPal Email *</label>
                                                <input type="email" 
                                                       name="paypal_email" 
                                                       id="paypal_email" 
                                                       class="form-control @error('paypal_email') is-invalid @enderror" 
                                                       value="{{ old('paypal_email') }}">
                                                @error('paypal_email')
                                                    <span class="invalid-feedback">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Stripe Fields -->
                                <div id="stripe-fields" class="payment-fields" style="display: none;">
                                    <div class="card card-outline card-primary mt-3">
                                        <div class="card-header">
                                            <h5 class="card-title">Stripe Details</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="form-group">
                                                <label for="stripe_account_id">Stripe Account ID *</label>
                                                <input type="text" 
                                                       name="stripe_account_id" 
                                                       id="stripe_account_id" 
                                                       class="form-control @error('stripe_account_id') is-invalid @enderror" 
                                                       value="{{ old('stripe_account_id') }}"
                                                       placeholder="acct_xxxxxxxxxxxxx">
                                                <small class="text-muted">Format: acct_ followed by letters and numbers</small>
                                                @error('stripe_account_id')
                                                    <span class="invalid-feedback">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Notes -->
                                <div class="form-group">
                                    <label for="notes">Notes (Optional)</label>
                                    <textarea name="notes" 
                                              id="notes" 
                                              class="form-control @error('notes') is-invalid @enderror" 
                                              rows="3">{{ old('notes') }}</textarea>
                                    @error('notes')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary" id="submitBtn">
                                    <i class="fas fa-paper-plane"></i> Submit Payout Request
                                </button>
                                <a href="{{ route('merchant.wallet.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Payout Summary</h3>
                        </div>
                        <div class="card-body">
                            <div id="payout-summary">
                                <div class="info-box bg-light">
                                    <div class="info-box-content">
                                        <span class="info-box-text">Payout Amount</span>
                                        <span class="info-box-number" id="summary-amount">{{ config('app.currency_symbol', '$') }} 0.00</span>
                                    </div>
                                </div>
                                <div class="info-box bg-light">
                                    <div class="info-box-content">
                                        <span class="info-box-text">Processing Fee (2%)</span>
                                        <span class="info-box-number" id="summary-fee">{{ config('app.currency_symbol', '$') }} 0.00</span>
                                        <span class="info-box-text text-muted">Minimum $1</span>
                                    </div>
                                </div>
                                <div class="info-box bg-success">
                                    <div class="info-box-content">
                                        <span class="info-box-text">Net Amount</span>
                                        <span class="info-box-number" id="summary-net">{{ config('app.currency_symbol', '$') }} 0.00</span>
                                    </div>
                                </div>
                            </div>
                            <hr>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>Important:</strong>
                                <ul class="mb-0 mt-2">
                                    <li>Payouts are reviewed before processing</li>
                                    <li>Ensure all payment details are correct</li>
                                    <li>Processing time: 3-5 business days</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Disable all payment method specific fields initially
document.querySelectorAll('#bank-fields input, #paypal-fields input, #stripe-fields input').forEach(input => {
    input.disabled = true;
});

// Show/hide payment fields based on selection and enable/disable fields
document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
    radio.addEventListener('change', function() {
        // Hide all payment fields
        document.querySelectorAll('.payment-fields').forEach(field => {
            field.style.display = 'none';
        });
        
        // Disable all payment method specific fields
        document.querySelectorAll('#bank-fields input, #paypal-fields input, #stripe-fields input').forEach(input => {
            input.disabled = true;
            input.removeAttribute('required');
        });
        
        // Show and enable fields based on selected method
        if (this.value === 'bank_transfer') {
            document.getElementById('bank-fields').style.display = 'block';
            document.querySelectorAll('#bank-fields input').forEach(input => {
                input.disabled = false;
                if (input.id !== 'routing_number') {
                    input.setAttribute('required', 'required');
                }
            });
        } else if (this.value === 'paypal') {
            document.getElementById('paypal-fields').style.display = 'block';
            document.querySelectorAll('#paypal-fields input').forEach(input => {
                input.disabled = false;
                input.setAttribute('required', 'required');
            });
        } else if (this.value === 'stripe') {
            document.getElementById('stripe-fields').style.display = 'block';
            document.querySelectorAll('#stripe-fields input').forEach(input => {
                input.disabled = false;
                input.setAttribute('required', 'required');
            });
        }
    });
});

// Trigger change on page load if a value is selected
const selectedRadio = document.querySelector('input[name="payment_method"]:checked');
if (selectedRadio) {
    selectedRadio.dispatchEvent(new Event('change'));
}

// Calculate and display fee preview
const amountInput = document.getElementById('amount');
const summaryAmount = document.getElementById('summary-amount');
const summaryFee = document.getElementById('summary-fee');
const summaryNet = document.getElementById('summary-net');

function calculateFee() {
    let amount = parseFloat(amountInput.value) || 0;
    let fee = Math.max(amount * 0.02, 1);
    let net = amount - fee;
    
    const currency = '{{ config('app.currency_symbol', '$') }}';
    
    summaryAmount.textContent = `${currency} ${amount.toFixed(2)}`;
    summaryFee.textContent = `${currency} ${fee.toFixed(2)}`;
    summaryNet.textContent = `${currency} ${net.toFixed(2)}`;
}

amountInput.addEventListener('input', calculateFee);
calculateFee();

// Set max amount based on current balance
const maxAmount = {{ min($currentBalance, 100000) }};
amountInput.max = maxAmount;

// Handle form submission
document.getElementById('payoutForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Get selected payment method
    const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
    
    if (!paymentMethod) {
        Swal.fire({
            icon: 'error',
            title: 'Payment Method Required',
            text: 'Please select a payment method'
        });
        return;
    }
    
    // Validate amount
    const amount = parseFloat(document.getElementById('amount').value);
    if (!amount || amount < 10) {
        Swal.fire({
            icon: 'error',
            title: 'Invalid Amount',
            text: 'Please enter a valid amount (minimum $10)'
        });
        return;
    }
    
    if (amount > maxAmount) {
        Swal.fire({
            icon: 'error',
            title: 'Amount Exceeds Balance',
            text: `Maximum payout amount is ${maxAmount}`
        });
        return;
    }
    
    // Create form data with only relevant fields
    const formData = new FormData();
    formData.append('_token', document.querySelector('input[name="_token"]').value);
    formData.append('amount', amount);
    formData.append('payment_method', paymentMethod.value);
    
    // Add notes if present
    const notes = document.getElementById('notes').value;
    if (notes) {
        formData.append('notes', notes);
    }
    
    // Add payment method specific fields
    if (paymentMethod.value === 'bank_transfer') {
        const accountName = document.getElementById('account_name').value;
        const accountNumber = document.getElementById('account_number').value;
        const bankName = document.getElementById('bank_name').value;
        
        if (!accountName || !accountNumber || !bankName) {
            Swal.fire({
                icon: 'error',
                title: 'Missing Bank Details',
                text: 'Please fill in all bank account details'
            });
            return;
        }
        
        formData.append('account_name', accountName);
        formData.append('account_number', accountNumber);
        formData.append('bank_name', bankName);
        
        const routingNumber = document.getElementById('routing_number').value;
        if (routingNumber) {
            formData.append('routing_number', routingNumber);
        }
    } 
    else if (paymentMethod.value === 'paypal') {
        const paypalEmail = document.getElementById('paypal_email').value;
        if (!paypalEmail) {
            Swal.fire({
                icon: 'error',
                title: 'Missing PayPal Email',
                text: 'Please enter your PayPal email address'
            });
            return;
        }
        
        // Validate email format
        const emailRegex = /^[^\s@]+@([^\s@]+\.)+[^\s@]+$/;
        if (!emailRegex.test(paypalEmail)) {
            Swal.fire({
                icon: 'error',
                title: 'Invalid Email',
                text: 'Please enter a valid email address'
            });
            return;
        }
        
        formData.append('paypal_email', paypalEmail);
    } 
    else if (paymentMethod.value === 'stripe') {
        const stripeAccountId = document.getElementById('stripe_account_id').value;
        if (!stripeAccountId) {
            Swal.fire({
                icon: 'error',
                title: 'Missing Stripe Account ID',
                text: 'Please enter your Stripe account ID'
            });
            return;
        }
        
        // Validate Stripe account ID format
        const stripeRegex = /^acct_[A-Za-z0-9]+$/;
        if (!stripeRegex.test(stripeAccountId)) {
            Swal.fire({
                icon: 'error',
                title: 'Invalid Stripe Account ID',
                text: 'Stripe account ID must start with "acct_" followed by letters and numbers'
            });
            return;
        }
        
        formData.append('stripe_account_id', stripeAccountId);
    }
    
    // Show confirmation dialog
    const fee = Math.max(amount * 0.02, 1);
    const net = amount - fee;
    const currency = '{{ config('app.currency_symbol', '$') }}';
    
    Swal.fire({
        title: 'Confirm Payout Request',
        html: `
            <div class="text-left">
                <p><strong>Amount:</strong> ${currency} ${amount.toFixed(2)}</p>
                <p><strong>Processing Fee (2%):</strong> ${currency} ${fee.toFixed(2)}</p>
                <p><strong>Net Amount:</strong> ${currency} ${net.toFixed(2)}</p>
                <p><strong>Payment Method:</strong> ${paymentMethod.value.replace('_', ' ').toUpperCase()}</p>
            </div>
            <div class="mt-3 text-warning">
                <i class="fas fa-exclamation-triangle"></i> This action cannot be undone
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Submit Request',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#dc3545'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading state
            const submitBtn = document.getElementById('submitBtn');
            const originalHtml = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            submitBtn.disabled = true;
            
            // Submit the form via AJAX
            fetch('{{ route('merchant.wallet.request-payout') }}', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Payout Request Submitted!',
                        html: `
                            <div class="text-center">
                                <p><strong>${data.message}</strong></p>
                                <p>Your payout request has been received and is being processed.</p>
                                <p class="text-muted small">You will be notified once it's completed.</p>
                            </div>
                        `,
                        confirmButtonText: 'Go to Wallet',
                        confirmButtonColor: '#007bff'
                    }).then(() => {
                        window.location.href = data.redirect || '{{ route('merchant.wallet.index') }}';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Payout Request Failed',
                        text: data.message || 'Something went wrong. Please try again.'
                    });
                    submitBtn.innerHTML = originalHtml;
                    submitBtn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to submit payout request. Please try again.'
                });
                submitBtn.innerHTML = originalHtml;
                submitBtn.disabled = false;
            });
        }
    });
});
</script>

<style>
    .payment-fields {
        animation: slideDown 0.3s ease;
    }
    
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .info-box {
        margin-bottom: 15px;
    }
    
    .form-check {
        padding: 10px;
        border: 2px solid #e9ecef;
        border-radius: 8px;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .form-check:hover {
        border-color: #007bff;
        background-color: #f8f9fa;
    }
    
    .form-check-input:checked + .form-check-label {
        color: #007bff;
        font-weight: bold;
    }
    
    .form-check-input {
        cursor: pointer;
    }
    
    .form-check-label {
        cursor: pointer;
        margin-left: 5px;
    }
    
    .is-invalid {
        border-color: #dc3545;
    }
    
    .invalid-feedback {
        display: block;
        color: #dc3545;
        font-size: 80%;
    }
</style>
@endsection