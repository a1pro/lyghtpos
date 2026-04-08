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
            'phone' => 'required|string|max:20|unique:users,phone',
            'location' => 'required|string|max:255',
            'password' => 'required|string|min:6',
            'referal_code' => 'nullable|string'
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
            'phone.required' => 'Phone number is required',
            'phone.string' => 'Phone number must be a string',
            'phone.max' => 'Phone number must not exceed 20 characters',
            'phone.unique' => 'Phone number is already taken',
            'location.required' => 'Location is required',
            'location.string' => 'Location must be a string',
            'location.max' => 'Location must not exceed 255 characters',
            'password.required' => 'Password is required',
            'password.string' => 'Password must be a string',
            'password.min' => 'Password must be at least 6 characters',
            'referal_code.string' => 'Referal Code Must be a valid string'

        ];
    }
}
