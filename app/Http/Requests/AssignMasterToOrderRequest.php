<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignMasterToOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'master_id' => ['required', 'integer', 'exists:masters,id'],
            'change_reason' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
