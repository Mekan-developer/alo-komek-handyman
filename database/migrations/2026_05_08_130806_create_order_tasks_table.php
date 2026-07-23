<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('before_photo_path')->nullable();
            $table->string('after_photo_path')->nullable();
            $table->string('before_status', 20)->default('pending');
            $table->string('after_status', 20)->default('pending');
            $table->timestamps();

            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_tasks');
    }
};
