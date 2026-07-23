<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_locations', function (Blueprint $table) {
            $table->foreignId('order_id')->nullable()->after('master_id')->constrained()->nullOnDelete();
            $table->index(['order_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::table('master_locations', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->dropIndex(['order_id', 'recorded_at']);
            $table->dropColumn('order_id');
        });
    }
};
