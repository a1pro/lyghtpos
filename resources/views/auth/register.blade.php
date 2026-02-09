@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <span>{{ __('Register - Step 1 of 2') }}</span>
                        <span class="badge badge-primary">Step 1</span>
                    </div>
                </div>

                <div class="card-body">
                    <form method="POST" action="{{ route('register') }}">
                        @csrf
                        <input type="hidden" name="step" value="1">

                        <!-- Progress Bar -->
                        <div class="progress mb-4" style="height: 10px;">
                            <div class="progress-bar" role="progressbar" style="width: 50%" aria-valuenow="50" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>

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
                                <input id="business_name" type="text" class="form-control @error('business_name') is-invalid @enderror" name="business_name" value="{{ old('business_name') ?: (session('registration.step1.business_name') ?? '') }}" required autocomplete="business_name">
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
                                <input id="primary_contact_name" type="text" class="form-control @error('primary_contact_name') is-invalid @enderror" name="primary_contact_name" value="{{ old('primary_contact_name') ?: (session('registration.step1.primary_contact_name') ?? '') }}" required autocomplete="name">
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
                                <input id="website" type="url" class="form-control @error('website') is-invalid @enderror" name="website" value="{{ old('website') ?: (session('registration.step1.website') ?? '') }}" required autocomplete="url" placeholder="https://example.com">
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
           <input id="consent_language_authorizing" type="text" class="form-control @error('consent_language_authorizing') is-invalid @enderror" name="consent_language_authorizing" value="{{ old('consent_language_authorizing') }}" required>
           @error('consent_language_authorizing')
               <span class="invalid-feedback" role="alert">
                   <strong>{{ $message }}</strong>
               </span>
           @enderror
       </div>
   </div>

   <div class="form-group row">
       <label for="Communyti_enrollment" class="col-md-4 col-form-label text-md-right">{{ __('Communyti Enrollment') }}</label>
       <div class="col-md-6">
           <input id="communyti_enrollment" type="text" class="form-control @error('communyti_enrollment') is-invalid @enderror" name="communyti_enrollment" value="{{ old('communyti_enrollment') }}" required>
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
           <input id="advocate_contact" type="text" class="form-control @error('advocate_contact') is-invalid @enderror" name="advocate_contact" value="{{ old('advocate_contact') }}" required>
           @error('advocate_contact')
               <span class="invalid-feedback" role="alert">
                   <strong>{{ $message }}</strong>
               </span>
           @enderror
       </div>
   </div>
   <!-- Password Section -->
   <h5 class="text-center mb-3 mt-4">Account Security</h5>

   <div class="form-group row">
       <label for="password" class="col-md-4 col-form-label text-md-right">{{ __('Password') }}</label>
       <div class="col-md-6">
           <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password">
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
           <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password">
       </div>
   </div>
                        <div class="form-group row mb-0 mt-4">
                            <div class="col-md-6 offset-md-4">
                                <button type="submit" class="btn btn-primary btn-block">
                                    {{ __('Next') }} &rarr;
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
@endsection