<?php

namespace Database\Seeders;

use App\Models\Oblast;
use Illuminate\Database\Seeder;

class OblastSeeder extends Seeder
{
    public function run(): void
    {
        $oblasts = [
            'Aşgabat',
            'Ahal',
            'Mary',
            'Daşoguz',
            'Balkan',
            'Lebap',
        ];

        foreach ($oblasts as $name) {
            Oblast::firstOrCreate(['name' => $name], ['is_active' => true]);
        }
    }
}
