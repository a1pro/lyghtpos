<?php

namespace App\Http\Requests\FavorMember;

use Illuminate\Foundation\Http\FormRequest;

class AddFundsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:1', 'max:10000']
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Please enter an amount',
            'amount.numeric' => 'Amount must be a number',
            'amount.min' => 'Minimum amount is 1 TT',
            'amount.max' => 'Maximum amount is 10,000 TT'
        ];
    }
}