<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesOrderSchedule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderScheduleRequest extends FormRequest
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
        return $this->orderScheduleRules();
    }
}
