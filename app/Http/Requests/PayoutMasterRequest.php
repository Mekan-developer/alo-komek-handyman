<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PayoutMasterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            // Omit for a full payout; provide a positive value for a partial one.
            // The amount-vs-balance check lives in RecordMasterPayoutAction.
            'amount' => ['nullable', 'numeric', 'gt:0', 'decimal:0,2'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
