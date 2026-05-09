<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'source_type' => 'required|string|max:50',
            'vendor_id' => 'nullable|exists:vendors,id',
            'expense_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:expense_date',
            'currency' => 'required|string|size:3',
            'notes' => 'nullable|string|max:2000',
            'lines' => 'required|array|min:1',
            'lines.*.category_id' => 'required|exists:expense_categories,id',
            'lines.*.department' => 'nullable|string|max:255',
            'lines.*.cost_center' => 'nullable|string|max:255',
            'lines.*.description' => 'required|string|max:1000',
            'lines.*.qty' => 'required|numeric|min:0.01',
            'lines.*.unit_cost' => 'required|numeric|min:0',
            'lines.*.tax_rate' => 'nullable|numeric|min:0|max:100',
        ];
    }
}
