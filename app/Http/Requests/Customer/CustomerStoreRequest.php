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
            'first_name'  => ['required', 'string', 'max:20'],
            'last_name'   => ['required', 'string', 'max:20'],
            'email'       => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone'       => ['nullable', 'string', 'max:20'],
            'avatar'      => ['nullable', 'image', 'max:2048'],

            'country_id'  => ['required', 'exists:countries,id'],
            'state_id'    => ['required', 'exists:states,id'],
            'city_id'     => ['required', 'exists:cities,id'],

            'address'     => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => __('First name is required'),
            'first_name.string'   => __('First name must be text'),
            'first_name.max'      => __('First name may not be greater than 20 characters'),

            'last_name.required'  => __('Last name is required'),
            'last_name.string'    => __('Last name must be text'),
            'last_name.max'       => __('Last name may not be greater than 20 characters'),

            'email.required'      => __('Email is required'),
            'email.email'         => __('Email must be a valid email address'),
            'email.max'           => __('Email may not be greater than 255 characters'),
            'email.unique'        => __('This email is already registered'),

            'phone.max'           => __('Phone may not be greater than 20 characters'),

            'avatar.image'        => __('Avatar must be an image file'),
            'avatar.max'          => __('Avatar may not be greater than 2048 kilobytes'),

            'country_id.required' => __('Country is required'),
            'country_id.exists'   => __('Selected country is invalid'),

            'state_id.required'   => __('State/Province is required'),
            'state_id.exists'     => __('Selected state is invalid'),

            'city_id.required'    => __('City is required'),
            'city_id.exists'      => __('Selected city is invalid'),

            'address.required'    => __('Address is required'),
            'address.max'         => __('Address may not be greater than 255 characters'),

            'postal_code.required' => __('Zip/Postal code is required'),
            'postal_code.max'      => __('Zip/Postal code may not be greater than 20 characters'),
        ];
    }
}
