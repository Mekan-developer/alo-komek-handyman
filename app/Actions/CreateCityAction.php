<?php

namespace App\Actions;

use App\Models\City;
use App\Repositories\CityRepository;

class CreateCityAction
{
    public function __construct(private readonly CityRepository $repository) {}

    /**
     * @param  array{name: string, is_active: bool}  $data
     */
    public function handle(array $data): City
    {
        return $this->repository->create($data);
    }
}
