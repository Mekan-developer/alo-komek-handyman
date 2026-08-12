<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pending_otps', function (Blueprint $table) {
            $table->id();
            $table->string('phone')->index();
            $table->string('code', 6);
            $table->string('recipient_type');
            $table->string('recipient_name')->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_otps');
    }
};
