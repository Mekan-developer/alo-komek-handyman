<?php

namespace App\Actions;

use App\Events\ClientRegistered;
use App\Exceptions\OtpException;
use App\Models\Client;
use App\Repositories\ClientRepository;
use App\Services\OtpTestAccountService;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\NewAccessToken;

class VerifyClientOtpAction
{
    public function __construct(
        private readonly ClientRepository $repository,
        private readonly OtpTestAccountService $testAccounts,
    ) {}

    /**
     * @return array{client: Client, token: NewAccessToken, is_new: bool}
     */
    public function handle(string $phone, string $code): array
    {
        if (! $this->testAccounts->matches($phone, $code)) {
            $cached = Cache::get("client_otp:{$phone}");

            if ($cached === null || $cached !== $code) {
                throw OtpException::invalid();
            }

            Cache::forget("client_otp:{$phone}");
        }

        $client = $this->repository->findByPhone($phone);
        $isNew = $client === null;

        if ($isNew) {
            $client = $this->repository->create(['phone' => $phone]);

            ClientRegistered::dispatch($client);
        }

        $client->tokens()->where('name', 'mobile-client')->delete();

        return [
            'client' => $client,
            'token' => $client->createToken('mobile-client'),
            'is_new' => $isNew,
        ];
    }
}
