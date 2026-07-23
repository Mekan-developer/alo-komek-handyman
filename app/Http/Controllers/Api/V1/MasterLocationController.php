<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\UpdateMasterLocationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreMasterLocationRequest;
use App\Http\Resources\Api\V1\MasterLocationResource;
use App\Repositories\MasterRepository;
use Illuminate\Http\JsonResponse;

class MasterLocationController extends Controller
{
    public function __construct(private readonly MasterRepository $repository) {}

    public function store(
        StoreMasterLocationRequest $request,
        int $masterId,
        UpdateMasterLocationAction $action,
    ): JsonResponse {
        $master = $this->repository->findOrFail($masterId);

        if (! $master->is_active || ! $master->hasActiveAccess()) {
            return response()->json(['message' => 'Master access expired or master is inactive.'], 403);
        }

        $location = $action->handle($master, $request->validated());

        return (new MasterLocationResource($location))
            ->response()
            ->setStatusCode(201);
    }
}
