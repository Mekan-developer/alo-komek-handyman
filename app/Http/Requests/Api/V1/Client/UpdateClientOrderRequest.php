<?php

namespace App\Http\Requests\Api\V1\Client;

use App\Enums\OrderTimeSlot;
use App\Http\Requests\Concerns\ValidatesOrderSchedule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClientOrderRequest extends FormRequest
{
    use ValidatesOrderSchedule;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->prepareOrderSchedule();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'category_id' => ['sometimes', 'integer', 'exists:categories,id'],
            'description' => ['sometimes', 'string', 'min:5', 'max:2000'],
            'client_phone' => ['sometimes', 'string', 'min:6', 'max:20'],
            'client_address' => ['nullable', 'string', 'max:255'],
            'client_lat' => ['sometimes', 'numeric', 'between:-90,90'],
            'client_lng' => ['sometimes', 'numeric', 'between:-180,180'],
            'photos' => ['sometimes', 'array', 'max:4'],
            'photos.*' => ['file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:102400'],
            'remove_photo_ids' => ['sometimes', 'array'],
            'remove_photo_ids.*' => ['integer'],
            'is_urgent' => ['sometimes', 'boolean'],
            'preferred_date' => [
                'sometimes',
                'nullable',
                'date',
                'after_or_equal:today',
                Rule::prohibitedIf(fn (): bool => $this->boolean('is_urgent')),
            ],
            'time_slot' => [
                'sometimes',
                'nullable',
                'string',
                Rule::enum(OrderTimeSlot::class),
                Rule::prohibitedIf(fn (): bool => $this->boolean('is_urgent')),
                $this->timeSlotStillAvailableRule(),
            ],
        ];
    }
}
