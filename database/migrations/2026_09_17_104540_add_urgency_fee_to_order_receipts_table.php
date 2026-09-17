<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_receipts', function (Blueprint $table) {
            $table->decimal('urgency_fee', 10, 2)->default(0)->after('discount_amount');
        });
    }

    public function down(): void
    {
        Schema::table('order_receipts', function (Blueprint $table) {
            $table->dropColumn('urgency_fee');
        });
    }
};
