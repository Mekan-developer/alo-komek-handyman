<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\ToggleMasterAvailabilityAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateMasterAvailabilityRequest;
use App\Http\Resources\Api\V1\MasterProfileResource;

class MasterAvailabilityController extends Controller
{
    public function update(
        UpdateMasterAvailabilityRequest $request,
        ToggleMasterAvailabilityAction $action,
    ): MasterProfileResource {
        $master = $request->user();

        $action->handle($master, $request->boolean('is_available'));

        return new MasterProfileResource($master->fresh()->load('city', 'categories'));
    }
}
