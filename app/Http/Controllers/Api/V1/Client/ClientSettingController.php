<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Controller;
use App\Repositories\SettingRepository;
use Illuminate\Http\JsonResponse;

class ClientSettingController extends Controller
{
    public function __construct(private readonly SettingRepository $repository) {}

    public function show(): JsonResponse
    {
        return response()->json([
            'data' => [
                'content' => $this->repository->get('client_app_rules') ?? '',
            ],
        ]);
    }
}
