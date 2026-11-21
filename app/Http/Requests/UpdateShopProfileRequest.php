<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShopProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->isShopOwner() || auth()->user()->isSuperAdmin());
    }

    public function rules(): array
    {
        $tenantId = auth()->user()->tenant_id;

        return [
            'name' => ['required', 'string', 'max:150'],
            'owner_name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'regex:/^[6-9]\d{9}$/'],
            'email' => ['nullable', 'string', 'email', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
            'gstin' => ['nullable', 'string', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/'],
            'currency' => ['required', 'string', 'max:10'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Shop name is required.',
            'owner_name.required' => 'Owner name is required.',
            'phone.regex' => 'Please enter a valid 10-digit Indian mobile number.',
            'gstin.regex' => 'Please enter a valid 15-character Indian GSTIN (e.g. 07AAAAA0000A1Z5).',
            'logo.max' => 'Shop logo must not exceed 2MB in size.',
        ];
    }
}

