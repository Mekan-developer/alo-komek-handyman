<?php

namespace App\Http\Controllers;

use App\Http\Resources\PendingOtpResource;
use App\Repositories\PendingOtpRepository;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Codes the SMS gateway failed to deliver — operators read them out to the
 * caller. The page renders through Inertia; `data` feeds the 10s poll used by
 * both this page and the dashboard panel.
 */
class PendingOtpController extends Controller
{
    public function __construct(private readonly PendingOtpRepository $repository) {}

    public function index(): Response
    {
        $this->repository->purgeExpired();

        return Inertia::render('PendingOtps/Index', [
            'codes' => PendingOtpResource::collection($this->repository->active())->resolve(),
        ]);
    }

    public function data(): JsonResponse
    {
        $this->repository->purgeExpired();

        return response()->json([
            'data' => PendingOtpResource::collection($this->repository->active())->resolve(),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->repository->delete($this->repository->findOrFail($id));

        return response()->json(null, 204);
    }
}
