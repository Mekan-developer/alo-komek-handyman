<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('number')->unique();

            // Снапшот участников на момент выдачи чека
            $table->string('client_name');
            $table->string('client_phone')->nullable();
            $table->string('master_name')->nullable();
            $table->string('master_phone')->nullable();
            $table->string('category_name')->nullable();

            // Снапшот сумм: цены задач потом можно править, чек остаётся прежним
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);

            $table->timestamp('issued_at');
            $table->timestamps();

            $table->index('issued_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_receipts');
    }
};
