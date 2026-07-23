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

    public function set(string $key, ?string $value): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
