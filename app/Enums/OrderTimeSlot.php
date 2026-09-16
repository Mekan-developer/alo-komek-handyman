<?php

namespace App\Enums;

use Carbon\Carbon;
use Carbon\CarbonInterface;

enum OrderTimeSlot: string
{
    case Slot08010 = '08-10';
    case Slot10012 = '10-12';
    case Slot12014 = '12-14';
    case Slot14016 = '14-16';
    case Slot16018 = '16-18';
    case Slot18020 = '18-20';

    public function label(): string
    {
        return sprintf('%02d:00 - %02d:00', $this->startHour(), $this->endHour());
    }

    public function startHour(): int
    {
        return (int) explode('-', $this->value)[0];
    }

    public function endHour(): int
    {
        return (int) explode('-', $this->value)[1];
    }

    /**
     * Слот доступен, если дата в будущем либо сегодня и текущее время ещё до начала слота.
     */
    public function isAvailableOn(CarbonInterface|string $preferredDate, ?CarbonInterface $now = null): bool
    {
        $now = $now ? Carbon::instance($now) : now();
        $date = Carbon::parse($preferredDate)->startOfDay();
        $today = $now->copy()->startOfDay();

        if ($date->greaterThan($today)) {
            return true;
        }

        if ($date->lessThan($today)) {
            return false;
        }

        return $now->lessThan($date->copy()->setTime($this->startHour(), 0));
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return list<string>
     */
    public static function availableValuesOn(CarbonInterface|string $preferredDate, ?CarbonInterface $now = null): array
    {
        return array_values(array_map(
            fn (self $slot): string => $slot->value,
            array_filter(
                self::cases(),
                fn (self $slot): bool => $slot->isAvailableOn($preferredDate, $now)
            )
        ));
    }
}
