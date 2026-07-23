<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_task_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_task_id')->constrained('order_tasks')->cascadeOnDelete();
            $table->enum('type', ['before', 'after']);
            $table->string('path');
            $table->string('status', 20)->default('pending');
            $table->timestamps();

            $table->index(['order_task_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_task_photos');
    }
};
