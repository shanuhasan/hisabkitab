<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && !empty(auth()->user()->tenant_id);
    }

    public function rules(): array
    {
        $tenantId = auth()->user()->tenant_id;

        return [
            'customer_id' => [
                'required',
                'integer',
                Rule::exists('customers', 'id')->where(function ($query) use ($tenantId) {
                    return $query->where('tenant_id', $tenantId)->whereNull('deleted_at');
                }),
            ],
            'type' => ['required', 'in:credit,payment'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999999.99'],
            'transaction_date' => ['required', 'date', 'before_or_equal:today'],
            'payment_mode' => ['nullable', 'in:cash,upi,bank_transfer,cheque,other'],
            'description' => ['nullable', 'string', 'max:255'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'bill_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:4096'],
            'allow_advance' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'Please select a customer for this transaction.',
            'customer_id.exists' => 'The selected customer is invalid or belongs to another shop.',
            'amount.required' => 'Transaction amount is required.',
            'amount.min' => 'Amount must be greater than zero (minimum ₹ 0.01).',
            'transaction_date.before_or_equal' => 'Transaction date cannot be in the future.',
            'bill_image.max' => 'Bill photo cannot exceed 4MB in size.',
        ];
    }
}

