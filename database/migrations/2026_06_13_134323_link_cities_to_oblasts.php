<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('cities')->where('name', 'Ашхабад')->update(['oblast_id' => 1]);     // Aşgabat
        DB::table('cities')->where('name', 'Туркменабат')->update(['oblast_id' => 6]); // Lebap
        DB::table('cities')->where('name', 'Дашогуз')->update(['oblast_id' => 4]);     // Daşoguz
        DB::table('cities')->where('name', 'Мары')->update(['oblast_id' => 3]);        // Mary
        DB::table('cities')->where('name', 'Балканабат')->update(['oblast_id' => 5]);  // Balkan
        DB::table('cities')->where('name', 'Туркменбаши')->update(['oblast_id' => 5]); // Balkan
    }

    public function down(): void
    {
        DB::table('cities')->update(['oblast_id' => null]);
    }
};
