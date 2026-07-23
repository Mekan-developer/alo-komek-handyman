<?php

namespace App\Http\Controllers;

use App\Actions\CreateCategoryAction;
use App\Actions\DeleteCategoryAction;
use App\Actions\UpdateCategoryAction;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Http\Traits\WithNotification;
use App\Repositories\CategoryRepository;
use App\Support\CategoryIcon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    use WithNotification;

    public function __construct(private readonly CategoryRepository $repository) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['search']);

        return Inertia::render('Categories/Index', [
            'categories' => CategoryResource::collection($this->repository->paginate(15, $filters)),
            'parentCategories' => $this->repository->roots(),
            'iconGroups' => array_filter(
                array_merge(config('service_icons', []), ['uploaded' => CategoryIcon::uploadedKeys()]),
                fn (array $group) => count($group) > 0,
            ),
            'filters' => $filters,
        ]);
    }

    public function store(StoreCategoryRequest $request, CreateCategoryAction $action): RedirectResponse
    {
        $action->handle($request->validated(), $request->file('icon_file'));
        $this->notifySuccess('notifications.created', ['resource' => __('resources.category')]);

        return redirect()->route('categories.index');
    }

    public function update(UpdateCategoryRequest $request, int $id, UpdateCategoryAction $action): RedirectResponse
    {
        $category = $this->repository->findOrFail($id);
        $action->handle($category, $request->validated(), $request->file('icon_file'));
        $this->notifySuccess('notifications.updated', ['resource' => __('resources.category')]);

        return redirect()->route('categories.index');
    }

    public function destroy(int $id, DeleteCategoryAction $action): RedirectResponse
    {
        $category = $this->repository->findOrFail($id);

        try {
            $action->handle($category);
            $this->notifySuccess('notifications.deleted', ['resource' => __('resources.category')]);
        } catch (\RuntimeException $e) {
            $this->notifyError($e->getMessage());
        }

        return redirect()->route('categories.index');
    }
}
