<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CreateOrderTaskAction;
use App\Actions\DeleteOrderTaskAction;
use App\Actions\UploadTaskPhotoAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CreateTaskRequest;
use App\Http\Requests\Api\V1\UploadTaskPhotoRequest;
use App\Http\Resources\Api\V1\MasterTaskResource;
use App\Models\Master;
use App\Models\OrderTask;
use App\Repositories\OrderRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MasterTaskController extends Controller
{
    public function __construct(private readonly OrderRepository $repository) {}

    public function store(CreateTaskRequest $request, int $orderId, CreateOrderTaskAction $action): JsonResponse
    {
        /** @var Master $master */
        $master = $request->user();

        $order = $this->repository->findForMasterOrFail($orderId, $master);

        try {
            $task = $action->handle($master, $order, $request->validated());
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $task->load(['beforePhotos', 'afterPhotos']);

        return (new MasterTaskResource($task))
            ->response()
            ->setStatusCode(201);
    }

    public function uploadPhoto(UploadTaskPhotoRequest $request, int $orderId, int $taskId, UploadTaskPhotoAction $action): JsonResponse
    {
        /** @var Master $master */
        $master = $request->user();

        $order = $this->repository->findForMasterOrFail($orderId, $master);

        $task = OrderTask::where('order_id', $order->id)->findOrFail($taskId);

        try {
            $updated = $action->handle($master, $task, $request->validated('type'), $request->file('photo'));
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return (new MasterTaskResource($updated))
            ->response()
            ->setStatusCode(202);
    }

    public function destroy(Request $request, int $orderId, int $taskId, DeleteOrderTaskAction $action): JsonResponse
    {
        /** @var Master $master */
        $master = $request->user();

        $order = $this->repository->findForMasterOrFail($orderId, $master);

        $task = OrderTask::where('order_id', $order->id)->findOrFail($taskId);

        try {
            $action->handle($master, $task);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(null, 204);
    }
}
