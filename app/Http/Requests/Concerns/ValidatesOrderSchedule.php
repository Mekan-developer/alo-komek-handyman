<?php

namespace App\Http\Requests\Concerns;

use App\Enums\OrderTimeSlot;
use Carbon\Carbon;
use Closure;
use Illuminate\Validation\Rule;

trait ValidatesOrderSchedule
{
    protected function prepareOrderSchedule(): void
    {
        if ($this->has('is_urgent')) {
            $this->merge([
                'is_urgent' => filter_var($this->input('is_urgent'), FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        if ($this->boolean('is_urgent')) {
            $this->merge([
                'preferred_date' => null,
                'time_slot' => null,
            ]);

            return;
        }

        if ($this->exists('time_slot') && in_array($this->input('time_slot'), [null, '', 'flexible'], true)) {
            $this->merge(['time_slot' => null]);
        }
    }

    /**
     * @return array<string, list<mixed>>
     */
    protected function orderScheduleRules(bool $requireFutureDate = true): array
    {
        $dateRules = [
            Rule::requiredIf(fn (): bool => ! $this->boolean('is_urgent')),
            'nullable',
            'date',
        ];

        if ($requireFutureDate) {
            $dateRules[] = 'after_or_equal:today';
        }

        return [
            'is_urgent' => ['sometimes', 'boolean'],
            'preferred_date' => $dateRules,
            'time_slot' => [
                'nullable',
                'string',
                Rule::enum(OrderTimeSlot::class),
                $this->timeSlotStillAvailableRule(),
            ],
        ];
    }

    protected function timeSlotStillAvailableRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if ($this->boolean('is_urgent') || $value === null || $value === '') {
                return;
            }

            $preferredDate = $this->input('preferred_date');

            if (! is_string($preferredDate) || $preferredDate === '') {
                return;
            }

            $slot = OrderTimeSlot::tryFrom((string) $value);

            if ($slot === null) {
                return;
            }

            if (! $slot->isAvailableOn(Carbon::parse($preferredDate))) {
                $fail(__('orders.validation.time_slot_passed'));
            }
        };
    }
}
