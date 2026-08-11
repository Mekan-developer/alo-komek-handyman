<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The service now operates in Ashgabat only, so the whole geography hierarchy
 * (oblast → city, plus the unused region directory) is dropped along with every
 * `city_id` reference on masters, clients and orders.
 *
 * Records tied to a city other than Ashgabat are deleted — cascading foreign keys
 * take care of order photos/tasks/reviews and master locations/categories.
 *
 * The `down()` method restores the schema only; the deleted rows are gone for good.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cities')) {
            $this->purgeNonAshgabatRecords();
        }

        // dropForeign must accompany dropColumn: on SQLite it is what makes the
        // builder rebuild the table instead of issuing an ALTER that trips over
        // the lingering foreign key definition.
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['city_id', 'status']);
            $table->dropForeign(['city_id']);
            $table->dropColumn('city_id');
        });

        Schema::table('masters', function (Blueprint $table) {
            $table->dropIndex(['city_id']);
            $table->dropForeign(['city_id']);
            $table->dropColumn('city_id');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex(['city_id']);
            $table->dropForeign(['city_id']);
            $table->dropColumn('city_id');
        });

        Schema::dropIfExists('regions');
        Schema::dropIfExists('cities');
        Schema::dropIfExists('oblasts');
    }

    public function down(): void
    {
        Schema::create('oblasts', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->foreignId('oblast_id')->nullable()->constrained('oblasts')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('oblast_id')->constrained('oblasts')->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('city_id')->nullable()->after('id')->constrained()->restrictOnDelete();
            $table->index(['city_id', 'status']);
        });

        Schema::table('masters', function (Blueprint $table) {
            $table->foreignId('city_id')->nullable()->constrained()->restrictOnDelete();
            $table->index('city_id');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
            $table->index('city_id');
        });
    }

    /**
     * Delete every master, client and order that does not belong to Ashgabat.
     * Orders go first so that masters can be removed without leaving orphans.
     */
    private function purgeNonAshgabatRecords(): void
    {
        $ashgabatId = $this->resolveAshgabatId();

        if ($ashgabatId === null) {
            return;
        }

        DB::table('orders')->where('city_id', '!=', $ashgabatId)->delete();
        DB::table('clients')->where('city_id', '!=', $ashgabatId)->delete();
        DB::table('masters')->where('city_id', '!=', $ashgabatId)->delete();
    }

    /**
     * Ashgabat is matched by name across both spellings; on a fresh or seed-less
     * database we fall back to the lowest city id so the migration never fails.
     */
    private function resolveAshgabatId(): ?int
    {
        $id = DB::table('cities')
            ->whereIn('name', ['Ашхабад', 'Aşgabat', 'Ashgabat', 'Aşgabat şäheri'])
            ->value('id');

        return $id !== null
            ? (int) $id
            : DB::table('cities')->min('id');
    }
};
