<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexReviewRequest;
use App\Http\Resources\OrderReviewResource;
use App\Repositories\MasterRepository;
use App\Repositories\OrderReviewRepository;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class ReviewController extends Controller
{
    public function __construct(
        private readonly OrderReviewRepository $reviews,
        private readonly MasterRepository $masters,
    ) {}

    public function index(IndexReviewRequest $request): Response
    {
        $filters = $request->filters();

        return Inertia::render('Reviews/Index', [
            'reviews' => OrderReviewResource::collection($this->reviews->paginate($filters)),
            'stats' => $this->reviews->stats($filters),
            'masters' => $this->masters->withReviews()->map(fn ($master) => [
                'id' => $master->id,
                'name' => $master->name,
                'reviews_count' => (int) $master->reviews_count,
                'reviews_avg_rating' => $master->reviews_avg_rating !== null
                    ? round((float) $master->reviews_avg_rating, 1)
                    : null,
            ]),
            'filters' => $filters,
        ]);
    }

    /** Reviews of one master, fetched on demand by the masters table. */
    public function forMaster(int $masterId): JsonResponse
    {
        $master = $this->masters->findOrFail($masterId);

        return response()->json([
            'master' => [
                'id' => $master->id,
                'name' => $master->name,
            ],
            'stats' => $this->reviews->stats(['master_id' => $master->id]),
            'reviews' => OrderReviewResource::collection($this->reviews->forMaster($master->id))->resolve(),
        ]);
    }
}
