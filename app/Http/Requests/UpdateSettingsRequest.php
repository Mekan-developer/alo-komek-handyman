<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'master_app_rules' => ['nullable', 'string'],
            'client_app_rules' => ['nullable', 'string'],
            'master_call_out_fee_note_ru' => ['nullable', 'string', 'max:500'],
            'master_call_out_fee_note_tk' => ['nullable', 'string', 'max:500'],
        ];
    }
}
