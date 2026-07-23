<?php

namespace App\Http\Requests;

use App\PaymentModel;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMasterRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20', 'unique:masters,phone'],
            'payment_model' => ['required', Rule::enum(PaymentModel::class)],
            'payment_value' => ['required_if:payment_model,percentage,salary_percentage', 'nullable', 'numeric', 'min:0'],
            'monthly_salary' => ['required_if:payment_model,salary,salary_percentage', 'nullable', 'numeric', 'min:0'],
            'access_expires_at' => ['nullable', 'date', 'after:now'],
            'is_active' => ['required', 'boolean'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $model = $this->input('payment_model');

        $merge = [];

        if (! in_array($model, [PaymentModel::Percentage->value, PaymentModel::SalaryPercentage->value], true)) {
            $merge['payment_value'] = $this->input('payment_value') ?? 0;
        }

        if (! in_array($model, [PaymentModel::Salary->value, PaymentModel::SalaryPercentage->value], true)) {
            $merge['monthly_salary'] = $this->input('monthly_salary') ?? 0;
        }

        $this->merge($merge);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $percentModels = [PaymentModel::Percentage->value, PaymentModel::SalaryPercentage->value];

            if (in_array($this->input('payment_model'), $percentModels, true) && (float) $this->input('payment_value') > 100) {
                $validator->errors()->add('payment_value', __('masters.errors.percent_max'));
            }
        });
    }
}
