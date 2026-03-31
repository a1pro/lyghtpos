<?php

namespace App\Http\Requests\Api\WP\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class WpRegisterRequest extends FormRequest
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
        $rules = [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string|min:8',
            'account_type' => 'required|in:TRADE_MERCHANT,TRADE_MEMBER,ORGANIZATION,FAVOR_MEMBER',
        ];

        // Merchant specific fields (required for TRADE_MERCHANT)
        if ($this->account_type === 'TRADE_MERCHANT') {
            $rules['business_name'] = 'required|string|max:255';
            $rules['primary_contact_name'] = 'required|string|max:255';
            $rules['phone'] = 'required|string|max:20';
            $rules['location'] = 'required|string|max:500';
            $rules['website'] = 'nullable|url|max:255';
            $rules['consent_language_authorizing'] = 'nullable|string';
            $rules['communyti_enrollment'] = 'nullable|boolean';
            $rules['advocate_contact'] = 'nullable|string|max:255';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'This email is already registered.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
            'account_type.in' => 'Invalid account type selected.',
            'business_name.required' => 'Business name is required for trade merchant.',
            'primary_contact_name.required' => 'Primary contact name is required for trade merchant.',
            'phone.required' => 'Phone number is required for trade merchant.',
            'location.required' => 'Location is required for trade merchant.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status' => 'error',
            'status_code' => 422,
            'message' => 'Validation failed',
            'errors' => $validator->errors()
        ], 422));
    }
}