<?php
// app/Http/Requests/Admin/UpdatePayoutStatusRequest.php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePayoutStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'status' => 'required|in:processing,completed,failed',
            'transaction_id' => 'required_if:status,completed|nullable|string|max:255',
            'failure_reason' => 'required_if:status,failed|nullable|string|max:500',
            'processed_notes' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Please select a status',
            'status.in' => 'Invalid status selected',
            'transaction_id.required_if' => 'Please enter Transaction ID for completed payout',
            'failure_reason.required_if' => 'Please provide a reason for failure',
        ];
    }
}