<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Client;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        $cities = City::all();

        if ($cities->isEmpty()) {
            return;
        }

        Client::factory()
            ->count(5)
            ->state(fn () => ['city_id' => $cities->random()->id])
            ->create();
    }
}
