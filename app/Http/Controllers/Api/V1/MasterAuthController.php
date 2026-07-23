<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\RequestMasterOtpAction;
use App\Actions\VerifyMasterOtpAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\RequestOtpRequest;
use App\Http\Requests\Api\V1\VerifyOtpRequest;
use App\Http\Resources\Api\V1\MasterProfileResource;
use App\Models\Master;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MasterAuthController extends Controller
{
    public function requestOtp(RequestOtpRequest $request, RequestMasterOtpAction $action): JsonResponse
    {
        $master = Master::where('phone', $request->validated('phone'))->firstOrFail();

        $action->handle($master);

        return response()->json(['message' => 'OTP sent.']);
    }

    public function verifyOtp(VerifyOtpRequest $request, VerifyMasterOtpAction $action): JsonResponse
    {
        $master = Master::where('phone', $request->validated('phone'))->firstOrFail();

        $token = $action->handle($master, $request->validated('code'));

        return response()->json([
            'token' => $token->plainTextToken,
            'master' => new MasterProfileResource($master->load('city', 'categories')),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(null, 204);
    }
}
