<?php

namespace App\Http\Controllers;

use App\Actions\CreateRegionAction;
use App\Actions\DeleteRegionAction;
use App\Actions\UpdateRegionAction;
use App\Http\Requests\StoreRegionRequest;
use App\Http\Requests\UpdateRegionRequest;
use App\Http\Traits\WithNotification;
use App\Repositories\OblastRepository;
use App\Repositories\RegionRepository;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class RegionController extends Controller
{
    use WithNotification;

    public function __construct(
        private readonly RegionRepository $repository,
        private readonly OblastRepository $oblastRepository,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Regions/Index', [
            'regions' => $this->repository->paginate(),
            'oblasts' => $this->oblastRepository->all(),
        ]);
    }

    public function store(StoreRegionRequest $request, CreateRegionAction $action): RedirectResponse
    {
        $action->handle($request->validated());
        $this->notifySuccess('notifications.created', ['resource' => __('resources.region')]);

        return redirect()->route('regions.index');
    }

    public function update(UpdateRegionRequest $request, int $id, UpdateRegionAction $action): RedirectResponse
    {
        $region = $this->repository->findOrFail($id);
        $action->handle($region, $request->validated());
        $this->notifySuccess('notifications.updated', ['resource' => __('resources.region')]);

        return redirect()->route('regions.index');
    }

    public function destroy(int $id, DeleteRegionAction $action): RedirectResponse
    {
        $region = $this->repository->findOrFail($id);
        $action->handle($region);
        $this->notifySuccess('notifications.deleted', ['resource' => __('resources.region')]);

        return redirect()->route('regions.index');
    }
}
