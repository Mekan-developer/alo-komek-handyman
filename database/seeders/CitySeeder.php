<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;

class CitySeeder extends Seeder
{
    public function run(): void
    {
        // oblast_id: 1=Aşgabat, 2=Ahal, 3=Mary, 4=Daşoguz, 5=Balkan, 6=Lebap
        $cities = [
            ['name' => 'Ашхабад', 'oblast_id' => 1],

            ['name' => 'Аннау', 'oblast_id' => 2],
            ['name' => 'Бахарлы', 'oblast_id' => 2],
            ['name' => 'Теджен', 'oblast_id' => 2],

            ['name' => 'Мары', 'oblast_id' => 3],
            ['name' => 'Байрамали', 'oblast_id' => 3],
            ['name' => 'Йолётен', 'oblast_id' => 3],

            ['name' => 'Дашогуз', 'oblast_id' => 4],
            ['name' => 'Куняургенч', 'oblast_id' => 4],
            ['name' => 'Акдепе', 'oblast_id' => 4],

            ['name' => 'Балканабат', 'oblast_id' => 5],
            ['name' => 'Туркменбаши', 'oblast_id' => 5],
            ['name' => 'Гарабогаз', 'oblast_id' => 5],

            ['name' => 'Туркменабат', 'oblast_id' => 6],
            ['name' => 'Сейди', 'oblast_id' => 6],
            ['name' => 'Газачак', 'oblast_id' => 6],
        ];

        foreach ($cities as $data) {
            City::updateOrCreate(
                ['name' => $data['name']],
                ['is_active' => true, 'oblast_id' => $data['oblast_id']],
            );
        }
    }
}
