<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'owner_name' => ['required', 'string', 'max:100'],
            'shop_name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'regex:/^[6-9]\d{9}$/', 'unique:users,phone'],
            'email' => ['nullable', 'string', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'terms' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Please enter a valid 10-digit Indian mobile number (e.g. 9876543210).',
            'phone.unique' => 'This mobile number is already registered. Please log in instead.',
            'email.unique' => 'This email address is already in use.',
            'password.min' => 'Password must be at least 8 characters long.',
            'password.confirmed' => 'The password confirmation does not match.',
            'terms.accepted' => 'You must accept the terms of service to create your shop account.',
        ];
    }
}

