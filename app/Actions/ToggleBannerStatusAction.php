<?php

namespace App\Actions;

use App\Models\Banner;
use App\Repositories\BannerRepository;

class ToggleBannerStatusAction
{
    public function __construct(private readonly BannerRepository $repository) {}

    public function handle(Banner $banner): Banner
    {
        return $this->repository->update($banner, ['is_active' => ! $banner->is_active]);
    }
}
