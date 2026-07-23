<?php

namespace App\Http\Controllers;

use App\Actions\CreateCityAction;
use App\Actions\DeleteCityAction;
use App\Actions\UpdateCityAction;
use App\Http\Requests\StoreCityRequest;
use App\Http\Requests\UpdateCityRequest;
use App\Http\Resources\CityResource;
use App\Http\Traits\WithNotification;
use App\Repositories\CityRepository;
use App\Repositories\OblastRepository;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CityController extends Controller
{
    use WithNotification;

    public function __construct(
        private readonly CityRepository $repository,
        private readonly OblastRepository $oblastRepository,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Cities/Index', [
            'cities' => CityResource::collection($this->repository->paginate()),
            'oblasts' => $this->oblastRepository->all(),
        ]);
    }

    public function store(StoreCityRequest $request, CreateCityAction $action): RedirectResponse
    {
        $action->handle($request->validated());
        $this->notifySuccess('notifications.created', ['resource' => __('resources.city')]);

        return redirect()->route('oblasts.index');
    }

    public function update(UpdateCityRequest $request, int $id, UpdateCityAction $action): RedirectResponse
    {
        $city = $this->repository->findOrFail($id);
        $action->handle($city, $request->validated());
        $this->notifySuccess('notifications.updated', ['resource' => __('resources.city')]);

        return redirect()->route('oblasts.index');
    }

    public function destroy(int $id, DeleteCityAction $action): RedirectResponse
    {
        $city = $this->repository->findOrFail($id);
        $action->handle($city);
        $this->notifySuccess('notifications.deleted', ['resource' => __('resources.city')]);

        return redirect()->route('oblasts.index');
    }
}
