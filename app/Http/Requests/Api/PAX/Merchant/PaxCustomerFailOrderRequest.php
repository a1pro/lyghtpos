<?php

namespace App\Http\Requests\Api\PAX\Merchant;

use Illuminate\Foundation\Http\FormRequest;

class PaxCustomerFailOrderRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'order_id' => 'required|integer|exists:merchant_orders,id',
            'failure_message' => 'nullable|string|max:500'
        ];
    }
}
