<?php

namespace App\Http\Requests\Api\POS\Auth;

use Illuminate\Foundation\Http\FormRequest;

class PosOtpVerificationRequest extends FormRequest
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
            'otp' => 'required|numeric'
        ];
    }

    public function message(): array{
        return [
            'otp.required' => 'OTP is required',
            'otp.numerice' => 'OTP must be a numeric value'
        ];
    }
}
