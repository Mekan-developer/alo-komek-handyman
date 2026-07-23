<?php

namespace App\Http\Requests\Api\V1\Client;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClientOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'city_id' => ['sometimes', 'integer', 'exists:cities,id'],
            'category_id' => ['sometimes', 'integer', 'exists:categories,id'],
            'description' => ['sometimes', 'string', 'min:5', 'max:2000'],
            'client_phone' => ['sometimes', 'string', 'min:6', 'max:20'],
            'client_address' => ['nullable', 'string', 'max:255'],
            'client_lat' => ['sometimes', 'numeric', 'between:-90,90'],
            'client_lng' => ['sometimes', 'numeric', 'between:-180,180'],
            'photos' => ['sometimes', 'array', 'max:4'],
            'photos.*' => ['file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:8192'],
            'remove_photo_ids' => ['sometimes', 'array'],
            'remove_photo_ids.*' => ['integer'],
        ];
    }
}
