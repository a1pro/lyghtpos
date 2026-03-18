<?php

namespace App\Http\Requests\Merchant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'barcode' => ['nullable', 'string', 'max:100'],
            'price' => ['required', 'numeric', 'min:0'],
            'quantity' => ['required', 'integer', 'min:0'],
            'unlimited_stock' => ['sometimes', 'boolean'],
            'status' => ['sometimes', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'categories' => ['required', 'array', 'min:1'],
            'categories.*' => ['required', 'string'],
            'trade_type' => ['nullable', 'string', Rule::in(['tradeTypeOffer', 'tradeTypeDemand'])],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => __('validation.required', ['attribute' => 'name']),
            'name.max' => __('validation.max', ['attribute' => 'name']),
            'price.required' => __('validation.required', ['attribute' => 'price']),
            'price.numeric' => __('validation.numeric', ['attribute' => 'price']),
            'price.min' => __('validation.min', ['attribute' => 'price']),
            'quantity.required' => __('validation.required', ['attribute' => 'quantity']),
            'quantity.integer' => __('validation.integer', ['attribute' => 'quantity']),
            'image.image' => __('validation.image', ['attribute' => 'image']),
            'image.max' => __('validation.max.file', ['attribute' => 'image', 'max' => 2048]),
            'categories.required' => 'Please select a category.',
            'categories.min' => 'Please select at least one category.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'unlimited_stock' => $this->boolean('unlimited_stock', false),
            'status' => $this->boolean('status', true),
        ]);
    }
}