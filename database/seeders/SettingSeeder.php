<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'master_app_rules_ru' => '',
            'master_app_rules_tk' => '',
            'client_app_rules_ru' => '',
            'client_app_rules_tk' => '',
            'order_urgency_fee' => '20',
            'order_cancel_fee' => '0',
            'master_app_download_url' => '',
        ];

        foreach ($defaults as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
