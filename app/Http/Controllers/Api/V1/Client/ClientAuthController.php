<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Actions\RequestClientOtpAction;
use App\Actions\VerifyClientOtpAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Client\CompleteRegistrationRequest;
use App\Http\Requests\Api\V1\Client\RequestOtpRequest;
use App\Http\Requests\Api\V1\Client\VerifyOtpRequest;
use App\Http\Resources\Api\V1\Client\ClientProfileResource;
use App\Repositories\ClientRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientAuthController extends Controller
{
    public function requestOtp(RequestOtpRequest $request, RequestClientOtpAction $action): JsonResponse
    {
        $action->handle($request->validated('phone'));

        return response()->json(['message' => 'OTP sent.']);
    }

    public function verifyOtp(VerifyOtpRequest $request, VerifyClientOtpAction $action): JsonResponse
    {
        $result = $action->handle(
            $request->validated('phone'),
            $request->validated('code'),
        );

        return response()->json([
            'token' => $result['token']->plainTextToken,
            'is_new' => $result['is_new'],
            'client' => new ClientProfileResource($result['client']->load('city')),
        ]);
    }

    public function completeRegistration(CompleteRegistrationRequest $request, ClientRepository $repository): JsonResponse
    {
        $client = $repository->update(
            $request->user(),
            $request->validated(),
        );

        return response()->json([
            'client' => new ClientProfileResource($client->load('city')),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(null, 204);
    }
}
