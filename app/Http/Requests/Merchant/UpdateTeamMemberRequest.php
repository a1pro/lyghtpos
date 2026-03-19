<?php
// app/Http/Requests/Merchant/UpdateTeamMemberRequest.php

namespace App\Http\Requests\Merchant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTeamMemberRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $teamMember = $this->route('teamMember');
        
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($teamMember->id)
            ],
            'password' => 'nullable|string|min:8',
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