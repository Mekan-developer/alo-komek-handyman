<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Split the single-language `name` / `title` / `description` columns into
     * Russian (`*_ru`) + Turkmen (`*_tk`) pairs. The existing value becomes the
     * Russian variant; the Turkmen one is backfilled from it so legacy rows stay
     * usable until they are translated.
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->renameColumn('name', 'name_ru');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->string('name_tk')->nullable()->after('name_ru');
        });

        DB::table('categories')->whereNull('name_tk')->update([
            'name_tk' => DB::raw('name_ru'),
        ]);

        Schema::table('category_contents', function (Blueprint $table) {
            $table->renameColumn('title', 'title_ru');
            $table->renameColumn('description', 'description_ru');
        });

        Schema::table('category_contents', function (Blueprint $table) {
            $table->string('title_tk')->nullable()->after('title_ru');
            $table->text('description_tk')->nullable()->after('description_ru');
        });

        DB::table('category_contents')->whereNull('title_tk')->update([
            'title_tk' => DB::raw('title_ru'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('category_contents', function (Blueprint $table) {
            $table->dropColumn(['title_tk', 'description_tk']);
        });

        Schema::table('category_contents', function (Blueprint $table) {
            $table->renameColumn('title_ru', 'title');
            $table->renameColumn('description_ru', 'description');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('name_tk');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->renameColumn('name_ru', 'name');
        });
    }
};
