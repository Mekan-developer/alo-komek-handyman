<?php

namespace App\Actions;

use App\Models\Region;
use App\Repositories\RegionRepository;

class DeleteRegionAction
{
    public function __construct(private readonly RegionRepository $repository) {}

    public function handle(Region $region): void
    {
        $this->repository->delete($region);
    }
}
