<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Task photos moved to the `order_task_photos` table, which supports several
     * photos per type. The single-photo columns are no longer written or read.
     */
    public function up(): void
    {
        Schema::table('order_tasks', function (Blueprint $table) {
            $table->dropColumn([
                'before_photo_path',
                'after_photo_path',
                'before_status',
                'after_status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('order_tasks', function (Blueprint $table) {
            $table->string('before_photo_path')->nullable();
            $table->string('after_photo_path')->nullable();
            $table->string('before_status', 20)->default('pending');
            $table->string('after_status', 20)->default('pending');
        });
    }
};
