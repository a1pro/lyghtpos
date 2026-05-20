<?php

namespace App\Http\Requests\Api\PAX\Merchant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaxCustomerCreateOrderRequest extends FormRequest
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
            'customer_id' => 'required|string',

            'amount' => 'required|numeric|min:0.01', // excluding tax amount
            'tax_amount' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric|min:0', // including tax amount

            'payment_method' => [
                'required',
                Rule::in(['card', 'cyclos_tokens', 'hybrid', 'pos_mobile', 'cash']),
            ],

            // Payment amounts based on method
            'token_amount' => 'required_if:payment_method,cyclos_tokens,hybrid|nullable|numeric|min:0', // excluding tax amount
            'card_amount' => 'required_if:payment_method,card,hybrid|nullable|numeric|min:0', // excluding tax amount

            // Products
            'products' => 'required|array|min:1',
            'products.*.id' => 'required|string|distinct',
            'products.*.name' => 'required|string',
            'products.*.price' => 'required|numeric|min:0',
            'products.*.quantity' => 'required|integer|min:1',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $paymentMethod = $this->input('payment_method');
            $totalAmount = $this->input('total_amount');
            $taxAmount = $this->input('tax_amount');
            $amount = $this->input('amount');
            $tokenAmount = $this->input('token_amount', 0);
            $cardAmount = $this->input('card_amount', 0);

            // Validate total calculation
            if ($amount + $taxAmount != $totalAmount) {
                $validator->errors()->add('total_amount', 'Total amount must equal amount + tax_amount');
            }

            // Validate payment method specific amounts
            if ($paymentMethod === 'card') {
                if ($cardAmount != $amount) {
                    $validator->errors()->add('card_amount', "Card amount ({$cardAmount}) must equal product total amount ({$amount}) for card payment");
                }
            } elseif ($paymentMethod === 'cyclos_tokens') {
                if ($tokenAmount != $amount) {
                    $validator->errors()->add('token_amount', "Token amount ({$tokenAmount}) must equal product total amount ({$amount}) for token payment");
                }
            } elseif ($paymentMethod === 'hybrid') {
                if ($tokenAmount <= 0 || $cardAmount <= 0) {
                    $validator->errors()->add('hybrid_amounts', 'Both token amount and card amount must be greater than 0 for hybrid payment');
                }

                if ($tokenAmount + $cardAmount != $amount) {
                    $validator->errors()->add('hybrid_amounts', "Card amount ({$cardAmount}) + Token amount ({$tokenAmount}) must equal product total amount ({$amount}) for hybrid payment");
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'Customer ID is required',

            'amount.required' => 'Order amount is required',
            'amount.min' => 'Order amount must be at least 0.01',
            'tax_amount.required' => 'Tax amount is required',
            'tax_amount.min' => 'Tax amount cannot be negative',
            'total_amount.required' => 'Total amount is required',

            'payment_method.required' => 'Payment method is required',
            'payment_method.in' => 'Payment method must be either card, cyclos_tokens, hybrid, pos_mobile, or cash',

            'token_amount.required_if' => 'Token amount is required for token or hybrid payment',
            'card_amount.required_if' => 'Card amount is required for card or hybrid payment',
            'token_amount.min' => 'Token amount must be at least 0',
            'card_amount.min' => 'Card amount must be at least 0',

            'products.required' => 'At least one product is required',
            'products.min' => 'At least one product is required',
            'products.*.id.required' => 'Product ID is required for each product',
            'products.*.id.distinct' => 'Product IDs must be unique',
            'products.*.name.required' => 'Product name is required for each product',
            'products.*.price.required' => 'Product price is required',
            'products.*.price.min' => 'Product price cannot be negative',
            'products.*.quantity.required' => 'Product quantity is required',
            'products.*.quantity.min' => 'Product quantity must be at least 1',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'customer_id' => 'customer ID',
            'amount' => 'order amount',
            'tax_amount' => 'tax amount',
            'total_amount' => 'total amount',
            'payment_method' => 'payment method',
            'token_amount' => 'token amount',
            'card_amount' => 'card amount',
            'products.*.id' => 'product ID',
            'products.*.name' => 'product name',
            'products.*.price' => 'product price',
            'products.*.quantity' => 'product quantity',
        ];
    }
}
