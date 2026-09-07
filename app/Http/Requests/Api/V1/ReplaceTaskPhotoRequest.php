<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ReplaceTaskPhotoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * No `type` here on purpose — the slot being replaced already carries it, and
     * letting it change would sidestep the per-type photo limit.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'photo' => ['required', 'file', 'image', 'max:102400'],
        ];
    }
}
