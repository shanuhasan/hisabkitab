<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReverseTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->isShopOwner() || auth()->user()->isSuperAdmin());
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:5', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Please provide a valid explanation/reason for reversing this entry.',
            'reason.min' => 'Reversal reason must be at least 5 characters long for audit records.',
        ];
    }
}

