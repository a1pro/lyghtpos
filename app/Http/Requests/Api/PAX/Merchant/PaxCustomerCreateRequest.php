<?php

namespace App\Http\Requests\Api\PAX\Merchant;

use Illuminate\Foundation\Http\FormRequest;

class PaxCustomerCreateRequest extends FormRequest
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
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
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

            'address.string' => 'Address must be a valid string',
            'address.max' => 'Address cannot exceed 500 characters',
        ];
    }
}
