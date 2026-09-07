<?php

namespace App\Http\Requests\Api\PAX\Merchant;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCardSurchargeRequest extends FormRequest
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
            'card_surcharge_percentage' => 'required|numeric|min:0|max:4'
        ];
    }

    public function messages(): array
    {
        return [
            'card_surcharge_percentage.required' => 'The card surcharge percentage is required.',
            'card_surcharge_percentage.numeric' => 'The card surcharge percentage must be a valid number.',
            'card_surcharge_percentage.min' => 'The card surcharge percentage cannot be less than 0%.',
            'card_surcharge_percentage.max' => 'The card surcharge percentage cannot be greater than 4%.',
        ];
    }

     public function attributes(): array
    {
        return [
            'card_surcharge_percentage' => 'card surcharge percentage',
        ];
    }
}
