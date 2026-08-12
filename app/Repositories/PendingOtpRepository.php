<?php

namespace App\Repositories;

use App\Enums\OtpRecipientType;
use App\Models\PendingOtp;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class PendingOtpRepository
{
    /**
     * Replaces any earlier undelivered code for the same phone — only the
     * latest one is valid, so showing stale codes would confuse operators.
     *
     * @param  array{phone: string, code: string, recipient_type: OtpRecipientType, recipient_name: ?string, expires_at: Carbon}  $data
     */
    public function replaceForPhone(array $data): PendingOtp
    {
        PendingOtp::where('phone', $data['phone'])->delete();

        return PendingOtp::create($data);
    }

    /** @return Collection<int, PendingOtp> */
    public function active(): Collection
    {
        return PendingOtp::where('expires_at', '>', now())
            ->latest('id')
            ->get();
    }

    public function countActive(): int
    {
        return PendingOtp::where('expires_at', '>', now())->count();
    }

    public function findOrFail(int $id): PendingOtp
    {
        return PendingOtp::findOrFail($id);
    }

    public function delete(PendingOtp $otp): void
    {
        $otp->delete();
    }

    public function purgeExpired(): void
    {
        PendingOtp::where('expires_at', '<=', now())->delete();
    }
}
