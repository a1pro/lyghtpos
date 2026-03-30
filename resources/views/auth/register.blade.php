@extends('layouts.app')
@section('css')

<style>
    .account-type-card {
        width: 100%;
        cursor: pointer;
    }

    .account-type-card input {
        display: none;
    }

    .account-type-card .card {
        border: 2px solid #dee2e6;
        transition: all 0.2s ease-in-out;
    }

    .account-type-card:hover .card {
        border-color: #80bdff;
        box-shadow: 0 0 0 0.1rem rgba(0, 123, 255, .25);
    }

    .account-type-card input:checked+.card {
        border-color: #007bff;
        background-color: #f8fbff;
    }
    
    /* Password wrapper styles */
    .password-wrapper {
        position: relative;
        width: 100%;
    }
    
    .password-wrapper input {
        padding-right: 40px;
        width: 100%;
    }
    
    .password-toggle {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        z-index: 10;
        background: transparent;
        border: none;
        padding: 0;
        margin: 0;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .password-toggle i {
        font-size: 18px;
        color: #6c757d;
        pointer-events: none;
    }
    
    .password-toggle:hover i {
        color: #007bff;
    }
    
    .error-message {
        color: #dc3545;
        font-size: 12px;
        margin-top: 4px;
    }
    
    .is-invalid {
        border-color: #dc3545 !important;
    }
    
    /* Fix for validation messages */
    .invalid-feedback {
        display: block;
    }
    
    /* Ensure proper spacing for password fields */
    .form-group .col-md-6 {
        position: relative;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .password-toggle i {
            font-size: 16px;
        }
        
        .password-wrapper input {
            padding-right: 35px;
        }
    }
</style>
@endsection

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <span>{{ __('Register') }}</span>
                    </div>
                </div>

                <div class="card-body">
                    <form method="POST" action="{{ route('register') }}" id="registrationForm">
                        @csrf
                        <!-- Personal Information Section -->
                        <h5 class="text-center mb-3">Personal Information</h5>

                        <div class="form-group row">
                            <label for="first_name" class="col-md-4 col-form-label text-md-right">{{ __('First Name') }}</label>
                            <div class="col-md-6">
                                <input id="first_name" type="text" class="form-control @error('first_name') is-invalid @enderror" name="first_name" value="{{ old('first_name') ?: (session('registration.step1.first_name') ?? '') }}" required autocomplete="first_name" autofocus>
                                @error('first_name')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="last_name" class="col-md-4 col-form-label text-md-right">{{ __('Last Name') }}</label>
                            <div class="col-md-6">
                                <input id="last_name" type="text" class="form-control @error('last_name') is-invalid @enderror" name="last_name" value="{{ old('last_name') ?: (session('registration.step1.last_name') ?? '') }}" required autocomplete="last_name">
                                @error('last_name')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="email" class="col-md-4 col-form-label text-md-right">{{ __('E-Mail Address') }}</label>
                            <div class="col-md-6">
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') ?: (session('registration.step1.email') ?? '') }}" required autocomplete="email">
                                @error('email')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>

                        <!-- Business Information Section -->
                        <h5 class="text-center mb-3 mt-4">Business Information</h5>

                        <div class="form-group row">
                            <label for="business_name" class="col-md-4 col-form-label text-md-right">{{ __('Business Name') }}</label>
                            <div class="col-md-6">
                                <input id="business_name" type="text" class="form-control @error('business_name') is-invalid @enderror" name="business_name" value="{{ old('business_name') ?: (session('registration.step1.business_name') ?? '') }}" autocomplete="business_name">
                                @error('business_name')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="primary_contact_name" class="col-md-4 col-form-label text-md-right">{{ __('Primary Contact Name') }}</label>
                            <div class="col-md-6">
                                <input id="primary_contact_name" type="text" class="form-control @error('primary_contact_name') is-invalid @enderror" name="primary_contact_name" value="{{ old('primary_contact_name') ?: (session('registration.step1.primary_contact_name') ?? '') }}" autocomplete="name">
                                @error('primary_contact_name')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="phone" class="col-md-4 col-form-label text-md-right">{{ __('Phone') }}</label>
                            <div class="col-md-6">
                                <input id="phone" type="text" class="form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone') ?: (session('registration.step1.phone') ?? '') }}" required autocomplete="tel">
                                @error('phone')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="location" class="col-md-4 col-form-label text-md-right">{{ __('Location') }}</label>
                            <div class="col-md-6">
                                <input id="location" type="text" class="form-control @error('location') is-invalid @enderror" name="location" value="{{ old('location') ?: (session('registration.step1.location') ?? '') }}" required autocomplete="address-level2">
                                @error('location')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="website" class="col-md-4 col-form-label text-md-right">{{ __('Website') }}</label>
                            <div class="col-md-6">
                                <input id="website" type="url" class="form-control @error('website') is-invalid @enderror" name="website" value="{{ old('website') ?: (session('registration.step1.website') ?? '') }}" autocomplete="url" placeholder="https://example.com">
                                @error('website')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>
                        
                        <!-- Additional Information Section -->
                        <h5 class="text-center mb-3">Additional Information</h5>

                        <div class="form-group row">
                            <label for="consent_language_authorizing" class="col-md-4 col-form-label text-md-right">{{ __('Consent Language Authorizing') }}</label>
                            <div class="col-md-6">
                                <input id="consent_language_authorizing" type="text" class="form-control @error('consent_language_authorizing') is-invalid @enderror" name="consent_language_authorizing" value="{{ old('consent_language_authorizing') }}">
                                @error('consent_language_authorizing')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="communyti_enrollment" class="col-md-4 col-form-label text-md-right">{{ __('Communyti Enrollment') }}</label>
                            <div class="col-md-6">
                                <input id="communyti_enrollment" type="text" class="form-control @error('communyti_enrollment') is-invalid @enderror" name="communyti_enrollment" value="{{ old('communyti_enrollment') }}">
                                @error('communyti_enrollment')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="advocate_contact" class="col-md-4 col-form-label text-md-right">{{ __('Advocate Contact') }}</label>
                            <div class="col-md-6">
                                <input id="advocate_contact" type="text" class="form-control @error('advocate_contact') is-invalid @enderror" name="advocate_contact" value="{{ old('advocate_contact') }}" autocomplete="tel">
                                @error('advocate_contact')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>

                        <!-- Account Type Selection -->
                        <h5 class="text-center mb-3">Account Type</h5>

                        <div class="form-group row">
                            <div class="col-md-10 offset-md-1">
                                <div class="row">

                                    <!-- Trade Merchant -->
                                    <div class="col-md-6 mb-3">
                                        <label class="account-type-card">
                                            <input type="radio" name="account_type" value="TRADE_MERCHANT" {{ old('account_type') === 'TRADE_MERCHANT' ? 'checked' : '' }} required>
                                            <div class="card h-100">
                                                <div class="card-body">
                                                    <h6 class="card-title mb-1">Trade Merchant</h6>
                                                    <p class="card-text text-muted small">
                                                        I run a business and want to accept TradeTokens and vouchers at my POS
                                                    </p>
                                                </div>
                                            </div>
                                        </label>
                                    </div>

                                    <!-- Trade Member -->
                                    <div class="col-md-6 mb-3">
                                        <label class="account-type-card">
                                            <input type="radio" name="account_type" value="TRADE_MEMBER" {{ old('account_type') === 'TRADE_MEMBER' ? 'checked' : '' }}>
                                            <div class="card h-100">
                                                <div class="card-body">
                                                    <h6 class="card-title mb-1">Trade Member</h6>
                                                    <p class="card-text text-muted small">
                                                        I want to trade with other members but do not run a POS
                                                    </p>
                                                </div>
                                            </div>
                                        </label>
                                    </div>

                                    <!-- Organization -->
                                    <div class="col-md-6 mb-3">
                                        <label class="account-type-card">
                                            <input type="radio" name="account_type" value="ORGANIZATION" {{ old('account_type') === 'ORGANIZATION' ? 'checked' : '' }}>
                                            <div class="card h-100">
                                                <div class="card-body">
                                                    <h6 class="card-title mb-1">Organization</h6>
                                                    <p class="card-text text-muted small">
                                                        I represent a nonprofit or community organization
                                                    </p>
                                                </div>
                                            </div>
                                        </label>
                                    </div>

                                    <!-- Favor Member -->
                                    <div class="col-md-6 mb-3">
                                        <label class="account-type-card">
                                            <input type="radio" name="account_type" value="FAVOR_MEMBER" {{ old('account_type') === 'FAVOR_MEMBER' ? 'checked' : '' }}>
                                            <div class="card h-100">
                                                <div class="card-body">
                                                    <h6 class="card-title mb-1">Favor Member</h6>
                                                    <p class="card-text text-muted small">
                                                        I am a consumer who wants to shop using TradeTokens or vouchers
                                                    </p>
                                                </div>
                                            </div>
                                        </label>
                                    </div>

                                </div>

                                @error('account_type')
                                <span class="text-danger small d-block mt-2">
                                    {{ $message }}
                                </span>
                                @enderror
                            </div>
                        </div>

                        <!-- Password Section -->
                        <h5 class="text-center mb-3 mt-4">Account Security</h5>

                        <div class="form-group row">
                            <label for="password" class="col-md-4 col-form-label text-md-right">{{ __('Password') }}</label>
                            <div class="col-md-6">
                                <div class="password-wrapper">
                                    <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password">
                                    <button type="button" class="password-toggle" data-target="password">
                                        <i class="fa fa-eye"></i>
                                    </button>
                                </div>
                                <small class="form-text text-muted">Minimum 8 characters</small>
                                @error('password')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="password-confirm" class="col-md-4 col-form-label text-md-right">{{ __('Confirm Password') }}</label>
                            <div class="col-md-6">
                                <div class="password-wrapper">
                                    <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password">
                                    <button type="button" class="password-toggle" data-target="password-confirm">
                                        <i class="fa fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group row mb-0 mt-4">
                            <div class="col-md-6 offset-md-4">
                                <button type="submit" class="btn btn-primary btn-block">
                                    {{ __('Register') }} &rarr;
                                </button>

                                <div class="text-center mt-3">
                                    <small class="text-muted">Already have an account? <a href="{{ route('login') }}">Login here</a></small>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>

<script>
$(document).ready(function() {
    // Password show/hide functionality
    $('.password-toggle').on('click', function(e) {
        e.preventDefault();
        const targetId = $(this).data('target');
        const passwordField = $('#' + targetId);
        const type = passwordField.attr('type') === 'password' ? 'text' : 'password';
        
        passwordField.attr('type', type);
        
        // Toggle eye icon
        const icon = $(this).find('i');
        icon.toggleClass('fa-eye fa-eye-slash');
        
        // Change button color when password is visible
        if (type === 'text') {
            $(this).css('opacity', '0.8');
        } else {
            $(this).css('opacity', '1');
        }
    });

    // jQuery Validation
    $('#registrationForm').validate({
        rules: {
            first_name: {
                required: true,
                maxlength: 255
            },
            last_name: {
                required: true,
                maxlength: 255
            },
            email: {
                required: true,
                email: true,
                maxlength: 255,
                remote: {
                    url: "{{ route('check.email') }}",
                    type: "post",
                    data: {
                        _token: "{{ csrf_token() }}",
                        email: function() {
                            return $('#email').val();
                        }
                    }
                }
            },
            password: {
                required: true,
                minlength: 8
            },
            password_confirmation: {
                required: true,
                equalTo: "#password"
            },
            account_type: {
                required: true
            },
            business_name: {
                required: function() {
                    return $('input[name="account_type"]:checked').val() === 'TRADE_MERCHANT';
                },
                maxlength: 255
            },
            primary_contact_name: {
                required: function() {
                    return $('input[name="account_type"]:checked').val() === 'TRADE_MERCHANT';
                },
                maxlength: 255
            },
            phone: {
                required: true,
                digits: true,
                minlength: 8,
                maxlength: 14
            },
            location: {
                required: true,
                maxlength: 255
            },
            website: {
                url: true,
                maxlength: 255
            },
            advocate_contact: {
                digits: true,
                minlength: 8,
                maxlength: 14
            }
        },
        messages: {
            phone: {
                required: "Phone number is required",
                digits: "Please enter a valid phone number",
                minlength: "Please enter a valid phone number (8-14 digits)",
                maxlength: "Please enter a valid phone number (8-14 digits)"
            },
            advocate_contact: {
                digits: "Please enter a valid phone number",
                minlength: "Please enter a valid phone number (8-14 digits)",
                maxlength: "Please enter a valid phone number (8-14 digits)"
            },
            website: {
                url: "Please enter a valid URL (e.g., https://example.com)"
            },
            password: {
                minlength: "Password must be at least 8 characters"
            }
        },
        errorElement: 'div',
        errorClass: 'error-message',
        highlight: function(element) {
            $(element).addClass('is-invalid');
        },
        unhighlight: function(element) {
            $(element).removeClass('is-invalid');
        },
        errorPlacement: function(error, element) {
            if (element.attr('name') === 'account_type') {
                error.insertAfter('.account-type-card:last');
            } else if (element.parent().hasClass('password-wrapper')) {
                error.insertAfter(element.parent());
            } else {
                error.insertAfter(element);
            }
        },
        submitHandler: function(form) {
            form.submit();
        }
    });

    // Update validation rules when account type changes
    $('input[name="account_type"]').on('change', function() {
        if ($(this).val() === 'TRADE_MERCHANT') {
            $('#business_name').rules('add', {
                required: true,
                messages: {
                    required: "Business name is required"
                }
            });
            $('#primary_contact_name').rules('add', {
                required: true,
                messages: {
                    required: "Primary contact name is required"
                }
            });
        } else {
            $('#business_name').rules('remove', 'required');
            $('#primary_contact_name').rules('remove', 'required');
        }
    });

    // Add real-time phone validation with custom message
    $('#phone, #advocate_contact').on('input', function() {
        const value = $(this).val();
        const phoneRegex = /^\d{8,14}$/;
        
        if (value && !phoneRegex.test(value)) {
            $(this).addClass('is-invalid');
            if ($(this).next('.error-message').length === 0 && !$(this).parent().find('.error-message').length) {
                const errorMsg = $('<div class="error-message">Please enter a valid phone number (8-14 digits)</div>');
                $(this).after(errorMsg);
            }
        } else {
            $(this).removeClass('is-invalid');
            $(this).next('.error-message').remove();
            $(this).parent().find('.error-message').remove();
        }
    });
    
    // Initialize validation for conditional fields based on account type
    if ($('input[name="account_type"]:checked').val() === 'TRADE_MERCHANT') {
        $('#business_name').rules('add', {
            required: true,
            messages: {
                required: "Business name is required"
            }
        });
        $('#primary_contact_name').rules('add', {
            required: true,
            messages: {
                required: "Primary contact name is required"
            }
        });
    }
});
</script>
@endsection