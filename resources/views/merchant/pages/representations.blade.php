@extends('merchant.layouts.main-single-page')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <span>{{ __('Fill Representation Form') }}</span>
                    </div>
                </div>

                <div class="card-body">
                    <form method="POST" action="{{ route('merchant.representation.submit') }}">
                        @csrf
                        <input type="hidden" id="user_id" name="user_id" value="{{ auth()->user()->id }}" />
                        <!-- Representation Information Section -->
                        <h5 class="text-center mb-3 mt-4">Representation Information</h5>

                        <!-- Community Selection -->
                        <div class="form-group row">
                            <label for="community" class="col-md-4 col-form-label text-md-right">{{ __('Community') }} <span class="text-danger">*</span></label>
                            <div class="col-md-6">
                                <select id="community" class="form-control @error('community') is-invalid @enderror" name="community" required>
                                    <option value="">Select Community</option>
                                    <option value="community1" {{ old('community') == 'community1' ? 'selected' : '' }}>Community 1</option>
                                    <option value="community2" {{ old('community') == 'community2' ? 'selected' : '' }}>Community 2</option>
                                    <option value="community3" {{ old('community') == 'community3' ? 'selected' : '' }}>Community 3</option>
                                </select>
                                @error('community')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>

                        <!-- Role Selection -->
                        <div class="form-group row">
                            <label for="role" class="col-md-4 col-form-label text-md-right">{{ __('Operating Role') }} <span class="text-danger">*</span></label>
                            <div class="col-md-6">
                                <select id="role" class="form-control @error('role') is-invalid @enderror" name="role" required>
                                    <option value="">Select Role</option>
                                    <option value="merchant" {{ old('role') == 'merchant' ? 'selected' : '' }}>Merchant</option>
                                    <option value="advocate" {{ old('role') == 'advocate' ? 'selected' : '' }}>Advocate</option>
                                    <option value="affiliate" {{ old('role') == 'affiliate' ? 'selected' : '' }}>Affiliate</option>
                                    <option value="supplier" {{ old('role') == 'supplier' ? 'selected' : '' }}>Supplier</option>
                                    <option value="distributor" {{ old('role') == 'distributor' ? 'selected' : '' }}>Distributor</option>
                                </select>
                                @error('role')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>

                        <!-- Accepted Currencies/Tokens -->
                        <div class="form-group row">
                            <label for="accepted_currencies" class="col-md-4 col-form-label text-md-right">{{ __('Accepted Currencies/Tokens') }} <span class="text-danger">*</span></label>
                            <div class="col-md-6">
                                <select id="accepted_currencies" class="form-control @error('accepted_currencies') is-invalid @enderror" name="accepted_currencies[]" multiple required size="4">
                                    <option value="USD" {{ is_array(old('accepted_currencies')) && in_array('USD', old('accepted_currencies')) ? 'selected' : '' }}>USD ($)</option>
                                    <option value="EUR" {{ is_array(old('accepted_currencies')) && in_array('EUR', old('accepted_currencies')) ? 'selected' : '' }}>EUR (€)</option>
                                    <option value="GBP" {{ is_array(old('accepted_currencies')) && in_array('GBP', old('accepted_currencies')) ? 'selected' : '' }}>GBP (£)</option>
                                    <option value="TOKEN1" {{ is_array(old('accepted_currencies')) && in_array('TOKEN1', old('accepted_currencies')) ? 'selected' : '' }}>Token 1</option>
                                    <option value="TOKEN2" {{ is_array(old('accepted_currencies')) && in_array('TOKEN2', old('accepted_currencies')) ? 'selected' : '' }}>Token 2</option>
                                    <option value="TOKEN3" {{ is_array(old('accepted_currencies')) && in_array('TOKEN3', old('accepted_currencies')) ? 'selected' : '' }}>Token 3</option>
                                </select>
                                <small class="text-muted">Hold Ctrl/Cmd to select multiple</small>
                                @error('accepted_currencies')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>

                        <!-- Voucher Participation -->
                        <div class="form-group row">
                            <label class="col-md-4 col-form-label text-md-right">{{ __('Voucher Participation') }}</label>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input @error('participate_vouchers') is-invalid @enderror" type="checkbox" name="participate_vouchers" id="participate_vouchers" value="1" {{ old('participate_vouchers') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="participate_vouchers">
                                        {{ __('I want to participate in vouchers') }}
                                    </label>
                                    @error('participate_vouchers')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Barter/Token Settlement -->
                        <div class="form-group row">
                            <label class="col-md-4 col-form-label text-md-right">{{ __('Barter/Token Settlement') }}</label>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input @error('allow_barter') is-invalid @enderror" type="checkbox" name="allow_barter" id="allow_barter" value="1" {{ old('allow_barter') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="allow_barter">
                                        {{ __('Allow barter or token settlement') }}
                                    </label>
                                    @error('allow_barter')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Pledge Description/Commitment -->
                        <div class="form-group row">
                            <label for="pledge_description" class="col-md-4 col-form-label text-md-right">{{ __('Pledge Commitment') }}</label>
                            <div class="col-md-6">
                                <textarea id="pledge_description" class="form-control @error('pledge_description') is-invalid @enderror" name="pledge_description" rows="3" placeholder="Describe your participation commitment or pledge terms...">{{ old('pledge_description') }}</textarea>
                                @error('pledge_description')
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

                        <div class="form-group row mb-0 mt-4">
                            <div class="col-md-6 offset-md-4">
                                <button type="submit" class="btn btn-primary btn-block">
                                    {{ __('Submit') }} &rarr;
                                </button>

                                <div class="text-center mt-3">
                                    <small class="text-muted">By clicking Submit, you agree to our Terms and Conditions</small>
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
