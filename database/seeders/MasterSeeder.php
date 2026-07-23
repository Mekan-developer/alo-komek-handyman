<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\City;
use App\Models\Master;
use App\Models\MasterLocation;
use App\PaymentModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MasterSeeder extends Seeder
{
    /**
     * Coordinate centers per city for realistic master locations.
     *
     * @var array<string, array{lat: float, lng: float}>
     */
    private array $cityCenters = [
        'Ашхабад' => ['lat' => 37.9500, 'lng' => 58.3833],
        'Туркменабат' => ['lat' => 39.0742, 'lng' => 63.5800],
        'Дашогуз' => ['lat' => 41.8333, 'lng' => 59.9667],
        'Мары' => ['lat' => 37.6000, 'lng' => 61.8333],
        'Балканабат' => ['lat' => 39.5108, 'lng' => 54.3675],
        'Туркменбаши' => ['lat' => 40.0214, 'lng' => 52.9700],
    ];

    public function run(): void
    {
        $cities = City::all()->keyBy('name');
        $leafCategories = Category::query()->whereNotNull('parent_id')->get();

        if ($cities->isEmpty() || $leafCategories->isEmpty()) {
            return;
        }

        $masters = [
            ['Ашхабад', 'Мерген Аннамырадов', PaymentModel::Percentage, 15.0, true, 30],
            ['Ашхабад', 'Бегенч Овезов', PaymentModel::Percentage, 20.0, true, 30],
            ['Ашхабад', 'Сердар Хыдыров', PaymentModel::FixedPerJob, 50.0, true, 30],
            ['Ашхабад', 'Гурбан Реджепов', PaymentModel::Percentage, 18.0, true, 30],
            ['Туркменабат', 'Атаджан Сапаров', PaymentModel::Percentage, 15.0, true, 30],
            ['Туркменабат', 'Довлет Атаев', PaymentModel::Salary, 1500.0, true, 15],
            ['Туркменабат', 'Максат Нурыев', PaymentModel::Percentage, 22.0, true, 30],
            ['Дашогуз', 'Аман Бердыев', PaymentModel::Percentage, 17.0, true, 30],
            ['Дашогуз', 'Назар Курбанов', PaymentModel::SalaryPercentage, 10.0, false, 30],
            ['Мары', 'Реджеп Овезгельдыев', PaymentModel::Percentage, 20.0, true, 30],
            ['Мары', 'Какаджан Мухаммедов', PaymentModel::Percentage, 25.0, true, 30],
            ['Балканабат', 'Тиркеш Аширов', PaymentModel::Percentage, 18.0, true, 30],
            ['Балканабат', 'Бабамурат Сейидов', PaymentModel::FixedPerJob, 75.0, true, -2],
            ['Туркменбаши', 'Айдогды Ходжаев', PaymentModel::Percentage, 19.0, true, 30],
            ['Туркменбаши', 'Мырат Назаров', PaymentModel::Percentage, 21.0, true, 30],
        ];

        DB::transaction(function () use ($masters, $cities, $leafCategories) {
            foreach ($masters as $index => [$cityName, $name, $paymentModel, $value, $isActive, $expiresInDays]) {
                $city = $cities->get($cityName);

                if ($city === null) {
                    continue;
                }

                $master = Master::updateOrCreate(
                    ['phone' => '+9936'.str_pad((string) (1000000 + $index), 7, '0', STR_PAD_LEFT)],
                    [
                        'city_id' => $city->id,
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

                $center = $this->cityCenters[$cityName] ?? ['lat' => 37.95, 'lng' => 58.38];

                MasterLocation::query()->where('master_id', $master->id)->delete();

                MasterLocation::create([
                    'master_id' => $master->id,
                    'latitude' => $center['lat'] + fake()->randomFloat(4, -0.05, 0.05),
                    'longitude' => $center['lng'] + fake()->randomFloat(4, -0.05, 0.05),
                    'recorded_at' => now()->subMinutes(rand(1, 60)),
                ]);
            }
        });
    }
}
