<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && !empty(auth()->user()->tenant_id);
    }

    public function rules(): array
    {
        $tenantId = auth()->user()->tenant_id;

        return [
            'name' => ['required', 'string', 'max:150'],
            'phone' => [
                'required',
                'string',
                'regex:/^[6-9]\d{9}$/',
                Rule::unique('customers', 'phone')->where(function ($query) use ($tenantId) {
                    return $query->where('tenant_id', $tenantId)->whereNull('deleted_at');
                }),
            ],
            'email' => ['nullable', 'string', 'email', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'opening_balance_type' => ['nullable', 'in:credit,payment'],
            'opening_balance' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Customer name is required.',
            'phone.required' => 'Customer mobile number is required.',
            'phone.regex' => 'Please enter a valid 10-digit Indian mobile number (e.g. 9876543210).',
            'phone.unique' => 'A customer with this mobile number already exists in your shop.',
            'credit_limit.numeric' => 'Credit limit must be a valid number.',
            'opening_balance.numeric' => 'Opening balance must be a valid number.',
        ];
    }
}

