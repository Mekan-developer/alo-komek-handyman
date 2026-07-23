<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained()->restrictOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('master_id')->nullable()->constrained()->nullOnDelete();

            $table->string('status', 20)->default('pending');

            $table->string('client_name');
            $table->string('client_phone', 20);
            $table->text('description');
            $table->string('client_address')->nullable();
            $table->decimal('client_lat', 10, 7);
            $table->decimal('client_lng', 10, 7);

            $table->decimal('final_price', 10, 2)->nullable();

            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();

            $table->timestamps();

            $table->index(['city_id', 'status']);
            $table->index(['master_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
