<?php
// app/Http/Requests/Merchant/StoreTeamMemberRequest.php

namespace App\Http\Requests\Merchant;

use Illuminate\Foundation\Http\FormRequest;

class StoreTeamMemberRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role_id' => 'required|in:9,10',
            'status' => 'sometimes|boolean'
        ];
    }

    public function messages()
    {
        return [
            'role_id.in' => 'The selected role is invalid. Please select Manager or Cashier.'
        ];
    }
}