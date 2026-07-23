<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:cities,name'],
            'oblast_id' => ['nullable', 'integer', 'exists:oblasts,id'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
