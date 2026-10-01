<?php

namespace App\Repositories;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Collection;

class SettingRepository
{
    /** @return Collection<int, Setting> */
    public function all(): Collection
    {
        return Setting::orderBy('key')->get();
    }

    public function get(string $key): ?string
    {
        return Setting::where('key', $key)->value('value');
    }

    /**
     * Билингвальное значение `{prefix}_{locale}` с откатом на русский вариант.
     */
    public function getLocalized(string $keyPrefix, string $locale): string
    {
        $value = $this->get("{$keyPrefix}_{$locale}");

        return $value ?: ($this->get("{$keyPrefix}_ru") ?? '');
    }

    public function set(string $key, ?string $value): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
