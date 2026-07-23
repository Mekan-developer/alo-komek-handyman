<?php

namespace App\Actions;

use App\Models\Banner;
use App\Repositories\BannerRepository;
use Illuminate\Http\UploadedFile;

class CreateBannerAction
{
    public function __construct(private readonly BannerRepository $repository) {}

    /**
     * @param  array{image: UploadedFile, url: ?string, is_active: bool, sort_order: ?int}  $data
     */
    public function handle(array $data): Banner
    {
        $path = $data['image']->store('banners', 'public');

        return $this->repository->create([
            'image_path' => $path,
            'url' => $data['url'] ?? null,
            'is_active' => $data['is_active'],
            'sort_order' => $data['sort_order'] ?? 0,
        ]);
    }
}
