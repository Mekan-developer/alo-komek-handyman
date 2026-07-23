<?php

namespace App\Actions;

use App\Models\City;
use App\Repositories\CityRepository;

class DeleteCityAction
{
    public function __construct(private readonly CityRepository $repository) {}

    public function handle(City $city): void
    {
        $this->repository->delete($city);
    }
}
