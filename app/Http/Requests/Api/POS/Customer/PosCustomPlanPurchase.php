<?php

namespace App\Http\Requests\Api\POS\Customer;

use Illuminate\Foundation\Http\FormRequest;

class PosCustomPlanPurchase extends FormRequest
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
           
            'plan_id' => 'required',
            'billing_type' => 'required',
            'start_date' => 'nullable',
            'end_date' => 'nullable',
            'transaction_id' => 'nullable',
            'amount_paid' => 'required',
            'payment_method' => 'nullable',
            'payment_status' => 'nullable',
            'paypal_payment_id' => 'nullable',
            'paypal_payer_id' => 'nullable',
            'paypal_order_id' => 'nullable',
            'paypal_capture_id' => 'nullable',
            'status' => 'nullable'
        ];
    }
}
