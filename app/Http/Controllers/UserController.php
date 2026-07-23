<?php

namespace App\Http\Controllers;

use App\Actions\CreateUserAction;
use App\Actions\DeleteUserAction;
use App\Actions\UpdateUserAction;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Http\Traits\WithNotification;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    use WithNotification;

    public function __construct(private readonly UserRepository $repository) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', User::class);

        $filters = $request->only(['role']);

        return Inertia::render('Users/Index', [
            'users' => UserResource::collection($this->repository->paginate(20, $filters)),
            'filters' => $filters,
        ]);
    }

    public function store(StoreUserRequest $request, CreateUserAction $action): RedirectResponse
    {
        Gate::authorize('create', User::class);

        $action->handle($request->user(), $request->validated());
        $this->notifySuccess('notifications.created', ['resource' => __('resources.user')]);

        return redirect()->route('users.index');
    }

    public function update(UpdateUserRequest $request, int $user, UpdateUserAction $action): RedirectResponse
    {
        $target = $this->repository->findOrFail($user);
        Gate::authorize('update', $target);

        $action->handle($request->user(), $target, $request->validated());
        $this->notifySuccess('notifications.updated', ['resource' => __('resources.user')]);

        return redirect()->route('users.index');
    }

    public function destroy(int $user, DeleteUserAction $action): RedirectResponse
    {
        $target = $this->repository->findOrFail($user);
        Gate::authorize('delete', $target);

        $action->handle(request()->user(), $target);
        $this->notifySuccess('notifications.deleted', ['resource' => __('resources.user')]);

        return redirect()->route('users.index');
    }
}
