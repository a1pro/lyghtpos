<?php

namespace App\Http\Requests\Api\POS\Customer;

use Illuminate\Foundation\Http\FormRequest;

class ChargeCardTokenRequest extends FormRequest
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
            'order_id' => 'nullable|integer|exists:merchant_orders,id',
            'card_token' => 'required|string',
            'amount' => 'required|numeric|min:0.01'
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.integer' => 'The order ID must be an integer.',
            'order_id.exists' => 'The specified order ID does not exist.',

            'card_token.required' => 'The card token is required.',
            'card_token.string' => 'The card token must be a string.',
            
            'amount.required' => 'The amount is required.',
            'amount.numeric' => 'The amount must be a number.',
            'amount.min' => 'The amount must be at least 0.01.',
        ];
    }
}
