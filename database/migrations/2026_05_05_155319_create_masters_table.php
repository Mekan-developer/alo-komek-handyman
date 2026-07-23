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
        Schema::create('masters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('phone', 20)->unique();
            $table->string('payment_model', 30)->default('percentage');
            $table->decimal('payment_value', 10, 2)->default(0);
            $table->decimal('balance', 10, 2)->default(0);
            $table->timestamp('access_expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('photo')->nullable();
            $table->timestamps();

            $table->index('city_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('masters');
    }
};
