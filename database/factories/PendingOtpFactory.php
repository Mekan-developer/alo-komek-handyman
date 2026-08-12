<?php

namespace Database\Factories;

use App\Enums\OtpRecipientType;
use App\Models\PendingOtp;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PendingOtp>
 */
class PendingOtpFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'phone' => fake()->unique()->numerify('+99361#######'),
            'code' => fake()->numerify('######'),
            'recipient_type' => OtpRecipientType::Client,
            'recipient_name' => null,
            'expires_at' => now()->addMinutes(3),
        ];
    }

    public function forMaster(): static
    {
        return $this->state(['recipient_type' => OtpRecipientType::Master]);
    }

    public function expired(): static
    {
        return $this->state(['expires_at' => now()->subMinute()]);
    }
}
