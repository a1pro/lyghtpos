<?php

namespace App\Http\Requests\Api\WP\Representation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class WpRepresentationRequest extends FormRequest
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
            'user_id' => 'required|exists:users,id',
            'community' => 'required|string',
            'role' => 'required|string',
            'accepted_currencies' => 'required|array|min:1',
            'accepted_currencies.*' => 'string',
            'participate_vouchers' => 'nullable|boolean',
            'allow_barter' => 'nullable|boolean',
            'pledge_description' => 'nullable|string|max:1000',
            'pledge_value' => 'required|numeric|min:0',
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
            'user_id.required' => 'User ID is required',
            'user_id.exists' => 'User does not exist',
            'community.required' => 'Community is required',
            'role.required' => 'Role is required',
            'accepted_currencies.required' => 'At least one accepted currency is required',
            'accepted_currencies.min' => 'At least one accepted currency must be selected',
            'pledge_value.required' => 'Pledge value is required',
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