<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\City;
use App\Models\Master;
use App\Models\Order;
use App\Models\OrderPhoto;
use App\Models\OrderTask;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
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
        $cities = City::all();
        $leafCategories = Category::query()->whereNotNull('parent_id')->get();

        if ($cities->isEmpty() || $leafCategories->isEmpty()) {
            return;
        }

        foreach ($cities as $city) {
            $center = $this->cityCenters[$city->name] ?? ['lat' => 37.95, 'lng' => 58.38];
            $masters = Master::query()
                ->where('city_id', $city->id)
                ->where('is_active', true)
                ->get();

            // Pending — без мастера
            Order::factory()
                ->count(4)
                ->forCity($city, $center['lat'], $center['lng'])
                ->state(fn () => ['category_id' => $leafCategories->random()->id])
                ->create();

            if ($masters->isEmpty()) {
                continue;
            }

            // Assigned
            Order::factory()
                ->count(2)
                ->forCity($city, $center['lat'], $center['lng'])
                ->forMaster($masters->random())
                ->state(fn () => ['category_id' => $leafCategories->random()->id])
                ->assigned()
                ->create();

            // In progress — с задачами
            Order::factory()
                ->count(2)
                ->forCity($city, $center['lat'], $center['lng'])
                ->forMaster($masters->random())
                ->state(fn () => ['category_id' => $leafCategories->random()->id])
                ->inProgress()
                ->has(OrderTask::factory()->count(3), 'tasks')
                ->create();

            // Completed — с задачами и фото
            Order::factory()
                ->count(3)
                ->forCity($city, $center['lat'], $center['lng'])
                ->forMaster($masters->random())
                ->state(fn () => ['category_id' => $leafCategories->random()->id])
                ->completed()
                ->has(
                    OrderTask::factory()
                        ->count(3)
                        ->state([
                            'before_status' => OrderTask::STATUS_DONE,
                            'after_status' => OrderTask::STATUS_DONE,
                        ]),
                    'tasks'
                )
                ->has(OrderPhoto::factory()->count(2), 'photos')
                ->create();

            // Cancelled
            Order::factory()
                ->count(1)
                ->forCity($city, $center['lat'], $center['lng'])
                ->state(fn () => ['category_id' => $leafCategories->random()->id])
                ->cancelled()
                ->create();
        }
    }
}
