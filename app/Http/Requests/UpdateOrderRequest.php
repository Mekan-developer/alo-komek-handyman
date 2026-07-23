<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'city_id' => ['required', 'integer', 'exists:cities,id'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'client_name' => ['required', 'string', 'max:255'],
            'client_phone' => ['required', 'string', 'max:20'],
            'description' => ['required', 'string', 'max:5000'],
            'client_address' => ['nullable', 'string', 'max:500'],
            'client_lat' => ['required', 'numeric', 'between:-90,90'],
            'client_lng' => ['required', 'numeric', 'between:-180,180'],
        ];
    }
}
