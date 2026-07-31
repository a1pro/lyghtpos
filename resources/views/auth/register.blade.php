@extends('layouts.auth')

@section('content')

<div class="container register-wrapper">
    <div class="row justify-content-center">
        <div class="col-lg-10">

            <div class="card register-card">

                <div class="register-header text-center">
                    <h3>{{ __('Create Your Account') }}</h3>
                    <p class="mb-0 mt-2">Fill in all required information to register</p>
                </div>

                <div class="register-body">

                    <form method="POST" action="{{ route('register') }}" id="registrationForm">
                        @csrf

                        <!-- PERSONAL INFORMATION -->
                        <div class="section-title">
                            Personal Information
                        </div>

                        <div class="row">

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">First Name</label>

                                    <input id="first_name" type="text" class="form-control @error('first_name') is-invalid @enderror" name="first_name" value="{{ old('first_name') }}" required>

                                    @error('first_name')
                                    <span class="invalid-feedback">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Last Name</label>

                                    <input id="last_name" type="text" class="form-control @error('last_name') is-invalid @enderror" name="last_name" value="{{ old('last_name') }}" required>

                                    @error('last_name')
                                    <span class="invalid-feedback">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Email Address</label>

                                    <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required>

                                    @error('email')
                                    <span class="invalid-feedback">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Phone Number</label>

                                    <input id="phone" type="text" class="form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone') }}" required>

                                    @error('phone')
                                    <span class="invalid-feedback">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>

                        </div>

                        <!-- BUSINESS INFORMATION -->
                        <div class="section-title mt-4">
                            Business Information
                        </div>

                        <div class="row">

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Business Name</label>

                                    <input id="business_name" type="text" class="form-control @error('business_name') is-invalid @enderror" name="business_name" value="{{ old('business_name') }}">

                                    @error('business_name')
                                    <span class="invalid-feedback">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Primary Contact Name</label>

                                    <input id="primary_contact_name" type="text" class="form-control @error('primary_contact_name') is-invalid @enderror" name="primary_contact_name" value="{{ old('primary_contact_name') }}">

                                    @error('primary_contact_name')
                                    <span class="invalid-feedback">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Location</label>

                                    <input id="location" type="text" class="form-control @error('location') is-invalid @enderror" name="location" value="{{ old('location') }}" required>

                                    <small class="form-text text-muted">
                                        City/Town name for general location
                                    </small>

                                    @error('location')
                                    <span class="invalid-feedback">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Website</label>

                                    <input id="website" type="url" class="form-control @error('website') is-invalid @enderror" name="website" value="{{ old('website') }}" placeholder="https://example.com">

                                    @error('website')
                                    <span class="invalid-feedback">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>

                        </div>

                        <!-- ADDRESS INFORMATION -->
                        <div class="section-title mt-4">
                            Address Information
                        </div>

                        <div class="row">

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Country</label>

                                    <select id="country_id" name="country_id" class="form-control @error('country_id') is-invalid @enderror" required>

                                        <option value="">Select Country</option>

                                        @foreach($countries as $country)
                                        <option value="{{ $country->id }}" {{ old('country_id')==$country->id ?
                                            'selected' : '' }}>
                                            {{ $country->name }}
                                        </option>
                                        @endforeach

                                    </select>

                                    @error('country_id')
                                    <span class="invalid-feedback">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">State / Province</label>

                                    <select id="state_id" name="state_id" class="form-control @error('state_id') is-invalid @enderror" required disabled>
                                        <option value="">Select Country First</option>
                                    </select>

                                    <div class="address-loading" id="state-loading">
                                        <i class="fa fa-spinner fa-spin"></i>
                                    </div>

                                    @error('state_id')
                                    <span class="invalid-feedback">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">City</label>

                                    <select id="city_id" name="city_id" class="form-control @error('city_id') is-invalid @enderror" required disabled>

                                        <option value="">Select State First</option>
                                    </select>

                                    <div class="address-loading" id="city-loading">
                                        <i class="fa fa-spinner fa-spin"></i>
                                    </div>

                                    @error('city_id')
                                    <span class="invalid-feedback">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-8">
                                <div class="form-group">
                                    <label class="form-label">Street Address</label>

                                    <input id="address" type="text" class="form-control @error('address') is-invalid @enderror" name="address" value="{{ old('address') }}" required>

                                    @error('address')
                                    <span class="invalid-feedback">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Postal Code</label>

                                    <input id="postal_code" type="text" class="form-control @error('postal_code') is-invalid @enderror" name="postal_code" value="{{ old('postal_code') }}" required>

                                    @error('postal_code')
                                    <span class="invalid-feedback">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>

                        </div>

                        <!-- ADDITIONAL INFORMATION -->
                        <div class="section-title mt-4">
                            Additional Information
                        </div>

                        <div class="row">

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Consent Language Authorizing</label>

                                    <input id="consent_language_authorizing" type="text" class="form-control" name="consent_language_authorizing" value="{{ old('consent_language_authorizing') }}">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Communyti Enrollment</label>

                                    <input id="communyti_enrollment" type="text" class="form-control" name="communyti_enrollment" value="{{ old('communyti_enrollment') }}">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Advocate Contact</label>

                                    <input id="advocate_contact" type="text" class="form-control" name="advocate_contact" value="{{ old('advocate_contact') }}">
                                </div>
                            </div>

                        </div>

                        <!-- ACCOUNT TYPE -->
                        <div class="section-title mt-4">
                            Account Type
                        </div>

                        <div class="row">

                            <div class="col-md-6 mb-4">
                                <label class="account-type-card">
                                    <input type="radio" name="account_type" value="TRADE_MERCHANT" checked required>

                                    <div class="card">
                                        <div class="card-body">
                                            <h6>Trade Merchant</h6>
                                            <p class="text-muted mb-0">
                                                I run a business and want to accept
                                                TradeTokens and vouchers at my POS
                                            </p>
                                        </div>
                                    </div>
                                </label>
                            </div>

                            {{-- <div class="col-md-6 mb-4">
                                <label class="account-type-card">

                                    <input type="radio" name="account_type" value="TRADE_MEMBER" {{
                                        old('account_type')=='TRADE_MEMBER' ? 'checked' : '' }}>

                                    <div class="card">
                                        <div class="card-body">
                                            <h6>Trade Member</h6>
                                            <p class="text-muted mb-0">
                                                I want to trade with other members
                                                but do not run a POS
                                            </p>
                                        </div>
                                    </div>
                                </label>
                            </div> --}}

                            {{-- <div class="col-md-6 mb-4">
                                <label class="account-type-card">

                                    <input type="radio" name="account_type" value="ORGANIZATION" {{
                                        old('account_type')=='ORGANIZATION' ? 'checked' : '' }}>

                                    <div class="card">
                                        <div class="card-body">
                                            <h6>Organization</h6>
                                            <p class="text-muted mb-0">
                                                I represent a nonprofit or community organization
                                            </p>
                                        </div>
                                    </div>
                                </label>
                            </div> --}}

                            {{-- <div class="col-md-6 mb-4">
                                <label class="account-type-card">

                                    <input type="radio" name="account_type" value="FAVOR_MEMBER" {{
                                        old('account_type')=='FAVOR_MEMBER' ? 'checked' : '' }}>

                                    <div class="card">
                                        <div class="card-body">
                                            <h6>Favor Member</h6>
                                            <p class="text-muted mb-0">
                                                I am a consumer who wants to shop
                                                using TradeTokens or vouchers
                                            </p>
                                        </div>
                                    </div>
                                </label>
                            </div> --}}

                        </div>
<div class="section-title mt-4">
    Intend POS Type
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <label class="account-type-card">
            <input type="radio" name="intended_pos" value="pax" checked required>  <!-- Only THIS has checked -->

            <div class="card">
                <div class="card-body">
                    <h6>Pax POS</h6>
                    <p class="text-muted mb-0">
                        I want to use PAX as intended POS
                    </p>
                </div>
            </div>
        </label>
    </div>
    
    <div class="col-md-6 mb-4">
        <label class="account-type-card">
            <input type="radio" name="intended_pos" value="clover" required>  <!-- NO checked here -->

            <div class="card">
                <div class="card-body">
                    <h6>Clover POS</h6>
                    <p class="text-muted mb-0">
                        I want to use Clover as intended POS
                    </p>
                </div>
            </div>
        </label>
    </div>
</div>

                        <!-- ACCOUNT SECURITY -->
                        <div class="section-title mt-4">
                            Account Security
                        </div>

                        <div class="row">

                            <div class="col-md-6">
                                <div class="form-group">

                                    <label class="form-label">Password</label>

                                    <div class="password-wrapper">

                                        <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required>

                                        <button type="button" class="password-toggle" data-target="password">
                                            <i class="fa fa-eye"></i>
                                        </button>

                                    </div>

                                    <small class="form-text text-muted">
                                        Minimum 8 characters
                                    </small>

                                    @error('password')
                                    <span class="invalid-feedback">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror

                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">

                                    <label class="form-label">
                                        Confirm Password
                                    </label>

                                    <div class="password-wrapper">

                                        <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required>

                                        <button type="button" class="password-toggle" data-target="password-confirm">
                                            <i class="fa fa-eye"></i>
                                        </button>

                                    </div>

                                </div>
                            </div>

                        </div>

                        <!-- SUBMIT -->
                        <div class="text-center mt-4">

                            <button type="submit" class="btn btn-primary btn-register px-5">
                                Register Account →
                            </button>

                            <div class="login-link">
                                <small class="text-muted">
                                    Already have an account ?
                                    <a href="{{ route('login') }}">
                                        Login Here
                                    </a>
                                </small>
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

        // Country change - Load states
        $('#country_id').on('change', function() {
            const countryId = $(this).val();
            const stateSelect = $('#state_id');
            const citySelect = $('#city_id');

            if (!countryId) {
                stateSelect.html('<option value="">Select Country First</option>');
                stateSelect.prop('disabled', true);
                citySelect.html('<option value="">Select State First</option>');
                citySelect.prop('disabled', true);
                return;
            }

            // Show loading
            $('#state-loading').show();
            stateSelect.prop('disabled', true);
            citySelect.prop('disabled', true);
            citySelect.html('<option value="">Select State First</option>');

            // AJAX call to get states
            $.ajax({
                url: '/get-states/' + countryId
                , type: 'GET'
                , success: function(states) {
                    stateSelect.html('<option value="">Select State</option>');
                    $.each(states, function(key, state) {
                        stateSelect.append('<option value="' + state.id + '">' + state.name + '</option>');
                    });
                    stateSelect.prop('disabled', false);
                    $('#state-loading').hide();
                }
                , error: function() {
                    stateSelect.html('<option value="">Error loading states</option>');
                    $('#state-loading').hide();
                }
            });
        });

        // State change - Load cities
        $('#state_id').on('change', function() {
            const stateId = $(this).val();
            const citySelect = $('#city_id');

            if (!stateId) {
                citySelect.html('<option value="">Select State First</option>');
                citySelect.prop('disabled', true);
                return;
            }

            // Show loading
            $('#city-loading').show();
            citySelect.prop('disabled', true);

            // AJAX call to get cities
            $.ajax({
                url: '/get-cities/' + stateId
                , type: 'GET'
                , success: function(cities) {
                    citySelect.html('<option value="">Select City</option>');
                    $.each(cities, function(key, city) {
                        citySelect.append('<option value="' + city.id + '">' + city.name + '</option>');
                    });
                    citySelect.prop('disabled', false);
                    $('#city-loading').hide();
                }
                , error: function() {
                    citySelect.html('<option value="">Error loading cities</option>');
                    $('#city-loading').hide();
                }
            });
        });

        // jQuery Validation
        $('#registrationForm').validate({
            rules: {
                first_name: {
                    required: true
                    , maxlength: 255
                }
                , last_name: {
                    required: true
                    , maxlength: 255
                }
                , email: {
                    required: true
                    , email: true
                    , maxlength: 255
                    , remote: {
                        url: "{{ route('check.email') }}"
                        , type: "post"
                        , data: {
                            _token: "{{ csrf_token() }}"
                            , email: function() {
                                return $('#email').val();
                            }
                        }
                    }
                }
                , password: {
                    required: true
                    , minlength: 8
                }
                , password_confirmation: {
                    required: true
                    , equalTo: "#password"
                }
                , account_type: {
                    required: true
                },
                // Address validation rules
                country_id: {
                    required: true
                }
                , state_id: {
                    required: true
                }
                , city_id: {
                    required: true
                }
                , address: {
                    required: true
                    , maxlength: 255
                }
                , postal_code: {
                    required: true
                    , maxlength: 20
                }
                , business_name: {
                    required: function() {
                        return $('input[name="account_type"]:checked').val() === 'TRADE_MERCHANT';
                    }
                    , maxlength: 255
                }
                , primary_contact_name: {
                    required: function() {
                        return $('input[name="account_type"]:checked').val() === 'TRADE_MERCHANT';
                    }
                    , maxlength: 255
                }
                , phone: {
                    required: true
                    , digits: true
                    , minlength: 8
                    , maxlength: 14
                }
                , location: {
                    required: true
                    , maxlength: 255
                }
                , website: {
                    url: true
                    , maxlength: 255
                }
                , advocate_contact: {
                    digits: true
                    , minlength: 8
                    , maxlength: 14
                }
            }
            , messages: {
                phone: {
                    required: "Phone number is required"
                    , digits: "Please enter a valid phone number"
                    , minlength: "Please enter a valid phone number (8-14 digits)"
                    , maxlength: "Please enter a valid phone number (8-14 digits)"
                }
                , advocate_contact: {
                    digits: "Please enter a valid phone number"
                    , minlength: "Please enter a valid phone number (8-14 digits)"
                    , maxlength: "Please enter a valid phone number (8-14 digits)"
                }
                , website: {
                    url: "Please enter a valid URL (e.g., https://example.com)"
                }
                , password: {
                    minlength: "Password must be at least 8 characters"
                }
                , address: {
                    required: "Street address is required"
                }
                , country_id: {
                    required: "Please select a country"
                }
                , state_id: {
                    required: "Please select a state"
                }
                , city_id: {
                    required: "Please select a city"
                }
                , postal_code: {
                    required: "Postal code is required"
                }
            }
            , errorElement: 'div'
            , errorClass: 'error-message'
            , highlight: function(element) {
                $(element).addClass('is-invalid');
            }
            , unhighlight: function(element) {
                $(element).removeClass('is-invalid');
            }
            , errorPlacement: function(error, element) {
                if (element.attr('name') === 'account_type') {
                    error.insertAfter('.account-type-card:last');
                } else if (element.parent().hasClass('password-wrapper')) {
                    error.insertAfter(element.parent());
                } else {
                    error.insertAfter(element);
                }
            }
            , submitHandler: function(form) {
                form.submit();
            }
        });

        // Update validation rules when account type changes
        $('input[name="account_type"]').on('change', function() {
            if ($(this).val() === 'TRADE_MERCHANT') {
                $('#business_name').rules('add', {
                    required: true
                    , messages: {
                        required: "Business name is required"
                    }
                });
                $('#primary_contact_name').rules('add', {
                    required: true
                    , messages: {
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
                required: true
                , messages: {
                    required: "Business name is required"
                }
            });
            $('#primary_contact_name').rules('add', {
                required: true
                , messages: {
                    required: "Primary contact name is required"
                }
            });
        }
    });

</script>

@endsection
