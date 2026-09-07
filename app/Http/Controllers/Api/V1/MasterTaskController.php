<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CreateOrderTaskAction;
use App\Actions\DeleteOrderTaskAction;
use App\Actions\ReplaceTaskPhotoAction;
use App\Actions\UpdateOrderTaskAction;
use App\Actions\UploadTaskPhotoAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CreateTaskRequest;
use App\Http\Requests\Api\V1\ReplaceTaskPhotoRequest;
use App\Http\Requests\Api\V1\UpdateTaskRequest;
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

    public function update(UpdateTaskRequest $request, int $orderId, int $taskId, UpdateOrderTaskAction $action): JsonResponse
    {
        /** @var Master $master */
        $master = $request->user();

        $order = $this->repository->findForMasterOrFail($orderId, $master);

        $task = OrderTask::where('order_id', $order->id)->findOrFail($taskId);

        $updated = $action->handle($master, $task, $request->validated());

        $updated->load(['beforePhotos', 'afterPhotos']);

        return (new MasterTaskResource($updated))->response();
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

    /**
     * POST rather than PATCH: PHP only populates `$_FILES` for POST bodies, so a
     * genuine multipart PATCH would arrive with no file attached.
     */
    public function replacePhoto(ReplaceTaskPhotoRequest $request, int $orderId, int $taskId, int $photoId, ReplaceTaskPhotoAction $action): JsonResponse
    {
        /** @var Master $master */
        $master = $request->user();

        $order = $this->repository->findForMasterOrFail($orderId, $master);

        $task = OrderTask::where('order_id', $order->id)->findOrFail($taskId);

        $photo = $task->photos()->findOrFail($photoId);

        $updated = $action->handle($master, $task, $photo, $request->file('photo'));

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
