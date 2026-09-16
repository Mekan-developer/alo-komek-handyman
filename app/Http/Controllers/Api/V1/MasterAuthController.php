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
     * Request an OTP for a master phone.
     * Unknown numbers get a generic success (no OTP cached) to avoid phone enumeration.
     * Inactive / expired masters still receive a proper 403 from the action.
     */
    public function requestOtp(RequestOtpRequest $request, RequestMasterOtpAction $action): JsonResponse
    {
        $master = Master::where('phone', $request->validated('phone'))->first();

        if ($master === null) {
            return response()->json([
                'message' => 'OTP sent.',
                'delivery' => OtpDeliveryChannel::Sms->value,
                'delivery_message' => null,
            ]);
        }

        $channel = $action->handle($master);

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
