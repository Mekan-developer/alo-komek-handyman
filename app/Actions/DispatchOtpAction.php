<?php

namespace App\Actions;

use App\Enums\OtpDeliveryChannel;
use App\Enums\OtpRecipientType;
use App\Events\PendingOtpCreated;
use App\Exceptions\OtpException;
use App\Repositories\PendingOtpRepository;
use App\Services\OtpGatewayService;
use App\Services\OtpTestAccountService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Generates an OTP, tries SMS delivery, and always parks the code in the admin
 * panel so an operator can dictate it if the SMS does not arrive.
 */
class DispatchOtpAction
{
    public function __construct(
        private readonly OtpGatewayService $gateway,
        private readonly PendingOtpRepository $pendingOtps,
        private readonly OtpTestAccountService $testAccounts,
    ) {}

    /**
     * Issues an OTP for the phone and returns how it was delivered to the caller.
     */
    public function handle(string $phone, OtpRecipientType $recipient, ?string $recipientName = null): OtpDeliveryChannel
    {
        if ($this->testAccounts->isTestPhone($phone)) {
            Log::info('OTP skipped for store-review test phone', [
                'phone' => $phone,
                'recipient_type' => $recipient->value,
            ]);

            return OtpDeliveryChannel::Sms;
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = now()->addMinutes((int) config('services.otp.ttl_minutes'));

        $channel = OtpDeliveryChannel::Sms;

        try {
            $this->gateway->send($phone, $code);
        } catch (OtpException) {
            $channel = OtpDeliveryChannel::Manual;
        }

        $pendingOtp = $this->pendingOtps->replaceForPhone([
            'phone' => $phone,
            'code' => $code,
            'recipient_type' => $recipient,
            'recipient_name' => $recipientName,
            'expires_at' => $expiresAt,
        ]);

        PendingOtpCreated::dispatch($pendingOtp);

        Cache::put($recipient->cacheKey($phone), $code, $expiresAt);

        Log::info('OTP dispatched', [
            'phone' => $phone,
            'recipient_type' => $recipient->value,
            'recipient_name' => $recipientName,
            'delivery' => $channel->value,
            'pending_otp_id' => $pendingOtp->id,
        ]);

        return $channel;
    }
}
