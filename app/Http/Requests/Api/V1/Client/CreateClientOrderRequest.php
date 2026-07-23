<?php

namespace App\Http\Requests\Api\V1\Client;

use Illuminate\Foundation\Http\FormRequest;

class CreateClientOrderRequest extends FormRequest
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
            'description' => ['required', 'string', 'min:5', 'max:2000'],
            'client_phone' => ['required', 'string', 'min:6', 'max:20'],
            'client_address' => ['nullable', 'string', 'max:255'],
            'client_lat' => ['required', 'numeric', 'between:-90,90'],
            'client_lng' => ['required', 'numeric', 'between:-180,180'],
            'photos' => ['nullable', 'array', 'max:4'],
            'photos.*' => ['file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:8192'],
        ];
    }
}
