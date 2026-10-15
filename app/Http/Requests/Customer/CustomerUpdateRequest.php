<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class CustomerUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:20'],
            'last_name' => ['required', 'string', 'max:20'],
            // 'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            // 'address' => ['nullable', 'string', 'max:500'],
            // 'avatar' => ['nullable', 'image', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => __('First name is required'),
            'first_name.string' => __('First name must be text'),
            'first_name.max' => __('First name may not be greater than 20 characters'),

            'last_name.required' => __('Last name is required'),
            'last_name.string' => __('Last name must be text'),
            'last_name.max' => __('Last name may not be greater than 20 characters'),

            'phone.max' => __('Phone may not be greater than 20 characters'),
        ];
    }
}
