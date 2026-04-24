<?php

namespace App\Http\Requests\Api\POS\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyForgotPasswordOtpRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|string|size:6'
        ];
    }

    public function messages()
    {
        return [
            'email.required' => 'Email address is required',
            'email.email' => 'Please provide a valid email address',
            'email.exists' => 'No account found with this email address',
            'otp.required' => 'OTP is required',
            'otp.size' => 'OTP must be 6 digits'
        ];
    }
}
