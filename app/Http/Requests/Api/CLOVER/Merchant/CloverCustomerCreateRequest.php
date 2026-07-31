<?php

namespace App\Http\Requests\Api\CLOVER\Merchant;

use Illuminate\Foundation\Http\FormRequest;

class CloverCustomerCreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email|max:255',
            'phone' => 'nullable|string|max:20|unique:users,phone',

            'address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:100',
        ];
    }


    public function messages(): array
    {
        return [
            'first_name.required' => 'First name is required',
            'first_name.string' => 'First name must be a valid string',
            'first_name.max' => 'First name cannot exceed 255 characters',

            'last_name.required' => 'Last name is required',
            'last_name.string' => 'Last name must be a valid string',
            'last_name.max' => 'Last name cannot exceed 255 characters',

            'email.required' => 'Email is required',
            'email.email' => 'Please enter a valid email address',
            'email.unique' => 'This email is already registered',
            'email.max' => 'Email cannot exceed 255 characters',

            'phone.string' => 'Phone number must be a valid string',
            'phone.max' => 'Phone number cannot exceed 20 characters',

            'address.required' => 'Address is required',
            'address.string' => 'Address must be a string',
            'address.max' => 'Address must not exceed 500 characters',

            'city.required' => 'City is required',
            'city.string' => 'City must be a string',
            'city.max' => 'City must not exceed 100 characters',

            'state.required' => 'State is required',
            'state.string' => 'State must be a string',
            'state.max' => 'State must not exceed 100 characters',

            'postal_code.required' => 'Postal code is required',
            'postal_code.string' => 'Postal code must be a string',
            'postal_code.max' => 'Postal code must not exceed 20 characters',

            'country.required' => 'Country is required',
            'country.string' => 'Country must be a string',
            'country.max' => 'Country must not exceed 100 characters',
        ];
    }
}
