<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class StoreVoucherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'expense_id' => 'required|exists:expenses,id',
            'payee' => 'nullable|string|max:255',
            'payment_method' => 'nullable|string|max:100',
            'payment_date' => 'nullable|date',
            'amount' => 'nullable|numeric|min:0.01',
        ];
    }
}
