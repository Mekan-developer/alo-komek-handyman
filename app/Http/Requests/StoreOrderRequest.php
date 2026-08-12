<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Пустые строки от FormData приводим к null, чтобы `client_id`
     * корректно определялся как «клиент не выбран».
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'client_id' => filled($this->input('client_id')) ? $this->input('client_id') : null,
        ]);
    }

    /** Клиент выбран из существующих — имя и телефон берём из его карточки. */
    private function hasExistingClient(): bool
    {
        return filled($this->input('client_id'));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'client_name' => [Rule::requiredIf(fn (): bool => ! $this->hasExistingClient()), 'nullable', 'string', 'max:255'],
            'client_phone' => [Rule::requiredIf(fn (): bool => ! $this->hasExistingClient()), 'nullable', 'string', 'max:20'],
            'description' => ['required', 'string', 'max:5000'],
            'client_address' => ['nullable', 'string', 'max:500'],
            'client_lat' => ['required', 'numeric', 'between:-90,90'],
            'client_lng' => ['required', 'numeric', 'between:-180,180'],
            'photos' => ['nullable', 'array', 'max:4'],
            'photos.*' => ['file', 'image', 'max:8192'],
        ];
    }
}
