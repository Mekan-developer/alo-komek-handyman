<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'master_id' => ['nullable', 'integer', 'exists:masters,id'],
            'rating' => ['nullable', 'integer', 'between:1,5'],
            'only_with_comment' => ['nullable', 'boolean'],
            'search' => ['nullable', 'string', 'max:255'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ];
    }

    /**
     * Only the filters that were actually set — blank query-string values must
     * not reach the repository, or they would filter everything out.
     *
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        return array_filter(
            $this->safe()->only(['master_id', 'rating', 'only_with_comment', 'search', 'date_from', 'date_to']),
            fn ($value) => $value !== null && $value !== '' && $value !== false,
        );
    }
}
