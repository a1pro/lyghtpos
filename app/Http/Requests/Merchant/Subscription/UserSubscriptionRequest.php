<?php

namespace App\Http\Requests\Merchant\Subscription;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserSubscriptionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Change to true to allow authorized users
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'plan_id' => ['required', 'integer', Rule::exists('subscription_plans', 'id')],
            'billing_type' => ['required', 'in:monthly,yearly'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'transaction_id' => ['nullable', 'string', 'unique:user_subscriptions,transaction_id'],
            'amount_paid' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'in:active,inactive,expired,cancelled']
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'User is required.',
            'user_id.integer' => 'User must be a valid ID.',
            'user_id.exists' => 'Selected user does not exist.',

            'plan_id.required' => 'Plan is required.',
            'plan_id.integer' => 'Plan must be a valid ID.',
            'plan_id.exists' => 'Selected plan does not exist.',
            
            'billing_type.required' => 'Billing type is required.',
            'billing_type.in' => 'Billing type must be monthly or yearly.',

            'start_date.required' => 'Start date is required.',
            'start_date.date' => 'Start date must be a valid date.',

            'end_date.required' => 'End date is required.',
            'end_date.date' => 'End date must be a valid date.',
            'end_date.after_or_equal' => 'End date must be the same as or after the start date.',
            
            'transaction_id.unique' => 'Transaction ID must be unique.',
            
            'amount_paid.numeric' => 'Amount paid must be a number.',
            'amount_paid.min' => 'Amount paid cannot be negative.',
            
            'status.in' => 'Invalid status selected.'
        ];
    }
}