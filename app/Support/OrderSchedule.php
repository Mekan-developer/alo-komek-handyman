<?php

namespace App\Support;

use App\Repositories\SettingRepository;

class OrderSchedule
{
    /**
     * Срочность = «здесь и сейчас»: дата и слот обнуляются, fee фиксируется из настроек.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function applyUrgency(array $data, SettingRepository $settings): array
    {
        $isUrgent = (bool) ($data['is_urgent'] ?? false);
        $data['is_urgent'] = $isUrgent;

        if ($isUrgent) {
            $data['preferred_date'] = null;
            $data['time_slot'] = null;
            $data['urgency_fee'] = round((float) ($settings->get('order_urgency_fee') ?? '20'), 2);

            return $data;
        }

        $data['urgency_fee'] = null;

        if (array_key_exists('time_slot', $data) && $data['time_slot'] === '') {
            $data['time_slot'] = null;
        }

        return $data;
    }
}
