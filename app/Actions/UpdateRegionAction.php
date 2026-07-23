<?php

namespace App\Actions;

use App\Models\Region;
use App\Repositories\RegionRepository;

class UpdateRegionAction
{
    public function __construct(private readonly RegionRepository $repository) {}

    /**
     * @param  array{name: string, oblast_id: int, is_active: bool}  $data
     */
    public function handle(Region $region, array $data): Region
    {
        return $this->repository->update($region, $data);
    }
}
