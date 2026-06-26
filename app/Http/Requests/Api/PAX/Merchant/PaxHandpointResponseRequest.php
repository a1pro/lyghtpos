<?php

namespace App\Http\Requests\Api\PAX\Merchant;

use Illuminate\Foundation\Http\FormRequest;

class PaxHandpointResponseRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'order_id' => 'required|integer|exists:merchant_orders,id',
            'handpoint_response' => 'required|array'
        ];
    }
}
