<?php

namespace App\Actions;

use App\Models\City;
use App\Repositories\CityRepository;

class UpdateCityAction
{
    public function __construct(private readonly CityRepository $repository) {}

    /**
     * @param  array{name: string, is_active: bool}  $data
     */
    public function handle(City $city, array $data): City
    {
        return $this->repository->update($city, $data);
    }
}
