<?php

namespace App\Http\Requests\Merchant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateSettingsRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'vat_tax_percentage' => [
                'required',
                'numeric',
                'min:0',
                'max:100',
                'regex:/^\d+(\.\d{1,2})?$/'
            ],
            'pos_payment_types' => [
                'required',
                'array',
                'min:1'
            ],
            'pos_payment_types.*' => [
                'string',
                'in:card,tokens,hybrid,pos_mobile,cash'
            ],
            'handpoint_api_key' => [
                'nullable',
                'string',
                'max:255'
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'vat_tax_percentage.required' => 'The VAT tax percentage is required.',
            'vat_tax_percentage.numeric' => 'The VAT tax percentage must be a valid number.',
            'vat_tax_percentage.min' => 'The VAT tax percentage must be at least 0%.',
            'vat_tax_percentage.max' => 'The VAT tax percentage cannot exceed 100%.',
            'vat_tax_percentage.regex' => 'The VAT tax percentage must have at most 2 decimal places.',

            'pos_payment_types.required' => 'At least one payment type must be selected.',
            'pos_payment_types.array' => 'The payment types must be submitted as an array.',
            'pos_payment_types.min' => 'Please select at least one payment type.',
            'pos_payment_types.*.in' => 'The selected payment type is invalid. Allowed types: card, tokens, hybrid, pos_mobile, cash.',

            'handpoint_api_key.string' => 'The Handpoint API key must be a string.',
            'handpoint_api_key.max' => 'The Handpoint API key cannot exceed 255 characters.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'vat_tax_percentage' => 'VAT tax percentage',
            'pos_payment_types' => 'POS payment types',
            'pos_payment_types.*' => 'payment type',
            'handpoint_api_key' => 'Handpoint API key',
        ];
    }
}
