<?php

namespace App\Actions;

use App\Models\Oblast;
use App\Repositories\OblastRepository;

class CreateOblastAction
{
    public function __construct(private readonly OblastRepository $repository) {}

    /**
     * @param  array{name: string, is_active: bool}  $data
     */
    public function handle(array $data): Oblast
    {
        return $this->repository->create($data);
    }
}
