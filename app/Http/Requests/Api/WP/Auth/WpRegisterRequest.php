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
            // User basic information
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'account_type' => 'required|in:FAVOR_MEMBER,TRADE_MERCHANT,TRADE_MEMBER,ORGANIZATION',
            
            // Merchant specific fields (only for TRADE_MERCHANT)
            'business_name' => 'required_if:account_type,TRADE_MERCHANT|string|max:255',
            'primary_contact_name' => 'required_if:account_type,TRADE_MERCHANT|string|max:255',
            'phone' => 'required_if:account_type,TRADE_MERCHANT|string|max:20',
            'location' => 'required_if:account_type,TRADE_MERCHANT|string|max:500',
            'website' => 'nullable|url|max:255',
            'consent_language_authorizing' => 'nullable|string',
            'communyti_enrollment' => 'nullable|boolean',
            'advocate_contact' => 'nullable|string|max:255',
            
            // Representation fields (conditional based on account_type)
            'community' => 'required_if:account_type,FAVOR_MEMBER,TRADE_MERCHANT|string',
            'role' => 'required_if:account_type,FAVOR_MEMBER,TRADE_MERCHANT|string',
            'accepted_currencies' => 'required_if:account_type,FAVOR_MEMBER,TRADE_MERCHANT|array|min:1',
            'accepted_currencies.*' => 'string',
            'participate_vouchers' => 'nullable|boolean',
            'allow_barter' => 'nullable|boolean',
            'pledge_description' => 'nullable|string|max:1000',
            'pledge_value' => 'required_if:account_type,FAVOR_MEMBER,TRADE_MERCHANT|numeric|min:0',
        ];
        
        return $rules;
    }
    
    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            // User messages
            'first_name.required' => 'First name is required',
            'last_name.required' => 'Last name is required',
            'email.required' => 'Email address is required',
            'email.email' => 'Please provide a valid email address',
            'email.unique' => 'This email address is already registered',
            'password.required' => 'Password is required',
            'password.min' => 'Password must be at least 8 characters',
            'password.confirmed' => 'Password confirmation does not match',
            'account_type.required' => 'Account type is required',
            'account_type.in' => 'Invalid account type selected',
            
            // Merchant messages
            'business_name.required_if' => 'Business name is required for trade merchants',
            'primary_contact_name.required_if' => 'Primary contact name is required for trade merchants',
            'phone.required_if' => 'Phone number is required for trade merchants',
            'location.required_if' => 'Location is required for trade merchants',
            
            // Representation messages
            'community.required_if' => 'Community is required for favor members and trade merchants',
            'role.required_if' => 'Role is required for favor members and trade merchants',
            'accepted_currencies.required_if' => 'At least one accepted currency is required for favor members and trade merchants',
            'accepted_currencies.min' => 'At least one accepted currency must be selected',
            'accepted_currencies.*.string' => 'Each accepted currency must be a valid string',
            'pledge_value.required_if' => 'Pledge value is required for favor members and trade merchants',
            'pledge_value.numeric' => 'Pledge value must be a number',
            'pledge_value.min' => 'Pledge value cannot be negative',
        ];
    }
    
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert boolean strings to actual booleans
        if ($this->has('participate_vouchers')) {
            $this->merge([
                'participate_vouchers' => filter_var($this->participate_vouchers, FILTER_VALIDATE_BOOLEAN)
            ]);
        }
        
        if ($this->has('allow_barter')) {
            $this->merge([
                'allow_barter' => filter_var($this->allow_barter, FILTER_VALIDATE_BOOLEAN)
            ]);
        }
        
        if ($this->has('communyti_enrollment')) {
            $this->merge([
                'communyti_enrollment' => filter_var($this->communyti_enrollment, FILTER_VALIDATE_BOOLEAN)
            ]);
        }
    }
    
    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status' => 'error',
            'status_code' => 422,
            'message' => 'Validation errors',
            'errors' => $validator->errors()
        ], 422));
    }
}