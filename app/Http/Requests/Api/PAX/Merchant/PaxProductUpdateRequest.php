<?php

namespace App\Http\Requests\Api\PAX\Merchant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaxProductUpdateRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'string'],
            'existing_product_image_id' => ['nullable'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'The product ID is required.',
            'product_id.string' => 'The product ID must be a string.',
            
            'image.image' => 'The uploaded file must be an image.',
            'image.mimes' => 'The uploaded image must be a file of type: jpeg, png, jpg, gif, svg.',
            'image.max' => 'The uploaded image may not be greater than 2MB.',
        ];
    }
}
