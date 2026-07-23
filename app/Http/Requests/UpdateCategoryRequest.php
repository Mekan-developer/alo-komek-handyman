<?php

namespace App\Http\Requests;

use App\Enums\CategoryIconType;
use App\Support\CategoryIcon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name_ru' => ['required', 'string', 'max:255'],
            'name_tk' => ['required', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'icon_type' => ['nullable', Rule::enum(CategoryIconType::class)],
            'icon' => ['nullable', 'required_if:icon_type,preset', 'string', Rule::in(CategoryIcon::presetKeys())],
            'icon_file' => ['nullable', 'file', 'extensions:svg', 'mimetypes:image/svg+xml,text/xml,text/plain', 'max:64'],
        ];
    }
}
