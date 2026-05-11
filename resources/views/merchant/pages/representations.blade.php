@extends('merchant.layouts.main-single-page')

@section('content')

<div class="container py-2 ak-representaion">
    <div class="row justify-content-center">
        <div class="col-lg-9 col-md-11">

            <div class="card representation-card">

                <!-- Header -->
                <div class="representation-header text-center">

                    <div class="d-flex justify-content-center">
                        <div class="icon-circle">
                            <i class="fas fa-file-signature"></i>
                        </div>
                    </div>

                    <h3>Representation Form</h3>
                    <p>
                        Complete your representation details to participate in the community network.
                    </p>
                </div>

                <!-- Body -->
                <div class="representation-body">

                    <form method="POST" action="{{ route('merchant.representation.submit') }}">
                        @csrf

                        <input type="hidden" id="user_id" name="user_id" value="{{ auth()->user()->id }}" />

                        <!-- Section -->
                        <div class="section-title">
                            Representation Information
                        </div>

                        <div class="row">

                            <!-- Community -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="community" class="form-label">
                                        Community <span class="required">*</span>
                                    </label>

                                    <select id="community" class="form-select @error('community') is-invalid @enderror" name="community" required>

                                        <option value="">Select Community</option>

                                        <option value="community1" {{ old('community') == 'community1' ? 'selected' : '' }}>
                                            Community 1
                                        </option>

                                        <option value="community2" {{ old('community') == 'community2' ? 'selected' : '' }}>
                                            Community 2
                                        </option>

                                        <option value="community3" {{ old('community') == 'community3' ? 'selected' : '' }}>
                                            Community 3
                                        </option>
                                    </select>

                                    @error('community')
                                    <span class="invalid-feedback">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Role -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="role" class="form-label">
                                        Operating Role <span class="required">*</span>
                                    </label>

                                    <select id="role" class="form-select @error('role') is-invalid @enderror" name="role" required>

                                        <option value="">Select Role</option>

                                        <option value="merchant" {{ old('role') == 'merchant' ? 'selected' : '' }}>
                                            Merchant
                                        </option>

                                        <option value="advocate" {{ old('role') == 'advocate' ? 'selected' : '' }}>
                                            Advocate
                                        </option>

                                        <option value="affiliate" {{ old('role') == 'affiliate' ? 'selected' : '' }}>
                                            Affiliate
                                        </option>

                                        <option value="supplier" {{ old('role') == 'supplier' ? 'selected' : '' }}>
                                            Supplier
                                        </option>

                                        <option value="distributor" {{ old('role') == 'distributor' ? 'selected' : '' }}>
                                            Distributor
                                        </option>
                                    </select>

                                    @error('role')
                                    <span class="invalid-feedback">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Currency -->
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="accepted_currencies" class="form-label">
                                        Accepted Currencies/Tokens
                                        <span class="required">*</span>
                                    </label>

                                    <select id="accepted_currencies" class="form-select multi-select @error('accepted_currencies') is-invalid @enderror" name="accepted_currencies[]" multiple required>

                                        <option value="USD" {{ is_array(old('accepted_currencies')) && in_array('USD', old('accepted_currencies')) ? 'selected' : '' }}>
                                            USD ($)
                                        </option>

                                        <option value="EUR" {{ is_array(old('accepted_currencies')) && in_array('EUR', old('accepted_currencies')) ? 'selected' : '' }}>
                                            EUR (€)
                                        </option>

                                        <option value="GBP" {{ is_array(old('accepted_currencies')) && in_array('GBP', old('accepted_currencies')) ? 'selected' : '' }}>
                                            GBP (£)
                                        </option>

                                        <option value="TOKEN1" {{ is_array(old('accepted_currencies')) && in_array('TOKEN1', old('accepted_currencies')) ? 'selected' : '' }}>
                                            Token 1
                                        </option>

                                        <option value="TOKEN2" {{ is_array(old('accepted_currencies')) && in_array('TOKEN2', old('accepted_currencies')) ? 'selected' : '' }}>
                                            Token 2
                                        </option>

                                        <option value="TOKEN3" {{ is_array(old('accepted_currencies')) && in_array('TOKEN3', old('accepted_currencies')) ? 'selected' : '' }}>
                                            Token 3
                                        </option>
                                    </select>

                                    <small class="text-muted">
                                        Hold Ctrl (Windows) or Cmd (Mac) to select multiple
                                    </small>

                                    @error('accepted_currencies')
                                    <span class="invalid-feedback">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Checkboxes -->
                            <div class="col-md-6">
                                <div class="custom-check">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="participate_vouchers" id="participate_vouchers" value="1" {{ old('participate_vouchers') ? 'checked' : '' }}>

                                        <label class="form-check-label" for="participate_vouchers">
                                            Participate in Vouchers
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 mt-3 mt-md-0">
                                <div class="custom-check">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="allow_barter" id="allow_barter" value="1" {{ old('allow_barter') ? 'checked' : '' }}>

                                        <label class="form-check-label" for="allow_barter">
                                            Allow Barter/Token Settlement
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Pledge -->
                            <div class="col-12 mt-4">
                                <div class="form-group">
                                    <label for="pledge_description" class="form-label">
                                        Pledge Commitment
                                    </label>

                                    <textarea id="pledge_description" class="form-control @error('pledge_description') is-invalid @enderror" name="pledge_description" rows="4" placeholder="Describe your commitment, contribution, or participation terms...">{{ old('pledge_description') }}</textarea>

                                    @error('pledge_description')
                                    <span class="invalid-feedback">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Pledge Value -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="pledge_value" class="form-label">
                                        Pledge Value
                                    </label>

                                    <input id="pledge_value" type="text" class="form-control @error('pledge_value') is-invalid @enderror" name="pledge_value" value="{{ old('pledge_value') }}" required placeholder="e.g. 1000">

                                    @error('pledge_value')
                                    <span class="invalid-feedback">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>

                        </div>

                        <!-- Submit -->
                        <div class="text-center mt-4">

                            <button type="submit" class="btn submit-btn px-5">
                                <i class="fas fa-paper-plane me-2"></i>
                                Submit Representation
                            </button>

                            <div class="bottom-note mt-3">
                                By clicking submit, you agree to our
                                Terms & Conditions and Community Policies.
                            </div>
                        </div>

                    </form>

                </div>
            </div>

        </div>
    </div>
</div>

@endsection
