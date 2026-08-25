<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Actions\CancelClientOrderAction;
use App\Actions\CreateClientOrderAction;
use App\Actions\CreateOrderReviewAction;
use App\Actions\UpdateClientOrderAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Client\CancelClientOrderRequest;
use App\Http\Requests\Api\V1\Client\CreateClientOrderRequest;
use App\Http\Requests\Api\V1\Client\CreateOrderReviewRequest;
use App\Http\Requests\Api\V1\Client\UpdateClientOrderRequest;
use App\Http\Resources\Api\V1\Client\ClientOrderResource;
use App\Http\Resources\Api\V1\Client\OrderReviewResource;
use App\Http\Resources\OrderReceiptResource;
use App\Models\Client;
use App\Repositories\OrderReceiptRepository;
use App\Repositories\OrderRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ClientOrderController extends Controller
{
    public function __construct(
        private readonly OrderRepository $repository,
        private readonly OrderReceiptRepository $receiptRepository,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var Client $client */
        $client = $request->user();

        $orders = $this->repository->forClient($client, $request->query('status'));

        return ClientOrderResource::collection($orders);
    }

    public function show(Request $request, int $id): ClientOrderResource
    {
        /** @var Client $client */
        $client = $request->user();

        $order = $this->repository->findForClientOrFail($id, $client);

        return new ClientOrderResource($order);
    }

    public function store(CreateClientOrderRequest $request, CreateClientOrderAction $action): JsonResponse
    {
        /** @var Client $client */
        $client = $request->user();

        $data = $request->validated();
        $photos = $request->file('photos', []);
        unset($data['photos']);

        $order = $action->handle($client, $data, $photos);

        return (new ClientOrderResource($order->load(['category', 'photos'])))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateClientOrderRequest $request, int $id, UpdateClientOrderAction $action): JsonResponse
    {
        /** @var Client $client */
        $client = $request->user();

        $order = $this->repository->findForClientOrFail($id, $client);

        $data = $request->validated();
        $photos = $request->file('photos', []);
        $removePhotoIds = $data['remove_photo_ids'] ?? [];
        unset($data['photos'], $data['remove_photo_ids']);

        $updated = $action->handle($order, $data, $photos, $removePhotoIds);

        return (new ClientOrderResource($updated->load(['category', 'master', 'photos'])))->response();
    }

    public function cancel(CancelClientOrderRequest $request, int $id, CancelClientOrderAction $action): JsonResponse
    {
        /** @var Client $client */
        $client = $request->user();

        $order = $this->repository->findForClientOrFail($id, $client);

        $cancelled = $action->handle($order, $request->validated('reason'));

        return (new ClientOrderResource($cancelled->load(['category', 'master', 'photos'])))->response();
    }

    /**
     * Чек по завершённому заказу. 404, пока заказ не завершён.
     */
    public function receipt(Request $request, int $id): OrderReceiptResource
    {
        /** @var Client $client */
        $client = $request->user();

        $order = $this->repository->findForClientOrFail($id, $client);

        return new OrderReceiptResource($this->receiptRepository->findForOrderOrFail($order));
    }

    public function storeReview(CreateOrderReviewRequest $request, int $id, CreateOrderReviewAction $action): JsonResponse
    {
        /** @var Client $client */
        $client = $request->user();

        $order = $this->repository->findForClientOrFail($id, $client);

        $review = $action->handle($order, $request->validated());

        return (new OrderReviewResource($review))
            ->response()
            ->setStatusCode(201);
    }
}
