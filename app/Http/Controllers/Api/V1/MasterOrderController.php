<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CompleteMasterOrderAction;
use App\Actions\StartMasterOrderAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\MasterOrderResource;
use App\Models\Master;
use App\Repositories\OrderRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MasterOrderController extends Controller
{
    public function __construct(private readonly OrderRepository $repository) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var Master $master */
        $master = $request->user();

        $orders = $this->repository->forMaster($master, $request->query('filter'));

        return MasterOrderResource::collection($orders);
    }

    public function show(Request $request, int $id): MasterOrderResource
    {
        /** @var Master $master */
        $master = $request->user();

        $order = $this->repository->findForMasterOrFail($id, $master);

        return new MasterOrderResource($order);
    }

    public function start(Request $request, int $id, StartMasterOrderAction $action): JsonResponse
    {
        /** @var Master $master */
        $master = $request->user();

        $order = $this->repository->findForMasterOrFail($id, $master);

        $updated = $action->handle($master, $order);

        return (new MasterOrderResource($updated))->response();
    }

    public function complete(Request $request, int $id, CompleteMasterOrderAction $action): JsonResponse
    {
        /** @var Master $master */
        $master = $request->user();

        $order = $this->repository->findForMasterOrFail($id, $master);

        $updated = $action->handle($master, $order);

        return (new MasterOrderResource($updated))->response();
    }
}
