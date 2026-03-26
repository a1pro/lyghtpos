<?php
// app/Http/Requests/Merchant/PayoutRequest.php

namespace App\Http\Requests\Merchant;

use Illuminate\Foundation\Http\FormRequest;

class PayoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $rules = [
            'amount' => 'required|numeric|min:10|max:100000',
            'payment_method' => 'required|in:bank_transfer,paypal,stripe',
            'notes' => 'nullable|string|max:500',
        ];

        // Add conditional rules based on payment method
        switch ($this->payment_method) {
            case 'bank_transfer':
                $rules['account_name'] = 'required|string|max:255';
                $rules['account_number'] = 'required|string|max:50';
                $rules['bank_name'] = 'required|string|max:255';
                $rules['routing_number'] = 'nullable|string|max:20';
                break;
                
            case 'paypal':
                $rules['paypal_email'] = 'required|email|max:255';
                break;
                
            case 'stripe':
                $rules['stripe_account_id'] = 'required|string|max:255|regex:/^acct_[A-Za-z0-9]+$/';
                break;
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Please enter an amount',
            'amount.min' => 'Minimum payout amount is $10',
            'amount.max' => 'Maximum payout amount is $100,000',
            'payment_method.required' => 'Please select a payment method',
            'payment_method.in' => 'Invalid payment method selected',
            
            'account_name.required' => 'Account name is required',
            'account_number.required' => 'Account number is required',
            'bank_name.required' => 'Bank name is required',
            
            'paypal_email.required' => 'PayPal email is required',
            'paypal_email.email' => 'Please enter a valid email address',
            
            'stripe_account_id.required' => 'Stripe account ID is required',
            'stripe_account_id.regex' => 'Stripe account ID must start with "acct_" followed by letters and numbers',
        ];
    }
}