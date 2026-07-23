<?php

namespace App\Http\Controllers;

use App\Actions\CreateMasterAction;
use App\Actions\DeleteMasterAction;
use App\Actions\RecordMasterPayoutAction;
use App\Actions\UpdateMasterAction;
use App\Exceptions\PaymentException;
use App\Http\Requests\StoreMasterRequest;
use App\Http\Requests\UpdateMasterRequest;
use App\Http\Resources\MasterResource;
use App\Http\Traits\WithNotification;
use App\PaymentModel;
use App\Repositories\CategoryRepository;
use App\Repositories\MasterRepository;
use App\Repositories\OblastRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MasterController extends Controller
{
    use WithNotification;

    public function __construct(private readonly MasterRepository $repository) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'city_id']);

        return Inertia::render('Masters/Index', [
            'masters' => MasterResource::collection($this->repository->paginate(15, $filters)),
            'oblasts' => app(OblastRepository::class)->allWithCities(),
            'categories' => app(CategoryRepository::class)->treeForSelect(),
            'paymentModels' => collect(PaymentModel::cases())
                ->reject(fn ($m) => $m === PaymentModel::FixedPerJob)
                ->map(fn ($m) => [
                    'value' => $m->value,
                    'label' => $m->label(),
                ])
                ->values(),
            'filters' => $filters,
        ]);
    }

    public function map(): Response
    {
        $masters = $this->repository->forMap();

        return Inertia::render('Masters/Map', [
            'masters' => MasterResource::collection($masters)->resolve(),
            'cityIds' => $masters->pluck('city_id')->unique()->values(),
        ]);
    }

    public function trajectory(int $id): JsonResponse
    {
        $master = $this->repository->findOrFail($id);

        return response()->json([
            'master' => ['id' => $master->id, 'name' => $master->name],
            'points' => $this->repository->trajectory($master),
        ]);
    }

    public function store(StoreMasterRequest $request, CreateMasterAction $action): RedirectResponse
    {
        $action->handle($request->validated());
        $this->notifySuccess('notifications.created', ['resource' => __('resources.master')]);

        return redirect()->route('masters.index');
    }

    public function update(UpdateMasterRequest $request, int $id, UpdateMasterAction $action): RedirectResponse
    {
        $master = $this->repository->findOrFail($id);
        $action->handle($master, $request->validated());
        $this->notifySuccess('notifications.updated', ['resource' => __('resources.master')]);

        return redirect()->route('masters.index');
    }

    public function destroy(int $id, DeleteMasterAction $action): RedirectResponse
    {
        $master = $this->repository->findOrFail($id);
        $action->handle($master);
        $this->notifySuccess('notifications.deleted', ['resource' => __('resources.master')]);

        return redirect()->route('masters.index');
    }

    public function resetBalance(int $id, RecordMasterPayoutAction $action): RedirectResponse
    {
        $master = $this->repository->findOrFail($id);

        try {
            $action->handle($master, request()->user());
            $this->notifySuccess('masters.notifications.balance_reset');
        } catch (PaymentException $e) {
            $this->notifyError($e->getMessage());
        }

        return redirect()->route('masters.index');
    }
}
