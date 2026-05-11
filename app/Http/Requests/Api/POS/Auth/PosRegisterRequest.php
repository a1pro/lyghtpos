<?php

namespace App\Http\Requests\Api\POS\Auth;

use Illuminate\Foundation\Http\FormRequest;

class PosRegisterRequest extends FormRequest
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
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20|unique:users,phone',
            'password' => 'required|string|min:6',
            'referal_code' => 'nullable|string',

            'address' => 'required|string|max:500',
            'city' => 'required|integer|exists:cities,id',
            'state' => 'required|integer|exists:states,id',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|integer|exists:countries,id',
        ];
    }

    public function message(): array
    {
        return [
            'firstname.required' => 'First name is required',
            'firstname.string' => 'First name must be a string',
            'firstname.max' => 'First name must not exceed 255 characters',

            'lastname.required' => 'Last name is required',
            'lastname.string' => 'Last name must be a string',
            'lastname.max' => 'Last name must not exceed 255 characters',

            'email.required' => 'Email is required',
            'email.email' => 'Email must be a valid email address',
            'email.unique' => 'Email is already taken',

            'phone.string' => 'Phone number must be a string',
            'phone.max' => 'Phone number must not exceed 20 characters',
            'phone.unique' => 'Phone number is already taken',

            'password.required' => 'Password is required',
            'password.string' => 'Password must be a string',
            'password.min' => 'Password must be at least 6 characters',

            'referal_code.string' => 'Referal Code Must be a valid string',

            'address.required' => 'Address is required',
            'address.string' => 'Address must be a string',
            'address.max' => 'Address must not exceed 500 characters',

            'city.required' => 'City is required',
            'city.integer' => 'City must be a valid ID',
            'city.exists' => 'Selected city does not exist in our records',

            'state.required' => 'State is required',
            'state.integer' => 'State must be a valid ID',
            'state.exists' => 'Selected state does not exist in our records',

            'postal_code.required' => 'Postal code is required',
            'postal_code.string' => 'Postal code must be a string',
            'postal_code.max' => 'Postal code must not exceed 20 characters',

            'country.required' => 'Country is required',
            'country.integer' => 'Country must be a valid ID',
            'country.exists' => 'Selected country does not exist in our records',
        ];
    }
}
