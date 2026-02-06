@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <span>{{ __('Register - Step 2 of 2') }}</span>
                        <span class="badge badge-primary">Step 2</span>
                    </div>
                </div>

                <div class="card-body">
                    <form method="POST" action="{{ route('register.final') }}">
                        @csrf

                        <!-- Progress Bar -->
                        <div class="progress mb-4" style="height: 10px;">
                            <div class="progress-bar" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>

                        <!-- Review Step 1 Data -->
                        <div class="alert alert-info mb-4">
                            <h6 class="alert-heading">Review Your Information</h6>
                            <small>
                                <strong>Name:</strong> {{ session('registration.step1.first_name') }} {{ session('registration.step1.last_name') }}<br>
                                <strong>Email:</strong> {{ session('registration.step1.email') }}<br>
                                <strong>Business:</strong> {{ session('registration.step1.business_name') }}<br>
                                <strong>Contact:</strong> {{ session('registration.step1.primary_contact_name') }}<br>
                                <strong>Phone:</strong> {{ session('registration.step1.phone') }}<br>
                                <a href="{{ route('register') }}" class="btn btn-sm btn-outline-info mt-2" style="background-color: #6cb2eb">Edit Information</a>
                            </small>
                        </div>

                     

                        <!-- Representation Information Section -->
                        <h5 class="text-center mb-3 mt-4">Representation Information</h5>

                        <div class="form-group row">
                            <label for="subject" class="col-md-4 col-form-label text-md-right">{{ __('Subject') }}</label>
                            <div class="col-md-6">
                                <input id="subject" type="text" class="form-control @error('subject') is-invalid @enderror" name="subject" value="{{ old('subject') }}" placeholder="Optional">
                                @error('subject')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="purpose" class="col-md-4 col-form-label text-md-right">{{ __('Purpose') }}</label>
                            <div class="col-md-6">
                                <input id="purpose" type="text" class="form-control @error('purpose') is-invalid @enderror" name="purpose" value="{{ old('purpose') }}" placeholder="Optional">
                                @error('purpose')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="futuristic_plan" class="col-md-4 col-form-label text-md-right">{{ __('Futuristic Plan') }}</label>
                            <div class="col-md-6">
                                <input id="futuristic_plan" type="text" class="form-control @error('futuristic_plan') is-invalid @enderror" name="futuristic_plan" value="{{ old('futuristic_plan') }}" placeholder="Optional">
                                @error('futuristic_plan')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="pledge_value" class="col-md-4 col-form-label text-md-right">{{ __('Pledge Value') }}</label>
                            <div class="col-md-6">
                                <input id="pledge_value" type="text" class="form-control @error('pledge_value') is-invalid @enderror" name="pledge_value" value="{{ old('pledge_value') }}" required placeholder="e.g., 1000">
                                @error('pledge_value')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="currency" class="col-md-4 col-form-label text-md-right">{{ __('Currency') }}</label>
                            <div class="col-md-6">
                                <select id="currency" class="form-control @error('currency') is-invalid @enderror" name="currency" required>
                                    <option value="">Select Currency</option>
                                    <option value="USD" {{ old('currency') == 'USD' ? 'selected' : '' }}>USD ($)</option>
                                    <option value="EUR" {{ old('currency') == 'EUR' ? 'selected' : '' }}>EUR (€)</option>
                                    <option value="GBP" {{ old('currency') == 'GBP' ? 'selected' : '' }}>GBP (£)</option>
                                    <option value="INR" {{ old('currency') == 'INR' ? 'selected' : '' }}>INR (₹)</option>
                                    <option value="CAD" {{ old('currency') == 'CAD' ? 'selected' : '' }}>CAD ($)</option>
                                    <option value="AUD" {{ old('currency') == 'AUD' ? 'selected' : '' }}>AUD ($)</option>
                                </select>
                                @error('currency')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        

                        <div class="form-group row mb-0 mt-4">
                            <div class="col-md-6 offset-md-4">
                                <div class="d-flex justify-content-between">
                                    <a href="{{ route('register') }}" class="btn btn-outline-secondary">
                                        &larr; {{ __('Back') }}
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        {{ __('Complete Registration') }}
                                    </button>
                                </div>
                                
                                <div class="text-center mt-3">
                                    <small class="text-muted">By clicking Complete Registration, you agree to our Terms and Conditions</small>
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