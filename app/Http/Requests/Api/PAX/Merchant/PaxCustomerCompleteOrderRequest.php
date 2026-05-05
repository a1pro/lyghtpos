<?php

namespace App\Http\Requests\Api\PAX\Merchant;

use Illuminate\Foundation\Http\FormRequest;

class PaxCustomerCompleteOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => 'required|integer|exists:merchant_orders,id',
            'transaction_id' => 'nullable|string',
            'handpoint_response' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.required' => 'Order ID is required',
            'order_id.exists' => 'Order not found',
        ];
    }
}
