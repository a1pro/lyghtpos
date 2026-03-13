<?php

namespace App\Http\Requests\FavorMember;

use Illuminate\Foundation\Http\FormRequest;

class ProcessPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'card_number' => ['required', 'string', 'min:13', 'max:19'],
            'expiry_month' => ['required', 'string', 'size:2'],
            'expiry_year' => ['required', 'string', 'size:4'],
            'cvv' => ['required', 'string', 'min:3', 'max:4'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'max:255'],
            'zip' => ['required', 'string', 'max:20']
        ];
    }

    public function messages(): array
    {
        return [
            'card_number.required' => 'Card number is required',
            'expiry_month.required' => 'Expiry month is required',
            'expiry_year.required' => 'Expiry year is required',
            'cvv.required' => 'CVV is required'
        ];
    }
}