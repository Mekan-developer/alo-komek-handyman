<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->date('preferred_date')->nullable()->after('description');
            $table->string('time_slot', 10)->nullable()->after('preferred_date');
            $table->boolean('is_urgent')->default(false)->after('time_slot');
            $table->decimal('urgency_fee', 10, 2)->nullable()->after('is_urgent');
            $table->decimal('cancel_fee', 10, 2)->nullable()->after('cancel_reason');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'preferred_date',
                'time_slot',
                'is_urgent',
                'urgency_fee',
                'cancel_fee',
            ]);
        });
    }
};
