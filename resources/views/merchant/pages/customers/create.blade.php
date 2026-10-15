@extends('merchant.layouts.main')

@section('title', __('customer.Create_Customer'))
@section('content-header', __('customer.Create_Customer'))

@section('content')
<div class="card">
    <div class="card-body">

        <form action="{{ auth()->user()->role_id == 1 ? route('customers.store') : route('merchant.customers.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="row">
                {{-- LEFT: Basic Details --}}
                <div class="col-md-6">
                    <h5 class="mb-3">Basic Details</h5>

                    <div class="form-group">
                        <label for="first_name">{{ __('customer.First_Name') }}</label>
                        <input type="text" name="first_name" class="form-control @error('first_name') is-invalid @enderror"
                               id="first_name" placeholder="{{ __('customer.First_Name') }}" value="{{ old('first_name') }}">
                        @error('first_name')
                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="last_name">{{ __('customer.Last_Name') }}</label>
                        <input type="text" name="last_name" class="form-control @error('last_name') is-invalid @enderror"
                               id="last_name" placeholder="{{ __('customer.Last_Name') }}" value="{{ old('last_name') }}">
                        @error('last_name')
                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="email">{{ __('customer.Email') }}</label>
                        <input type="text" name="email" class="form-control @error('email') is-invalid @enderror"
                               id="email" placeholder="{{ __('customer.Email') }}" value="{{ old('email') }}">
                        @error('email')
                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="phone">{{ __('customer.Phone') }}</label>
                        <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                               id="phone" placeholder="{{ __('customer.Phone') }}" value="{{ old('phone') }}">
                        @error('phone')
                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>
                </div>

                {{-- RIGHT: Address --}}
                <div class="col-md-6">
                    <h5 class="mb-3">Customer Address</h5>

                    <div class="form-group">
                        <label for="country_id">Country *</label>
                        <select name="country_id" id="country_id" class="form-control @error('country_id') is-invalid @enderror" required>
                            <option value="">Select Country</option>
                            @foreach($countries as $country)
                                <option value="{{ $country->id }}" {{ old('country_id') == $country->id ? 'selected' : '' }}>
                                    {{ $country->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('country_id')
                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="state_id">State/Province *</label>
                        <select name="state_id" id="state_id" class="form-control @error('state_id') is-invalid @enderror" required disabled>
                            <option value="">Select Country First</option>
                        </select>
                        <small id="state-loading" style="display:none;" class="text-primary">
                            <i class="fas fa-spinner fa-spin"></i> Loading states...
                        </small>
                        @error('state_id')
                        <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="city_id">City *</label>
                        <select name="city_id" id="city_id" class="form-control @error('city_id') is-invalid @enderror" required disabled>
                            <option value="">Select State First</option>
                        </select>
                        <small id="city-loading" style="display:none;" class="text-primary">
                            <i class="fas fa-spinner fa-spin"></i> Loading cities...
                        </small>
                        @error('city_id')
                        <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="address">{{ __('customer.Address') }} *</label>
                        <input type="text" name="address" class="form-control @error('address') is-invalid @enderror"
                               id="address" placeholder="{{ __('customer.Address') }}" value="{{ old('address') }}" required>
                        @error('address')
                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="postal_code">Zip/Postal Code *</label>
                        <input type="text" name="postal_code" class="form-control @error('postal_code') is-invalid @enderror"
                               id="postal_code" placeholder="Zip/Postal Code" value="{{ old('postal_code') }}" required>
                        @error('postal_code')
                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>
                </div>
            </div>

            <button class="btn btn-primary" type="submit">{{ __('common.Create') }}</button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const countrySelect = document.getElementById('country_id');
    const stateSelect = document.getElementById('state_id');
    const citySelect = document.getElementById('city_id');

    const stateLoading = document.getElementById('state-loading');
    const cityLoading = document.getElementById('city-loading');

    const oldCountryId = @json(old('country_id'));
    const oldStateId = @json(old('state_id'));
    const oldCityId = @json(old('city_id'));

    const statesUrlTemplate = @json(route('merchant.profile.get-states', ['countryId' => '__COUNTRY_ID__']));
    const citiesUrlTemplate = @json(route('merchant.profile.get-cities', ['stateId' => '__STATE_ID__']));

    function getStatesUrl(countryId) {
        return statesUrlTemplate.replace('__COUNTRY_ID__', encodeURIComponent(countryId));
    }

    function getCitiesUrl(stateId) {
        return citiesUrlTemplate.replace('__STATE_ID__', encodeURIComponent(stateId));
    }

    function resetState() {
        stateSelect.innerHTML = '<option value="">Select Country First</option>';
        stateSelect.disabled = true;
    }

    function resetCity() {
        citySelect.innerHTML = '<option value="">Select State First</option>';
        citySelect.disabled = true;
    }

    async function loadStates(countryId, selectedStateId = null, selectedCityId = null) {
        if (!countryId) {
            resetState();
            resetCity();
            return;
        }

        stateLoading.style.display = 'inline-block';
        stateSelect.disabled = true;
        stateSelect.innerHTML = '<option value="">Loading states...</option>';
        resetCity();

        try {
            const response = await fetch(getStatesUrl(countryId), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const states = await response.json();

            stateSelect.innerHTML = '<option value="">Select State</option>';

            states.forEach(function (state) {
                const option = document.createElement('option');
                option.value = state.id;
                option.textContent = state.name;
                stateSelect.appendChild(option);
            });

            stateSelect.disabled = false;

            if (selectedStateId) {
                stateSelect.value = String(selectedStateId);

                if (stateSelect.value) {
                    await loadCities(selectedStateId, selectedCityId);
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

    async function loadCities(stateId, selectedCityId = null) {
        if (!stateId) {
            resetCity();
            return;
        }

        cityLoading.style.display = 'inline-block';
        citySelect.disabled = true;
        citySelect.innerHTML = '<option value="">Loading cities...</option>';

        try {
            const response = await fetch(getCitiesUrl(stateId), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const cities = await response.json();

            citySelect.innerHTML = '<option value="">Select City</option>';

            cities.forEach(function (city) {
                const option = document.createElement('option');
                option.value = city.id;
                option.textContent = city.name;
                citySelect.appendChild(option);
            });

            citySelect.disabled = false;

            if (selectedCityId) {
                citySelect.value = String(selectedCityId);
            }
        } catch (error) {
            console.error(error);
            citySelect.innerHTML = '<option value="">Error loading cities</option>';
            citySelect.disabled = true;
        } finally {
            cityLoading.style.display = 'none';
        }
    }

    countrySelect.addEventListener('change', function () {
        loadStates(this.value);
    });

    stateSelect.addEventListener('change', function () {
        loadCities(this.value);
    });

    if (oldCountryId) {
        loadStates(oldCountryId, oldStateId, oldCityId);
    }
});
</script>
@endsection

@section('js')
<script src="{{ asset('plugins/bs-custom-file-input/bs-custom-file-input.min.js') }}"></script>
<script>
    $(document).ready(function () {
        bsCustomFileInput.init();
    });
</script>
@endsection