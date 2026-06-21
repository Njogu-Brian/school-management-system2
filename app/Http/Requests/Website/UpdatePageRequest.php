<?php

namespace App\Http\Requests\Website;

use App\Models\Website\Page;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $pageId = $this->route('page')?->id ?? $this->route('page');

        return [
            'name' => 'required|string|max:255',
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('pages', 'slug')->ignore($pageId)],
            'title' => 'required|string|max:255',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:2000',
            'status' => ['required', Rule::in([Page::STATUS_DRAFT, Page::STATUS_PUBLISHED, Page::STATUS_ARCHIVED])],
            'is_homepage' => 'nullable|boolean',
            'published_at' => 'nullable|date',
        ];
    }
}
