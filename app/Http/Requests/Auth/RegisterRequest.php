<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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
            'account_type' => 'required|in:TRADE_MERCHANT,TRADE_MEMBER,ORGANIZATION,FAVOR_MEMBER',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'business_name' => 'required_if:account_type,TRADE_MERCHANT|string|max:255',
            'primary_contact_name' => 'required_if:account_type,TRADE_MERCHANT|string|max:255',
            'phone' => 'required|string|regex:/^[0-9]{8,14}$/',
            'location' => 'required|string|max:255',
            'website' => 'nullable|url|max:255',
            'consent_language_authorizing' => 'nullable|string',
            'communyti_enrollment' => 'nullable',
            'advocate_contact' => 'nullable|string|regex:/^[0-9]{8,14}$/',
            'intended_pos' => 'required'
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Please enter a valid phone number (8-14 digits)',
            'advocate_contact.regex' => 'Please enter a valid phone number (8-14 digits)',
            'website.url' => 'Please enter a valid URL (e.g., https://example.com)',
        ];
    }
}