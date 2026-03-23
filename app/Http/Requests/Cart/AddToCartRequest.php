<?php

namespace App\Http\Requests\Cart;

use Illuminate\Foundation\Http\FormRequest;

class AddToCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required'],
            'barcode' => ['nullable', 'string', 'exists:products,barcode'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => __('cart.validation.product_not_found'),
        ];
    }
}
