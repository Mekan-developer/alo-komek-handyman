<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('cities', 'name')->ignore($this->route('city'))],
            'oblast_id' => ['nullable', 'integer', 'exists:oblasts,id'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
