<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $adminEmail = config('app.admin_email');
        $adminPassword = config('app.admin_password');

        if ($adminEmail && $adminPassword) {
            User::updateOrCreate(
                ['email' => $adminEmail],
                ['name' => 'Admin', 'password' => bcrypt($adminPassword)]
            );
        } else {
            $this->command?->warn('ADMIN_EMAIL / ADMIN_PASSWORD not set — skipping admin user creation.');
        }

        $this->call([
            CategorySeeder::class,
            MasterSeeder::class,
            MasterLocationSeeder::class,
            OrderSeeder::class,
        ]);
    }
}
