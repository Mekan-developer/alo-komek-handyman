<?php

namespace App\Actions;

use App\Models\Region;
use App\Repositories\RegionRepository;

class CreateRegionAction
{
    public function __construct(private readonly RegionRepository $repository) {}

    /**
     * @param  array{name: string, oblast_id: int, is_active: bool}  $data
     */
    public function handle(array $data): Region
    {
        return $this->repository->create($data);
    }
}
