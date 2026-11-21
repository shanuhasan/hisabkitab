<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && !empty(auth()->user()->tenant_id);
    }

    public function rules(): array
    {
        $tenantId = auth()->user()->tenant_id;
        $customerId = $this->route('customer') instanceof \App\Models\Customer
            ? $this->route('customer')->id
            : $this->route('customer');

        return [
            'name' => ['required', 'string', 'max:150'],
            'phone' => [
                'required',
                'string',
                'regex:/^[6-9]\d{9}$/',
                Rule::unique('customers', 'phone')
                    ->where(function ($query) use ($tenantId) {
                        return $query->where('tenant_id', $tenantId)->whereNull('deleted_at');
                    })
                    ->ignore($customerId),
            ],
            'email' => ['nullable', 'string', 'email', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Customer name is required.',
            'phone.required' => 'Customer mobile number is required.',
            'phone.regex' => 'Please enter a valid 10-digit Indian mobile number.',
            'phone.unique' => 'Another customer in your shop already has this mobile number.',
        ];
    }
}

