<?php

namespace App\Http\Requests\Website;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEnquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'parent_name' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'email' => 'required|email|max:255',
            'child_age' => 'nullable|string|max:50',
            'grade_interest' => 'nullable|string|max:100',
            'message' => 'nullable|string|max:5000',
            'source' => 'nullable|string|max:100',
        ];
    }
}
