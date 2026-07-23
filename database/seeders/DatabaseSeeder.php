<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            ['name' => 'Admin', 'password' => bcrypt('password')]
        );

        $this->call([
            OblastSeeder::class,
            CitySeeder::class,
            CategorySeeder::class,
            MasterSeeder::class,
            MasterLocationSeeder::class,
            OrderSeeder::class,
        ]);
    }
}
