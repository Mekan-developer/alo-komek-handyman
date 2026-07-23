<?php

namespace App\Actions;

use App\Models\Banner;
use App\Repositories\BannerRepository;
use Illuminate\Support\Facades\Storage;

class DeleteBannerAction
{
    public function __construct(private readonly BannerRepository $repository) {}

    public function handle(Banner $banner): void
    {
        Storage::disk('public')->delete($banner->image_path);
        $this->repository->delete($banner);
    }
}
