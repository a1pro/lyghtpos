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
                                        <span><strong>City:</strong> {{ $address->city ?? 'Not provided' }}</span>
                                    </div>

                                    <div class="address-line">
                                        <i class="fas fa-building"></i>
                                        <span><strong>State/Province:</strong> {{ $address->state ?? 'Not provided' }}</span>
                                    </div>

                                    <div class="address-line">
                                        <i class="fas fa-mail-bulk"></i>
                                        <span><strong>Postal Code:</strong> {{ $address->postal_code ?? 'Not provided' }}</span>
                                    </div>

                                    <div class="address-line">
                                        <i class="fas fa-globe"></i>
                                        <span><strong>Country:</strong> {{ $address->country ?? 'Not provided' }}</span>
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
                                    value="{{ $country->country_id }}"
                                    {{ (string) ($selectedCountryId ?? '') === (string) $country->country_id ? 'selected' : '' }}
                                >
                                    {{ $country->country_name }}
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
                option.value = state.id_state;
                option.textContent = state.state;
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
                option.value = city.id_city;
                option.textContent = city.city;
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