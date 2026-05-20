@extends('merchant.layouts.main')

@section('title', 'Merchant Profile')
@section('content-header', 'My Profile')

@section('css')
<style>
    .profile-avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #fff;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        margin-bottom: 1rem;
    }

    .info-card {
        background: white;
        border-radius: 10px;
        padding: 0;
        margin-bottom: 20px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    }

    .info-card-header {
        padding: 15px 20px;
        border-bottom: 1px solid #e9ecef;
        background: #f8f9fa;
        border-radius: 10px 10px 0 0;
    }

    .info-card-header h5 {
        margin: 0;
        font-weight: 600;
        color: #333;
    }

    .info-card-body {
        padding: 20px;
    }

    .info-row {
        display: flex;
        padding: 12px 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .info-row:last-child {
        border-bottom: none;
    }

    .info-label {
        width: 140px;
        font-weight: 600;
        color: #495057;
        font-size: 14px;
    }

    .info-value {
        flex: 1;
        color: #212529;
        font-size: 14px;
    }

    .badge-subscribed {
        background-color: #28a745;
        color: white;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .badge-not-subscribed {
        background-color: #dc3545;
        color: white;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .address-card {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        margin-top: 10px;
    }

    .address-line {
        margin-bottom: 8px;
        display: flex;
        align-items: flex-start;
    }

    .address-line i {
        width: 25px;
        margin-top: 3px;
        color: #6c757d;
    }

    .address-line span {
        flex: 1;
        color: #495057;
    }

    .default-badge {
        background-color: #28a745;
        color: white;
        padding: 3px 10px;
        border-radius: 15px;
        font-size: 11px;
        margin-left: 10px;
    }

    .address-loading {
        display: none;
        font-size: 12px;
        color: #007bff;
        margin-top: 5px;
    }

    .form-group {
        margin-bottom: 1rem;
    }

    select.form-control:disabled {
        background-color: #e9ecef;
        cursor: not-allowed;
    }

    .modal-header {
        background: #f8f9fa;
        border-bottom: 2px solid #e9ecef;
    }

    .modal-footer {
        background: #f8f9fa;
        border-top: 1px solid #e9ecef;
    }

    hr {
        margin-top: 1rem;
        margin-bottom: 1rem;
        border: 0;
        border-top: 1px solid rgba(0, 0, 0, 0.1);
    }

     /* Payment Checkbox Group Styling */
     .payment-checkbox-group {
     display: flex;
     flex-direction: column;
     gap: 0.75rem;
     padding: 0.5rem 0;
     }

     .payment-checkbox-item {
     display: flex;
     align-items: center;
     cursor: pointer;
     margin: 0;
     padding: 0.5rem 0.75rem;
     border: 1px solid #e9ecef;
     border-radius: 0.375rem;
     transition: all 0.2s ease;
     background-color: #ffffff;
     }

     .payment-checkbox-item:hover {
     background-color: #f8f9fa;
     border-color: #007bff;
     transform: translateX(5px);
     }

     .payment-checkbox-item input[type="checkbox"] {
     margin-right: 0.75rem;
     width: 1.1rem;
     height: 1.1rem;
     cursor: pointer;
     accent-color: #007bff;
     }

     .payment-checkbox-label {
     flex: 1;
     cursor: pointer;
     font-weight: 500;
     color: #495057;
     display: flex;
     align-items: center;
     gap: 0.5rem;
     }

     .payment-checkbox-label i {
     width: 25px;
     color: #6c757d;
     font-size: 1.1rem;
     }

     .payment-checkbox-item input[type="checkbox"]:checked + .payment-checkbox-label {
     color: #212529;
     }

     .payment-checkbox-item input[type="checkbox"]:checked + .payment-checkbox-label i {
     color: #212529;
     }

     .payment-checkbox-item:has(input:checked) {
     background-color: #f8fbff;
     border-color: #212529;
     }

     /* Input group styling */
     .input-group .input-group-text {
     background-color: #e9ecef;
     border-color: #ced4da;
     }

     /* Form control focus state */
     .form-control:focus {
     border-color: #80bdff;
     box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
     }

     /* Invalid feedback */
     .invalid-feedback {
     display: block;
     }

     /* Responsive */
     @media (max-width: 576px) {
     .payment-checkbox-item {
     padding: 0.4rem 0.6rem;
     }

     .payment-checkbox-label {
     font-size: 0.9rem;
     }

     .payment-checkbox-label i {
     font-size: 1rem;
     }
     }

</style>
@endsection

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="row">

                <div class="col-md-4">
                    <div class="info-card">
                        <div class="info-card-header">
                            <h5><i class="fas fa-user-circle"></i> Profile Information</h5>
                        </div>

                        <div class="info-card-body text-center">
                            <img src="{{ $user->getAvatar() }}" alt="{{ $user->getFullname() }}" class="profile-avatar">

                            <h4 class="mb-1">{{ $user->getFullname() }}</h4>

                            <p class="text-muted mb-2">
                                <i class="fas fa-envelope"></i> {{ $user->email }}
                            </p>

                            <p class="text-muted mb-2">
                                Merchant ID: {{ $merchant->merchant_id ?? '' }}
                            </p>

                            <div class="mb-3">
                                @if($user->subscribed)
                                    <span class="badge-subscribed">
                                        <i class="fas fa-check-circle"></i> Subscribed
                                    </span>
                                @else
                                    <span class="badge-not-subscribed">
                                        <i class="fas fa-times-circle"></i> Not Subscribed
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="info-card">
                        <div class="info-card-header">
                            <h5><i class="fas fa-address-card"></i> Personal Details</h5>
                        </div>

                        <div class="info-card-body">
                            <div class="info-row">
                                <div class="info-label">Full Name:</div>
                                <div class="info-value">{{ $user->getFullname() }}</div>
                            </div>

                            <div class="info-row">
                                <div class="info-label">Email Address:</div>
                                <div class="info-value">{{ $user->email }}</div>
                            </div>

                            <div class="info-row">
                                <div class="info-label">Phone Number:</div>
                                <div class="info-value">{{ $user->phone ?? 'Not provided' }}</div>
                            </div>

                            <button type="button" class="btn btn-primary btn-sm mt-3" data-toggle="modal" data-target="#editProfileModal">
                                <i class="fas fa-edit"></i> Edit Profile
                            </button>
                        </div>
                    </div>

                    <div class="info-card">
                        <div class="info-card-header">
                            <h5><i class="fas fa-map-marker-alt"></i> Address Information</h5>
                        </div>

                        <div class="info-card-body">
                            @if($address && $address->address)
                                <div class="address-card">
                                    <div class="address-line">
                                        <i class="fas fa-location-dot"></i>
                                        <span><strong>Address:</strong> {{ $address->address }}</span>
                                    </div>

                                    <div class="address-line">
                                        <i class="fas fa-city"></i>
                                        <span><strong>City:</strong> {{ $address->city_name ?? 'Not provided' }}</span>
                                    </div>

                                    <div class="address-line">
                                        <i class="fas fa-building"></i>
                                        <span><strong>State/Province:</strong> {{ $address->state_name ?? 'Not provided' }}</span>
                                    </div>

                                    <div class="address-line">
                                        <i class="fas fa-mail-bulk"></i>
                                        <span><strong>Postal Code:</strong> {{ $address->postal_code ?? 'Not provided' }}</span>
                                    </div>

                                    <div class="address-line">
                                        <i class="fas fa-globe"></i>
                                        <span><strong>Country:</strong> {{ $address->country_name ?? 'Not provided' }}</span>
                                    </div>

                                    @if(isset($address->is_active) && $address->is_active)
                                        <div class="mt-2">
                                            <span class="default-badge">
                                                <i class="fas fa-check-circle"></i> Default Address
                                            </span>
                                        </div>
                                    @endif
                                </div>

                                <button type="button" class="btn btn-primary btn-sm mt-3" data-toggle="modal" data-target="#editAddressModal">
                                    <i class="fas fa-edit"></i> Edit Address
                                </button>
                            @else
                                <div class="text-center text-muted py-3">
                                    <i class="fas fa-map-marker-alt fa-2x mb-2 d-block"></i>
                                    <p>No address information available.</p>
                                    <small>Please contact support to add an address.</small>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="info-card">
                        <div class="info-card-header">
                            <h5><i class="fas fa-gear"></i> Settings</h5>
                        </div>

                        <div class="info-card-body">
                            <div class="info-row">
                                <div class="info-label">VAT Tax (%):</div>
                                <div class="info-value">{{ $merchant->vat_tax_percentage ?? 10 }}%</div>
                            </div>

                            <div class="info-row">
                                <div class="info-label">POS Payment Types:</div>
                                <div class="info-value">
                                    @php
                                    $paymentTypes = $merchant->pos_payment_types ?? ['card', 'tokens', 'hybrid'];
                                    if (is_string($paymentTypes)) {
                                    $paymentTypes = json_decode($paymentTypes, true);
                                    if (json_last_error() !== JSON_ERROR_NONE) {
                                    $paymentTypes = explode(',', $paymentTypes);
                                    }
                                    }
                                    @endphp

                                    @if(is_array($paymentTypes) && count($paymentTypes) > 0)
                                    @foreach($paymentTypes as $type)
                                    <span class="payment-type-badge">
                                        @if($type == 'card')
                                        <i class="fas fa-credit-card"></i> Card
                                        @elseif($type == 'tokens')
                                        <i class="fas fa-coins"></i> Tokens
                                        @elseif($type == 'hybrid')
                                        <i class="fas fa-exchange-alt"></i> Hybrid
                                        @elseif($type == 'pos_mobile')
                                        <i class="fas fa-mobile-alt"></i> Lyght App
                                        @elseif($type == 'cash')
                                        <i class="fas fa-money-bill"></i> Cash
                                        @else
                                        {{ ucfirst($type) }}
                                        @endif
                                    </span>
                                    @endforeach
                                    @else
                                    All Payment Types
                                    @endif
                                </div>
                            </div>

                            <button type="button" class="btn btn-primary btn-sm mt-3" data-toggle="modal" data-target="#editSettingsModal">
                                <i class="fas fa-edit"></i> Edit Settings
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editProfileModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-edit"></i> Edit Profile
                </h5>

                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>

            <form action="{{ route('merchant.profile.update') }}" method="POST">
                @csrf

                <div class="modal-body">
                    <div class="form-group">
                        <label for="first_name">First Name *</label>
                        <input type="text" class="form-control" name="first_name" id="first_name" value="{{ old('first_name', $user->first_name) }}" required>
                    </div>

                    <div class="form-group">
                        <label for="last_name">Last Name *</label>
                        <input type="text" class="form-control" name="last_name" id="last_name" value="{{ old('last_name', $user->last_name) }}" required>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="text" class="form-control" name="phone" id="phone" value="{{ old('phone', $user->phone) }}">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        Cancel
                    </button>

                    <button type="submit" class="btn btn-primary">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@if($address && $address->address)
<div class="modal fade" id="editAddressModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-map-marker-alt"></i> Edit Address
                </h5>

                <button type="button" class="close" data-dismiss="modal" data-bs-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>

            <form action="{{ route('merchant.profile.address.update') }}" method="POST">
                @csrf
                @method('PUT')

                <input type="hidden" name="address_id" value="{{ $address->id }}">

                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_country_id">Country *</label>

                        <select id="edit_country_id" name="country_id" class="form-control" required>
                            <option value="">Select Country</option>

                            @foreach($countries as $country)
                                <option
                                    value="{{ $country->id }}"
                                    {{ (string) ($selectedCountryId ?? '') === (string) $country->id ? 'selected' : '' }}
                                >
                                    {{ $country->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="edit_state_id">State/Province *</label>

                        <select id="edit_state_id" name="state_id" class="form-control" required disabled>
                            <option value="">Select Country First</option>
                        </select>

                        <div class="address-loading" id="edit-state-loading">
                            <i class="fas fa-spinner fa-spin"></i> Loading states...
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="edit_city_id">City *</label>

                        <select id="edit_city_id" name="city_id" class="form-control" required disabled>
                            <option value="">Select State First</option>
                        </select>

                        <div class="address-loading" id="edit-city-loading">
                            <i class="fas fa-spinner fa-spin"></i> Loading cities...
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="edit_address">Street Address *</label>

                        <input
                            type="text"
                            class="form-control"
                            name="address"
                            id="edit_address"
                            value="{{ $address->address }}"
                            required
                            placeholder="House number, street"
                        >
                    </div>

                    <div class="form-group">
                        <label for="edit_postal_code">Postal Code *</label>

                        <input
                            type="text"
                            class="form-control"
                            name="postal_code"
                            id="edit_postal_code"
                            value="{{ $address->postal_code }}"
                            required
                        >
                    </div>

                    <hr>

                    <div class="form-check">
                        <input
                            type="checkbox"
                            class="form-check-input"
                            name="is_active"
                            id="edit_is_active"
                            value="1"
                            {{ $address->is_active ? 'checked' : '' }}
                        >

                        <label class="form-check-label" for="edit_is_active">
                            Set as default address
                        </label>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button type="submit" class="btn btn-primary">
                        Update Address
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<div class="modal fade" id="editSettingsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-gear"></i> Edit Settings
                </h5>

                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>

            <form action="{{ route('merchant.profile.settings.update') }}" method="POST" id="settingsForm">
                @csrf
                @method('PUT')

                <div class="modal-body">
                    <!-- VAT Tax Percentage -->
                    <div class="form-group">
                        <label for="vat_tax_percentage">VAT Tax (%) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" step="0.01" class="form-control @error('vat_tax_percentage') is-invalid @enderror" name="vat_tax_percentage" id="vat_tax_percentage" value="{{ old('vat_tax_percentage', $merchant->vat_tax_percentage ?? 10.00) }}" min="0" max="100" required>
                            <div class="input-group-append">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        <small class="form-text text-muted">Set the VAT/GST percentage (0-100%)</small>
                        @error('vat_tax_percentage')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>

                    <!-- POS Payment Types - Multiple Checkbox Group -->
                    <div class="form-group">
                        <label>POS Payment Types <span class="text-danger">*</span></label>
                        <div class="payment-checkbox-group">
                            @php
                            // Get current payment types
                            $currentPayments = $merchant->pos_payment_types ?? ['card', 'tokens', 'hybrid'];

                            // Decode if it's JSON string
                            if (is_string($currentPayments)) {
                            $decoded = json_decode($currentPayments, true);
                            if (json_last_error() === JSON_ERROR_NONE) {
                            $currentPayments = $decoded;
                            } else {
                            $currentPayments = explode(',', $currentPayments);
                            }
                            }

                            // Ensure it's an array
                            if (!is_array($currentPayments)) {
                            $currentPayments = ['card', 'tokens', 'hybrid'];
                            }

                            $selectedPayments = old('pos_payment_types', $currentPayments);
                            @endphp

                            <label class="payment-checkbox-item">
                                <input type="checkbox" name="pos_payment_types[]" value="card" {{ in_array('card', $selectedPayments) ? 'checked' : '' }} class="payment-checkbox">
                                <span class="payment-checkbox-label">
                                    <i class="fas fa-credit-card"></i> Card Payments
                                </span>
                            </label>

                            <label class="payment-checkbox-item">
                                <input type="checkbox" name="pos_payment_types[]" value="tokens" {{ in_array('tokens', $selectedPayments) ? 'checked' : '' }} class="payment-checkbox">
                                <span class="payment-checkbox-label">
                                    <i class="fas fa-coins"></i> Trade Tokens
                                </span>
                            </label>

                            <label class="payment-checkbox-item">
                                <input type="checkbox" name="pos_payment_types[]" value="hybrid" {{ in_array('hybrid', $selectedPayments) ? 'checked' : '' }} class="payment-checkbox">
                                <span class="payment-checkbox-label">
                                    <i class="fas fa-exchange-alt"></i> Hybrid Payments
                                </span>
                            </label>

                            <label class="payment-checkbox-item">
                                <input type="checkbox" name="pos_payment_types[]" value="pos_mobile" {{ in_array('pos_mobile', $selectedPayments) ? 'checked' : '' }} class="payment-checkbox">
                                <span class="payment-checkbox-label">
                                    <i class="fas fa-mobile-alt"></i> Lyght App
                                </span>
                            </label>

                            <label class="payment-checkbox-item">
                                <input type="checkbox" name="pos_payment_types[]" value="cash" {{ in_array('cash', $selectedPayments) ? 'checked' : '' }} class="payment-checkbox">
                                <span class="payment-checkbox-label">
                                    <i class="fas fa-money-bill"></i> Cash
                                </span>
                            </label>
                        </div>
                        <small class="form-text text-muted">Select one or more payment methods to accept at your POS</small>
                        <div id="payment-error" class="invalid-feedback" style="display: none;">
                            Please select at least one payment type.
                        </div>
                        @error('pos_payment_types')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        Cancel
                    </button>

                    <button type="submit" class="btn btn-primary">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


@if($address && $address->address)
<script>
document.addEventListener('DOMContentLoaded', function () {
    const countrySelect = document.getElementById('edit_country_id');
    const stateSelect = document.getElementById('edit_state_id');
    const citySelect = document.getElementById('edit_city_id');

    const stateLoading = document.getElementById('edit-state-loading');
    const cityLoading = document.getElementById('edit-city-loading');

    const selectedStateId = @json($selectedStateId ?? null);
    const selectedCityId = @json($selectedCityId ?? null);

    const statesUrlTemplate = @json(route('merchant.profile.get-states', ['countryId' => '__COUNTRY_ID__']));
    const citiesUrlTemplate = @json(route('merchant.profile.get-cities', ['stateId' => '__STATE_ID__']));

    function getStatesUrl(countryId) {
        return statesUrlTemplate.replace('__COUNTRY_ID__', encodeURIComponent(countryId));
    }

    function getCitiesUrl(stateId) {
        return citiesUrlTemplate.replace('__STATE_ID__', encodeURIComponent(stateId));
    }

    function resetStateDropdown() {
        stateSelect.innerHTML = '<option value="">Select Country First</option>';
        stateSelect.disabled = true;
    }

    function resetCityDropdown() {
        citySelect.innerHTML = '<option value="">Select State First</option>';
        citySelect.disabled = true;
    }

    async function loadStates(countryId, stateIdToSelect = null, cityIdToSelect = null) {
        if (!countryId) {
            resetStateDropdown();
            resetCityDropdown();
            return;
        }

        stateLoading.style.display = 'block';
        stateSelect.disabled = true;
        stateSelect.innerHTML = '<option value="">Loading states...</option>';

        resetCityDropdown();

        try {
            const response = await fetch(getStatesUrl(countryId), {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load states');
            }

            const states = await response.json();

            stateSelect.innerHTML = '<option value="">Select State</option>';

            states.forEach(function (state) {
                const option = document.createElement('option');
                option.value = state.id;
                option.textContent = state.name;
                stateSelect.appendChild(option);
            });

            stateSelect.disabled = false;

            if (stateIdToSelect) {
                stateSelect.value = String(stateIdToSelect);

                if (stateSelect.value) {
                    await loadCities(stateIdToSelect, cityIdToSelect);
                }
            }
        } catch (error) {
            console.error(error);
            stateSelect.innerHTML = '<option value="">Error loading states</option>';
            stateSelect.disabled = true;
        } finally {
            stateLoading.style.display = 'none';
        }
    }

    async function loadCities(stateId, cityIdToSelect = null) {
        if (!stateId) {
            resetCityDropdown();
            return;
        }

        cityLoading.style.display = 'block';
        citySelect.disabled = true;
        citySelect.innerHTML = '<option value="">Loading cities...</option>';

        try {
            const response = await fetch(getCitiesUrl(stateId), {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load cities');
            }

            const cities = await response.json();

            citySelect.innerHTML = '<option value="">Select City</option>';

            cities.forEach(function (city) {
                const option = document.createElement('option');
                option.value = city.id;
                option.textContent = city.name;
                citySelect.appendChild(option);
            });

            citySelect.disabled = false;

            if (cityIdToSelect) {
                citySelect.value = String(cityIdToSelect);
            }
        } catch (error) {
            console.error(error);
            citySelect.innerHTML = '<option value="">Error loading cities</option>';
            citySelect.disabled = true;
        } finally {
            cityLoading.style.display = 'none';
        }
    }

    if (countrySelect && stateSelect && citySelect) {
        countrySelect.addEventListener('change', function () {
            loadStates(this.value);
        });

        stateSelect.addEventListener('change', function () {
            loadCities(this.value);
        });

        if (countrySelect.value) {
            loadStates(countrySelect.value, selectedStateId, selectedCityId);
        }
    }
});
</script>
@endif
@endsection