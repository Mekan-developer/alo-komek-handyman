<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\RequestMasterOtpAction;
use App\Actions\VerifyMasterOtpAction;
use App\Enums\OtpDeliveryChannel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\RequestOtpRequest;
use App\Http\Requests\Api\V1\VerifyOtpRequest;
use App\Http\Resources\Api\V1\MasterProfileResource;
use App\Models\Master;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MasterAuthController extends Controller
{
    /**
     * Request an OTP for a registered master phone.
     * Unknown numbers return 404 — masters are staff accounts, not public signup.
     */
    public function requestOtp(RequestOtpRequest $request, RequestMasterOtpAction $action): JsonResponse
    {
        $channel = $action->handle($request->validated('phone'));

        return response()->json([
            'message' => 'OTP sent.',
            'delivery' => $channel->value,
            'delivery_message' => $channel === OtpDeliveryChannel::Manual
                ? __('api.otp.manual_delivery')
                : null,
        ]);
    }

    /**
     * Verify OTP and issue a Sanctum token for the master.
     */
    public function verifyOtp(VerifyOtpRequest $request, VerifyMasterOtpAction $action): JsonResponse
    {
        $master = Master::where('phone', $request->validated('phone'))->firstOrFail();

        $token = $action->handle($master, $request->validated('code'));

        return response()->json([
            'token' => $token->plainTextToken,
            'master' => new MasterProfileResource($master->load('categories')),
        ]);
    }

    /**
     * Revoke the current master access token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(null, 204);
    }
}
