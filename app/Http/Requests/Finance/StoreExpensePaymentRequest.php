<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class StoreExpensePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reference_no' => 'nullable|string|max:255',
            'account_source' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'paid_at' => 'nullable|date',
        ];
    }
}
