<?php

namespace App\Http\Controllers;

use App\Actions\AssignMasterAction;
use App\Actions\CreateOrderForClientAction;
use App\Actions\DeleteOrderAction;
use App\Actions\SetOrderFinalPriceAction;
use App\Actions\UpdateOrderAction;
use App\Actions\UpdateOrderStatusAction;
use App\Exceptions\OrderException;
use App\Http\Requests\AssignMasterToOrderRequest;
use App\Http\Requests\SetOrderFinalPriceRequest;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Http\Resources\OrderResource;
use App\Http\Traits\WithNotification;
use App\Models\MasterLocation;
use App\OrderStatus;
use App\Repositories\CategoryRepository;
use App\Repositories\ClientRepository;
use App\Repositories\MasterRepository;
use App\Repositories\OblastRepository;
use App\Repositories\OrderRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    use WithNotification;

    public function __construct(
        private readonly OrderRepository $repository,
        private readonly MasterRepository $masterRepository,
    ) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['status', 'city_id', 'search', 'date_from', 'date_to']);

        return Inertia::render('Orders/Index', [
            'orders' => OrderResource::collection($this->repository->paginate($filters)),
            'oblasts' => app(OblastRepository::class)->allWithCities(),
            'categories' => app(CategoryRepository::class)->treeForSelect(),
            'clients' => app(ClientRepository::class)->allForSelect(),
            'statuses' => collect(OrderStatus::cases())->map(fn ($s) => [
                'value' => $s->value,
                'label' => $s->label(),
                'color' => $s->color(),
            ]),
            'filters' => $filters,
        ]);
    }

    public function show(int $id): Response
    {
        $order = $this->repository->findOrFail($id);

        $isPending = $order->status === OrderStatus::Pending;
        $isAssignable = ! in_array($order->status, [OrderStatus::Completed, OrderStatus::Cancelled]);

        return Inertia::render('Orders/Show', [
            'order' => (new OrderResource($order))->resolve(),
            'oblasts' => $isPending ? app(OblastRepository::class)->allWithCities() : collect(),
            'categories' => $isPending ? app(CategoryRepository::class)->treeForSelect() : [],
            'eligibleMasters' => $isAssignable
                ? $this->masterRepository
                    ->eligibleForOrder($order->city_id, $order->category_id)
                    ->filter(fn ($m) => $m->id !== $order->master_id)
                    ->map(fn ($m) => [
                        'id' => $m->id,
                        'name' => $m->name,
                        'phone' => $m->phone,
                        'categories' => $m->categories->pluck('name'),
                        'latest_location' => $m->latestLocation ? [
                            'latitude' => $m->latestLocation->latitude,
                            'longitude' => $m->latestLocation->longitude,
                        ] : null,
                    ])
                    ->values()
                : collect(),
            'statuses' => collect(OrderStatus::cases())->map(fn ($s) => [
                'value' => $s->value,
                'label' => $s->label(),
                'color' => $s->color(),
            ]),
        ]);
    }

    public function store(StoreOrderRequest $request, CreateOrderForClientAction $action): RedirectResponse
    {
        $data = $request->validated();
        $photos = $request->file('photos', []);
        unset($data['photos']);

        $action->handle($data, $photos);
        $this->notifySuccess('notifications.created', ['resource' => __('resources.order')]);

        return redirect()->route('orders.index');
    }

    public function update(UpdateOrderRequest $request, int $id, UpdateOrderAction $action): RedirectResponse
    {
        $order = $this->repository->findOrFail($id);

        try {
            $action->handle($order, $request->validated());
            $this->notifySuccess('notifications.updated', ['resource' => __('resources.order')]);
        } catch (OrderException $e) {
            $this->notifyError($e->getMessage());
        }

        return redirect()->route('orders.show', $id);
    }

    public function destroy(int $id, DeleteOrderAction $action): RedirectResponse
    {
        $order = $this->repository->findOrFail($id);
        $action->handle($order);
        $this->notifySuccess('notifications.deleted', ['resource' => __('resources.order')]);

        return redirect()->route('orders.index');
    }

    public function assign(AssignMasterToOrderRequest $request, int $id, AssignMasterAction $action): RedirectResponse
    {
        $order = $this->repository->findOrFail($id);

        try {
            $data = $request->validated();
            $action->handle($order, (int) $data['master_id'], $data['change_reason'] ?? null);
            $this->notifySuccess('orders.notifications.master_assigned');
        } catch (OrderException $e) {
            $this->notifyError($e->getMessage());
        }

        return redirect()->route('orders.show', $order->id);
    }

    public function setPrice(SetOrderFinalPriceRequest $request, int $id, SetOrderFinalPriceAction $action): RedirectResponse
    {
        $order = $this->repository->findOrFail($id);

        try {
            $action->handle($order, (float) $request->validated()['final_price']);
            $this->notifySuccess('orders.notifications.price_set');
        } catch (OrderException $e) {
            $this->notifyError($e->getMessage());
        }

        return redirect()->route('orders.show', $order->id);
    }

    public function updateStatus(UpdateOrderStatusRequest $request, int $id, UpdateOrderStatusAction $action): RedirectResponse
    {
        $order = $this->repository->findOrFail($id);
        $data = $request->validated();
        $newStatus = OrderStatus::from($data['status']);

        try {
            $updated = $action->handle($order, $newStatus, $data['cancel_reason'] ?? null);
            $updated->loadMissing('master');

            if ($newStatus === OrderStatus::Completed
                && $updated->master !== null
                && $updated->master->payment_model->requiresFinalPrice()
                && $updated->final_price === null) {
                $this->notifyWarning('orders.notifications.completed_without_price');
            } else {
                $this->notifySuccess('orders.notifications.status_updated');
            }
        } catch (OrderException $e) {
            $this->notifyError($e->getMessage());
        }

        return redirect()->route('orders.show', $order->id);
    }

    public function masterTrajectoryForOrder(int $id): JsonResponse
    {
        $order = $this->repository->findOrFail($id);

        if (! $order->master_id) {
            return response()->json(['points' => []]);
        }

        // Bounding box ~33 km around the client location to filter noise from unrelated simulate runs
        $lat = (float) $order->client_lat;
        $lng = (float) $order->client_lng;
        $delta = 0.3; // ~33 km

        $points = MasterLocation::where('master_id', $order->master_id)
            ->whereBetween('latitude', [$lat - $delta, $lat + $delta])
            ->whereBetween('longitude', [$lng - $delta, $lng + $delta])
            ->when($order->assigned_at, fn ($q) => $q->where('recorded_at', '>=', $order->assigned_at))
            ->when(
                $order->completed_at ?? $order->cancelled_at,
                fn ($q) => $q->where('recorded_at', '<=', $order->completed_at ?? $order->cancelled_at)
            )
            ->orderBy('recorded_at')
            ->get(['latitude', 'longitude', 'recorded_at']);

        return response()->json(['points' => $points]);
    }
}
