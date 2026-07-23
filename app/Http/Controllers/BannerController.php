<?php

namespace App\Http\Controllers;

use App\Actions\CreateBannerAction;
use App\Actions\DeleteBannerAction;
use App\Actions\ToggleBannerStatusAction;
use App\Actions\UpdateBannerAction;
use App\Http\Requests\StoreBannerRequest;
use App\Http\Requests\UpdateBannerRequest;
use App\Http\Resources\BannerResource;
use App\Http\Traits\WithNotification;
use App\Repositories\BannerRepository;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class BannerController extends Controller
{
    use WithNotification;

    public function __construct(private readonly BannerRepository $repository) {}

    public function index(): Response
    {
        return Inertia::render('Banners/Index', [
            'banners' => BannerResource::collection($this->repository->paginate()),
        ]);
    }

    public function store(StoreBannerRequest $request, CreateBannerAction $action): RedirectResponse
    {
        $action->handle($request->validated());
        $this->notifySuccess('notifications.created', ['resource' => __('resources.banner')]);

        return redirect()->route('banners.index');
    }

    public function update(UpdateBannerRequest $request, int $id, UpdateBannerAction $action): RedirectResponse
    {
        $banner = $this->repository->findOrFail($id);
        $action->handle($banner, $request->validated());
        $this->notifySuccess('notifications.updated', ['resource' => __('resources.banner')]);

        return redirect()->route('banners.index');
    }

    public function destroy(int $id, DeleteBannerAction $action): RedirectResponse
    {
        $banner = $this->repository->findOrFail($id);
        $action->handle($banner);
        $this->notifySuccess('notifications.deleted', ['resource' => __('resources.banner')]);

        return redirect()->route('banners.index');
    }

    public function toggle(int $id, ToggleBannerStatusAction $action): RedirectResponse
    {
        $banner = $this->repository->findOrFail($id);
        $action->handle($banner);

        return redirect()->route('banners.index');
    }
}
