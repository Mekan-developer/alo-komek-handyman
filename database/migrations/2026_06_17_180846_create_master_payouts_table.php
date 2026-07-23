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
        Schema::create('master_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_id')->nullable()->constrained()->nullOnDelete();
            $table->string('master_name');
            $table->decimal('amount', 10, 2);
            $table->string('payment_model', 30);
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note', 500)->nullable();
            $table->timestamps();

            $table->index('master_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_payouts');
    }
};
