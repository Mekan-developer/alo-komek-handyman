<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Client\SearchCategoriesRequest;
use App\Http\Resources\Api\V1\Client\BannerResource;
use App\Http\Resources\Api\V1\Client\CategoryContentResource;
use App\Http\Resources\Api\V1\Client\CategoryResource;
use App\Models\Category;
use App\Models\City;
use App\Models\Oblast;
use App\Repositories\BannerRepository;
use App\Repositories\CategoryContentRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\RegionRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ClientCatalogController extends Controller
{
    public function __construct(
        private readonly BannerRepository $bannerRepository,
        private readonly CategoryContentRepository $contentRepository,
        private readonly RegionRepository $regionRepository,
        private readonly CategoryRepository $categoryRepository,
    ) {}

    public function banners(): AnonymousResourceCollection
    {
        return BannerResource::collection($this->bannerRepository->activeSorted());
    }

    public function cities(): JsonResponse
    {
        return response()->json([
            'data' => City::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'oblast_id']),
        ]);
    }

    public function oblasts(): JsonResponse
    {
        return response()->json([
            'data' => Oblast::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function regions(): JsonResponse
    {
        return response()->json([
            'data' => $this->regionRepository->activeWithOblast(),
        ]);
    }

    public function categoryContent(Category $category): CategoryContentResource
    {
        if (! $category->is_active) {
            abort(404, __('categories.content_not_found'));
        }

        $content = $this->contentRepository->findByCategory($category);

        if ($content === null) {
            abort(404, __('categories.content_not_found'));
        }

        return new CategoryContentResource($content);
    }

    public function categories(): JsonResponse
    {
        $nameColumn = in_array(app()->getLocale(), ['ru', 'tk'], true)
            ? 'name_'.app()->getLocale()
            : 'name_ru';

        return response()->json([
            'data' => Category::where('is_active', true)
                ->whereNull('parent_id')
                ->with(['children' => fn ($q) => $q->where('is_active', true)->orderBy($nameColumn)])
                ->orderBy($nameColumn)
                ->get(['id', 'name_ru', 'name_tk', 'parent_id', 'icon_type', 'icon']),
        ]);
    }

    /**
     * Search active services (categories) by name — backs the client app's
     * search input. Returns a flat list with each match's parent group.
     */
    public function searchCategories(SearchCategoriesRequest $request): AnonymousResourceCollection
    {
        $categories = $this->categoryRepository->searchActive($request->validated('q'));

        return CategoryResource::collection($categories);
    }
}
