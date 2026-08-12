<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Master;
use App\Models\Order;
use App\Models\OrderPhoto;
use App\Models\OrderTask;
use App\Models\OrderTaskPhoto;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /** Ashgabat centre — the service covers this city only. */
    private const CENTER_LAT = 37.9500;

    private const CENTER_LNG = 58.3833;

    public function run(): void
    {
        $leafCategories = Category::query()->whereNotNull('parent_id')->get();

        if ($leafCategories->isEmpty()) {
            return;
        }

        $masters = Master::query()->where('is_active', true)->get();

        // Pending — без мастера
        Order::factory()
            ->count(4)
            ->around(self::CENTER_LAT, self::CENTER_LNG)
            ->state(fn () => ['category_id' => $leafCategories->random()->id])
            ->create();

        if ($masters->isEmpty()) {
            return;
        }

        // Assigned
        Order::factory()
            ->count(2)
            ->around(self::CENTER_LAT, self::CENTER_LNG)
            ->forMaster($masters->random())
            ->state(fn () => ['category_id' => $leafCategories->random()->id])
            ->assigned()
            ->create();

        // In progress — с задачами
        Order::factory()
            ->count(2)
            ->around(self::CENTER_LAT, self::CENTER_LNG)
            ->forMaster($masters->random())
            ->state(fn () => ['category_id' => $leafCategories->random()->id])
            ->inProgress()
            ->has(OrderTask::factory()->count(3), 'tasks')
            ->create();

        // Completed — с задачами и фото
        Order::factory()
            ->count(3)
            ->around(self::CENTER_LAT, self::CENTER_LNG)
            ->forMaster($masters->random())
            ->state(fn () => ['category_id' => $leafCategories->random()->id])
            ->completed()
            ->has(
                OrderTask::factory()
                    ->count(3)
                    ->has(OrderTaskPhoto::factory()->before(), 'photos')
                    ->has(OrderTaskPhoto::factory()->after(), 'photos'),
                'tasks'
            )
            ->has(OrderPhoto::factory()->count(2), 'photos')
            ->create();

        // Cancelled
        Order::factory()
            ->count(1)
            ->around(self::CENTER_LAT, self::CENTER_LNG)
            ->state(fn () => ['category_id' => $leafCategories->random()->id])
            ->cancelled()
            ->create();
    }
}
