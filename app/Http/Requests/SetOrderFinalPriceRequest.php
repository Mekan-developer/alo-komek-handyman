<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetOrderFinalPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'final_price' => ['required', 'numeric', 'min:0'],
        ];
    }
}
