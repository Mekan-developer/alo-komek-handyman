<?php

namespace App\Repositories;

use App\Models\Banner;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class BannerRepository
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Banner::orderBy('sort_order')->orderBy('id')->paginate($perPage);
    }

    /** @return Collection<int, Banner> */
    public function activeSorted(): Collection
    {
        return Banner::where('is_active', true)->orderBy('sort_order')->orderBy('id')->get();
    }

    public function findOrFail(int $id): Banner
    {
        return Banner::findOrFail($id);
    }

    public function create(array $data): Banner
    {
        return Banner::create($data);
    }

    public function update(Banner $banner, array $data): Banner
    {
        $banner->update($data);

        return $banner;
    }

    public function delete(Banner $banner): void
    {
        $banner->delete();
    }
}
