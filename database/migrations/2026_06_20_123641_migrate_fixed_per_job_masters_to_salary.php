<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('masters')
            ->where('payment_model', 'fixed_per_job')
            ->update([
                'payment_model' => 'salary',
                'payment_value' => 0,
            ]);
    }

    public function down(): void
    {
        // Intentionally irreversible — no way to know original payment_value
    }
};
