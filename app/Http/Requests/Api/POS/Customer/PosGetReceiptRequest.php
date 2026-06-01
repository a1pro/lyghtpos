<?php

namespace App\Http\Requests\Api\POS\Customer;

use Illuminate\Foundation\Http\FormRequest;

class PosGetReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => 'required|integer|exists:merchant_orders,id'
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
