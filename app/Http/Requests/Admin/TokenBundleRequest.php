<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class TokenBundleRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $rules = [
            'tokens'       => 'required|integer|min:1',
            'base_amount' => 'required|numeric',
            'system_fee'  => 'nullable|numeric',
            'discounted'  => 'boolean',
            'status'      => 'required|in:active,inactive',
        ];

        return $rules;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        $discounted = $this->has('discounted') ? 1 : 0;

        $systemFee = $this->input('system_fee');
        if ($systemFee === null || $systemFee === '') {
            $systemFee = 0;
        }

        $this->merge([
            'discounted' => $discounted,
            'system_fee' => $systemFee,
        ]);
    }

    public function messages()
    {
        return [
            'tokens.required'       => 'Please enter the number of tokens.',
            'tokens.integer'        => 'Tokens must be a whole number.',
            'tokens.min'            => 'Tokens must be at least 1.',

            'base_amount.required' => 'The base amount field is required.',
            'base_amount.numeric'  => 'The base amount must be a number.',

            'system_fee.numeric'   => 'The system fee must be a number.',

            'discounted.boolean'   => 'The discounted field must be true or false.',

            'status.required'      => 'Please select a status.',
            'status.in'            => 'The selected status is invalid. Choose active or inactive.',
        ];
    }
}
