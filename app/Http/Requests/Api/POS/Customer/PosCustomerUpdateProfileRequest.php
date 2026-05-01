<?php

namespace App\Http\Requests\Api\POS\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PosCustomerUpdateProfileRequest extends FormRequest
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
            'first_name' => 'required|string|max:200',
            'last_name' => 'required|string|max:200',
            'phone' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('users', 'phone')->ignore($this->user()->id)
            ],
            'address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:100',
        ];
    }

    public function message(): array
    {
        return [
            'first_name.required' => 'First name is required',
            'first_name.string' => 'First name must be a string',
            'first_name.max' => 'First name must not exceed 255 characters',

            'last_name.required' => 'Last name is required',
            'last_name.string' => 'Last name must be a string',
            'last_name.max' => 'Last name must not exceed 255 characters',

            'phone.string' => 'Phone number must be a string',
            'phone.max' => 'Phone number must not exceed 20 characters',
            'phone.unique' => 'Phone number is already taken by another user',

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
