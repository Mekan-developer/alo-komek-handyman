<?php

namespace App\Actions;

use App\Repositories\SettingRepository;

class UpdateSettingsAction
{
    public function __construct(private readonly SettingRepository $repository) {}

    /**
     * @param  array<string, string|null>  $settings
     */
    public function handle(array $settings): void
    {
        foreach ($settings as $key => $value) {
            $this->repository->set($key, $value);
        }
    }
}
