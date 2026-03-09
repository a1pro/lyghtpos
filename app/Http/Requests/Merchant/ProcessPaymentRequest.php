<?php

namespace App\Http\Requests\Merchant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProcessPaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Add your authorization logic here
        // For example, check if user has permission to make payments
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Payment amount validation
            'amount' => [
                'required',
                'numeric',
                'min:0.01',
                'max:999999.99',
                'regex:/^\d+(\.\d{1,2})?$/'
            ],
            
            // Order description (optional)
            'order_description' => 'nullable|string|max:255',
            
            // Card information validation
            'card_number' => [
                'required',
                'string',
                'min:13',
                'max:19',
                'regex:/^[0-9\s]+$/'
            ],
            
            'expiry_month' => [
                'required',
                'string',
                'size:2',
                'regex:/^(0[1-9]|1[0-2])$/'
            ],
            
            'expiry_year' => [
                'required',
                'string',
                'size:4',
                'regex:/^20[2-9][0-9]$/',
                function ($attribute, $value, $fail) {
                    $month = $this->input('expiry_month');
                    $year = $value;
                    $expiry = \Carbon\Carbon::createFromDate($year, $month, 1)->endOfMonth();
                    
                    if ($expiry->isPast()) {
                        $fail('The card has expired.');
                    }
                },
            ],
            
            'cvv' => [
                'required',
                'string',
                'min:3',
                'max:4',
                'regex:/^[0-9]+$/'
            ],
            
            // Billing information (optional)
            'billing.first_name' => 'nullable|string|max:50',
            'billing.last_name' => 'nullable|string|max:50',
            'billing.address' => 'nullable|string|max:255',
            'billing.city' => 'nullable|string|max:100',
            'billing.state' => 'nullable|string|max:50',
            'billing.zip' => 'nullable|string|max:20',
            'billing.country' => 'nullable|string|max:50',
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
            'amount.required' => 'The payment amount is required.',
            'amount.numeric' => 'Please enter a valid amount.',
            'amount.min' => 'The minimum payment amount is $0.01.',
            'amount.max' => 'The maximum payment amount is $999,999.99.',
            'amount.regex' => 'Please enter a valid amount with up to 2 decimal places.',
            
            'card_number.required' => 'The card number is required.',
            'card_number.min' => 'The card number must be at least 13 digits.',
            'card_number.max' => 'The card number must not exceed 19 digits.',
            'card_number.regex' => 'The card number must contain only digits and spaces.',
            
            'expiry_month.required' => 'The expiry month is required.',
            'expiry_month.size' => 'Please select a valid expiry month.',
            'expiry_month.regex' => 'Please select a valid expiry month.',
            
            'expiry_year.required' => 'The expiry year is required.',
            'expiry_year.size' => 'Please select a valid expiry year.',
            'expiry_year.regex' => 'Please select a valid expiry year.',
            
            'cvv.required' => 'The CVV is required.',
            'cvv.min' => 'The CVV must be at least 3 digits.',
            'cvv.max' => 'The CVV must not exceed 4 digits.',
            'cvv.regex' => 'The CVV must contain only digits.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Clean up card number by removing spaces
        if ($this->has('card_number')) {
            $this->merge([
                'card_number' => str_replace(' ', '', $this->card_number)
            ]);
        }

        // Ensure amount is properly formatted
        if ($this->has('amount')) {
            $this->merge([
                'amount' => number_format((float) $this->amount, 2, '.', '')
            ]);
        }
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes(): array
    {
        return [
            'billing.first_name' => 'billing first name',
            'billing.last_name' => 'billing last name',
            'billing.address' => 'billing address',
            'billing.city' => 'billing city',
            'billing.state' => 'billing state',
            'billing.zip' => 'billing ZIP code',
            'billing.country' => 'billing country',
        ];
    }

    /**
     * Handle a passed validation attempt.
     */
    protected function passedValidation(): void
    {
        // You can perform additional actions after validation passes
        // For example, log the validated data or trigger events
    }
}