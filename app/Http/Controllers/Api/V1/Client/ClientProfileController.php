<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Client\UpdateClientProfileRequest;
use App\Http\Resources\Api\V1\Client\ClientProfileResource;
use App\Models\Client;
use App\Repositories\ClientRepository;
use Illuminate\Http\Request;

class ClientProfileController extends Controller
{
    public function __construct(private readonly ClientRepository $repository) {}

    public function show(Request $request): ClientProfileResource
    {
        /** @var Client $client */
        $client = $request->user();

        return new ClientProfileResource($client->load('city'));
    }

    public function update(UpdateClientProfileRequest $request): ClientProfileResource
    {
        /** @var Client $client */
        $client = $request->user();

        $updated = $this->repository->update($client, $request->validated());

        return new ClientProfileResource($updated->load('city'));
    }
}
