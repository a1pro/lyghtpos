<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class CustomerStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:20'],
            'last_name' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'avatar' => ['nullable', 'image', 'max:2048'],
            'country_id' => 'required|exists:countries,id',
            'state_id' => 'required|exists:states,id',
            'city_id' => 'required|exists:cities,id',
            'address' => 'required|string|max:255',
            'postal_code' => 'required|string|max:20',
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => __('First name is required'),
            'first_name.max' => __('First name may not be greater than 20 characters'),
            'last_name.required' => __('Last name is required'),
            'last_name.max' => __('Last name may not be greater than 20 characters'),
            'email.required' => __('Email is required'),
            'email.email' => __('Email must be a valid email address'),
            'phone.max' => __('Phone may not be greater than 20 characters'),
            'avatar.image' => __('Avatar must be an image file'),
            'avatar.max' => __('Avatar may not be greater than 2048 kilobytes'),
            'country_id' => 'required|exists:countries,country_id',
            'state_id' => 'required|exists:states,id_state',
            'city_id' => 'required|exists:cities,id_city',
            'address' => 'required|string|max:255',
            'postal_code' => 'required|string|max:20',
        ];
    }
}
