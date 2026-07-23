<?php

namespace App\Actions;

use App\Models\Oblast;
use App\Repositories\OblastRepository;

class DeleteOblastAction
{
    public function __construct(private readonly OblastRepository $repository) {}

    public function handle(Oblast $oblast): void
    {
        $this->repository->delete($oblast);
    }
}
