<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetOrderTaskPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'price' => ['present', 'nullable', 'numeric', 'min:0', 'max:9999999.99'],
        ];
    }
}
