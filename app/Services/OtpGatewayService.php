<?php

namespace App\Services;

use App\Exceptions\OtpException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Relays OTP codes to the Flutter SMS-gateway phone through the Socket.IO
 * bridge in socket-server/. The bridge re-emits {phone_number, otp} as a
 * Socket.IO event the gateway app is subscribed to, which then sends the SMS.
 */
class OtpGatewayService
{
    public function send(string $phone, string $code): void
    {
        try {
            $response = Http::timeout(5)
                ->withHeaders(['X-Gateway-Secret' => (string) config('services.sms_gateway.secret')])
                ->post(rtrim((string) config('services.sms_gateway.url'), '/').'/emit-otp', [
                    'phone_number' => $this->toLocalFormat($phone),
                    'otp' => $code,
                ]);
        } catch (Throwable $e) {
            Log::error("SMS gateway unreachable for {$phone}: {$e->getMessage()}");

            throw OtpException::sendFailed();
        }

        if ($response->failed()) {
            Log::error("SMS gateway rejected OTP for {$phone}: {$response->status()} {$response->body()}");

            throw OtpException::sendFailed();
        }

        Cache::put('otp_gateway:last_sent', now()->format('H:i'), now()->addDay());
    }

    private function toLocalFormat(string $phone): string
    {
        return str_starts_with($phone, '+993') ? substr($phone, 4) : $phone;
    }
}
