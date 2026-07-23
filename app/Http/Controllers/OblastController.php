<?php

namespace App\Http\Controllers;

use App\Actions\CreateOblastAction;
use App\Actions\DeleteOblastAction;
use App\Actions\UpdateOblastAction;
use App\Http\Requests\StoreOblastRequest;
use App\Http\Requests\UpdateOblastRequest;
use App\Http\Resources\CityResource;
use App\Http\Traits\WithNotification;
use App\Repositories\CityRepository;
use App\Repositories\OblastRepository;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class OblastController extends Controller
{
    use WithNotification;

    public function __construct(
        private readonly OblastRepository $repository,
        private readonly CityRepository $cityRepository,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Oblasts/Index', [
            'oblasts' => $this->repository->list(),
            'cities' => CityResource::collection($this->cityRepository->all()),
        ]);
    }

    public function store(StoreOblastRequest $request, CreateOblastAction $action): RedirectResponse
    {
        $action->handle($request->validated());
        $this->notifySuccess('notifications.created', ['resource' => __('resources.oblast')]);

        return redirect()->route('oblasts.index');
    }

    public function update(UpdateOblastRequest $request, int $id, UpdateOblastAction $action): RedirectResponse
    {
        $oblast = $this->repository->findOrFail($id);
        $action->handle($oblast, $request->validated());
        $this->notifySuccess('notifications.updated', ['resource' => __('resources.oblast')]);

        return redirect()->route('oblasts.index');
    }

    public function destroy(int $id, DeleteOblastAction $action): RedirectResponse
    {
        $oblast = $this->repository->findOrFail($id);
        $action->handle($oblast);
        $this->notifySuccess('notifications.deleted', ['resource' => __('resources.oblast')]);

        return redirect()->route('oblasts.index');
    }
}
