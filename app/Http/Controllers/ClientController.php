<?php

namespace App\Http\Controllers;

use App\Actions\CreateClientAction;
use App\Actions\DeleteClientAction;
use App\Actions\ToggleClientBlockAction;
use App\Actions\UpdateClientAction;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Http\Resources\ClientResource;
use App\Http\Traits\WithNotification;
use App\Repositories\ClientRepository;
use App\Repositories\OblastRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    use WithNotification;

    public function __construct(private readonly ClientRepository $repository) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['oblast_id', 'city_id']);

        return Inertia::render('Clients/Index', [
            'clients' => ClientResource::collection($this->repository->paginate(20, $filters)),
            'oblasts' => app(OblastRepository::class)->allWithCities(),
            'filters' => $filters,
        ]);
    }

    public function store(StoreClientRequest $request, CreateClientAction $action): RedirectResponse
    {
        $action->handle($request->validated());
        $this->notifySuccess('notifications.created', ['resource' => __('resources.client')]);

        return redirect()->route('clients.index');
    }

    public function update(UpdateClientRequest $request, int $client, UpdateClientAction $action): RedirectResponse
    {
        $model = $this->repository->findOrFail($client);
        $action->handle($model, $request->validated());
        $this->notifySuccess('notifications.updated', ['resource' => __('resources.client')]);

        return redirect()->route('clients.index');
    }

    public function destroy(int $client, DeleteClientAction $action): RedirectResponse
    {
        $model = $this->repository->findOrFail($client);
        $action->handle($model);
        $this->notifySuccess('notifications.deleted', ['resource' => __('resources.client')]);

        return redirect()->route('clients.index');
    }

    public function toggleBlock(int $client, ToggleClientBlockAction $action): RedirectResponse
    {
        $model = $this->repository->findOrFail($client);
        $updated = $action->handle($model);

        $key = $updated->is_blocked ? 'clients.notifications.blocked' : 'clients.notifications.unblocked';
        $this->notifySuccess($key);

        return redirect()->route('clients.index');
    }
}
