<?php

namespace App\Http\Requests\Api\Location;

use Illuminate\Foundation\Http\FormRequest;

class GetStatesRequest extends FormRequest
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
            'country_id' => 'required|integer|exists:countries,country_id'
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'country_id.required' => 'Country ID is required',
            'country_id.integer' => 'Country ID must be an integer',
            'country_id.exists' => 'The selected country does not exist'
        ];
    }
}
