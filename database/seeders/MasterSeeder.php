<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Master;
use App\Models\MasterLocation;
use App\PaymentModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MasterSeeder extends Seeder
{
    /** Ashgabat centre — masters are scattered around it. */
    private const CENTER_LAT = 37.9500;

    private const CENTER_LNG = 58.3833;

    public function run(): void
    {
        $leafCategories = Category::query()->whereNotNull('parent_id')->get();

        if ($leafCategories->isEmpty()) {
            return;
        }

        $masters = [
            ['Мерген Аннамырадов', PaymentModel::Percentage, 15.0, true, 30],
            ['Бегенч Овезов', PaymentModel::Percentage, 20.0, true, 30],
            ['Сердар Хыдыров', PaymentModel::FixedPerJob, 50.0, true, 30],
            ['Гурбан Реджепов', PaymentModel::Percentage, 18.0, true, 30],
            ['Атаджан Сапаров', PaymentModel::Percentage, 15.0, true, 30],
            ['Довлет Атаев', PaymentModel::Salary, 1500.0, true, 15],
            ['Максат Нурыев', PaymentModel::Percentage, 22.0, true, 30],
            ['Аман Бердыев', PaymentModel::Percentage, 17.0, true, 30],
            ['Назар Курбанов', PaymentModel::SalaryPercentage, 10.0, false, 30],
            ['Реджеп Овезгельдыев', PaymentModel::Percentage, 20.0, true, 30],
            ['Какаджан Мухаммедов', PaymentModel::Percentage, 25.0, true, 30],
            ['Тиркеш Аширов', PaymentModel::Percentage, 18.0, true, 30],
            ['Бабамурат Сейидов', PaymentModel::FixedPerJob, 75.0, true, -2],
            ['Айдогды Ходжаев', PaymentModel::Percentage, 19.0, true, 30],
            ['Мырат Назаров', PaymentModel::Percentage, 21.0, true, 30],
        ];

        DB::transaction(function () use ($masters, $leafCategories) {
            foreach ($masters as $index => [$name, $paymentModel, $value, $isActive, $expiresInDays]) {
                $master = Master::updateOrCreate(
                    ['phone' => '+9936'.str_pad((string) (1000000 + $index), 7, '0', STR_PAD_LEFT)],
                    [
                        'name' => $name,
                        'payment_model' => $paymentModel,
                        'payment_value' => $value,
                        'balance' => fake()->randomFloat(2, 0, 500),
                        'access_expires_at' => now()->addDays($expiresInDays),
                        'is_active' => $isActive,
                        'photo' => null,
                    ]
                );

                $master->categories()->sync(
                    $leafCategories->random(rand(2, 4))->pluck('id')->all()
                );

                MasterLocation::query()->where('master_id', $master->id)->delete();

                MasterLocation::create([
                    'master_id' => $master->id,
                    'latitude' => self::CENTER_LAT + fake()->randomFloat(4, -0.05, 0.05),
                    'longitude' => self::CENTER_LNG + fake()->randomFloat(4, -0.05, 0.05),
                    'recorded_at' => now()->subMinutes(rand(1, 60)),
                ]);
            }
        });
    }
}
