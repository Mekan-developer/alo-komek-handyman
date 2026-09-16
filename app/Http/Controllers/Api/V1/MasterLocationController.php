<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\UpdateMasterLocationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreMasterLocationRequest;
use App\Http\Resources\Api\V1\MasterLocationResource;
use App\Models\Master;
use Illuminate\Http\JsonResponse;

class MasterLocationController extends Controller
{
    /**
     * Accept a GPS ping from the authenticated master.
     */
    public function store(
        StoreMasterLocationRequest $request,
        UpdateMasterLocationAction $action,
    ): JsonResponse {
        /** @var Master $master */
        $master = $request->user();

        $location = $action->handle($master, $request->validated());

        return (new MasterLocationResource($location))
            ->response()
            ->setStatusCode(201);
    }
}
