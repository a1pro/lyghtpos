<?php

namespace App\Http\Requests\Api\PAX\Auth;

use Illuminate\Foundation\Http\FormRequest;

class PaxUpdateMerchantAddressRequest extends FormRequest
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
            'address' => 'required|string|max:500',
            'city' => 'required|integer|exists:cities,id',
            'state' => 'required|integer|exists:states,id',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|integer|exists:countries,id',
        ];
    }


    public function messages(): array
    {
        return [
            'address.required' => 'Address is required',
            'address.string' => 'Address must be a string',
            'address.max' => 'Address must not exceed 500 characters',

            'city.required' => 'City is required',
            'city.integer' => 'City must be a valid ID',
            'city.exists' => 'Selected city does not exist in our records',

            'state.required' => 'State is required',
            'state.integer' => 'State must be a valid ID',
            'state.exists' => 'Selected state does not exist in our records',

            'postal_code.required' => 'Postal code is required',
            'postal_code.string' => 'Postal code must be a string',
            'postal_code.max' => 'Postal code must not exceed 20 characters',

            'country.required' => 'Country is required',
            'country.integer' => 'Country must be a valid ID',
            'country.exists' => 'Selected country does not exist in our records',
        ];
    }
}
