<?php

namespace App\Actions;

use App\Models\Banner;
use App\Repositories\BannerRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UpdateBannerAction
{
    public function __construct(private readonly BannerRepository $repository) {}

    /**
     * @param  array{image: ?UploadedFile, url: ?string, is_active: bool, sort_order: ?int}  $data
     */
    public function handle(Banner $banner, array $data): Banner
    {
        $updateData = [
            'url' => $data['url'] ?? null,
            'is_active' => $data['is_active'],
            'sort_order' => $data['sort_order'] ?? $banner->sort_order,
        ];

        if (isset($data['image'])) {
            Storage::disk('public')->delete($banner->image_path);
            $updateData['image_path'] = $data['image']->store('banners', 'public');
        }

        return $this->repository->update($banner, $updateData);
    }
}
