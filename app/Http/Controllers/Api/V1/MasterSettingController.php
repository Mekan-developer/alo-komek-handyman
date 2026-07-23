<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Repositories\SettingRepository;
use Illuminate\Http\JsonResponse;

class MasterSettingController extends Controller
{
    public function __construct(private readonly SettingRepository $repository) {}

    public function show(): JsonResponse
    {
        return response()->json([
            'data' => [
                'content' => $this->repository->get('master_app_rules') ?? '',
            ],
        ]);
    }
}
