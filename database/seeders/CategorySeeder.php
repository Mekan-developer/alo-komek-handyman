<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // Each entry: ['ru' => …, 'tk' => …, 'children' => [['ru' => …, 'tk' => …], …]]
        $tree = [
            ['ru' => 'Сантехника', 'tk' => 'Santehnika', 'children' => [
                ['ru' => 'Ремонт кранов', 'tk' => 'Kran abatlamak'],
                ['ru' => 'Установка унитаза', 'tk' => 'Unitaz oturtmak'],
                ['ru' => 'Прочистка труб', 'tk' => 'Turba arassalamak'],
                ['ru' => 'Замена смесителя', 'tk' => 'Garyjy çalyşmak'],
            ]],
            ['ru' => 'Электрика', 'tk' => 'Elektrika', 'children' => [
                ['ru' => 'Замена розеток', 'tk' => 'Rozetka çalyşmak'],
                ['ru' => 'Установка люстры', 'tk' => 'Lýustra oturtmak'],
                ['ru' => 'Электромонтаж', 'tk' => 'Elektromontaž'],
                ['ru' => 'Поиск короткого замыкания', 'tk' => 'Gysga utgaşmany gözlemek'],
            ]],
            ['ru' => 'Бытовая техника', 'tk' => 'Durmuş tehnikasy', 'children' => [
                ['ru' => 'Ремонт холодильника', 'tk' => 'Sowadyjyny abatlamak'],
                ['ru' => 'Ремонт стиральной машины', 'tk' => 'Kir ýuwýan maşyny abatlamak'],
                ['ru' => 'Ремонт кондиционера', 'tk' => 'Kondisioneri abatlamak'],
            ]],
            ['ru' => 'Уборка', 'tk' => 'Arassalaýyş', 'children' => [
                ['ru' => 'Генеральная уборка', 'tk' => 'Umumy arassalaýyş'],
                ['ru' => 'Мойка окон', 'tk' => 'Aýna ýuwmak'],
                ['ru' => 'Химчистка мебели', 'tk' => 'Mebeli himiki arassalamak'],
            ]],
            ['ru' => 'Мебель', 'tk' => 'Mebel', 'children' => [
                ['ru' => 'Сборка мебели', 'tk' => 'Mebel ýygnamak'],
                ['ru' => 'Перетяжка дивана', 'tk' => 'Diwan örtmek'],
            ]],
        ];

        foreach ($tree as $parent) {
            $parentCategory = Category::updateOrCreate(
                ['name_ru' => $parent['ru'], 'parent_id' => null],
                ['name_tk' => $parent['tk'], 'is_active' => true]
            );

            foreach ($parent['children'] as $child) {
                Category::updateOrCreate(
                    ['name_ru' => $child['ru'], 'parent_id' => $parentCategory->id],
                    ['name_tk' => $child['tk'], 'is_active' => true]
                );
            }
        }
    }
}
