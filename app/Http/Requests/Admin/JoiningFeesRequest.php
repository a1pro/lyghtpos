<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class JoiningFeesRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Update based on your auth logic
    }

    public function rules()
    {
        $rules = [
            'role_id' => 'required|exists:roles,id',
            'amount' => 'required|numeric|min:0|max:999999.99',
            'status' => 'sometimes|in:active,inactive'
        ];

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['role_id'] = 'required|exists:roles,id';
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'role_id.required' => 'Please select a role',
            'role_id.exists' => 'Selected role does not exist',
            'amount.required' => 'Amount is required',
            'amount.numeric' => 'Amount must be a number',
            'amount.min' => 'Amount must be greater than 0',
            'status.in' => 'Status must be either active or inactive'
        ];
    }
}