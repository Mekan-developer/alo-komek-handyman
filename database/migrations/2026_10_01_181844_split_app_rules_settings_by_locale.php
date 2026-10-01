<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Правила приложений стали двуязычными: существующий текст считается русским
 * (`*_app_rules` → `*_app_rules_ru`), туркменский вариант заполняется в админке.
 */
return new class extends Migration
{
    /** @var list<string> */
    private array $keys = ['master_app_rules', 'client_app_rules'];

    public function up(): void
    {
        foreach ($this->keys as $key) {
            $this->rename($key, "{$key}_ru");
        }
    }

    public function down(): void
    {
        foreach ($this->keys as $key) {
            $this->rename("{$key}_ru", $key);
            DB::table('settings')->where('key', "{$key}_tk")->delete();
        }
    }

    private function rename(string $from, string $to): void
    {
        if (DB::table('settings')->where('key', $to)->exists()) {
            return;
        }

        DB::table('settings')->where('key', $from)->update(['key' => $to]);
    }
};
