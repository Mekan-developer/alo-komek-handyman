<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetOrderDiscountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'discount_percent' => ['present', 'nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
