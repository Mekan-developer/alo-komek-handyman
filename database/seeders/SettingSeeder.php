<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'master_app_rules' => '',
            'client_app_rules' => '',
            'order_urgency_fee' => '20',
            'order_cancel_fee' => '0',
            'master_app_download_url' => '',
        ];

        foreach ($defaults as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
