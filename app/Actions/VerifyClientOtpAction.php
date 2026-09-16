<?php

namespace App\Actions;

use App\Events\ClientRegistered;
use App\Exceptions\ClientBlockedException;
use App\Exceptions\OtpException;
use App\Models\Client;
use App\Repositories\ClientRepository;
use App\Services\OtpCodeVerifier;
use App\Services\OtpTestAccountService;
use Laravel\Sanctum\NewAccessToken;

class VerifyClientOtpAction
{
    public function __construct(
        private readonly ClientRepository $repository,
        private readonly OtpTestAccountService $testAccounts,
        private readonly OtpCodeVerifier $verifier,
    ) {}

    /**
     * @return array{client: Client, token: NewAccessToken, is_new: bool}
     *
     * @throws OtpException
     * @throws ClientBlockedException
     */
    public function handle(string $phone, string $code): array
    {
        if (! $this->testAccounts->matches($phone, $code)) {
            $this->verifier->verify("client_otp:{$phone}", $code);
        }

        $client = $this->repository->findByPhone($phone);
        $isNew = $client === null;

        if ($isNew) {
            $client = $this->repository->create(['phone' => $phone]);

            ClientRegistered::dispatch($client);
        }

        if ($client->is_blocked) {
            throw ClientBlockedException::blocked();
        }

        $client->tokens()->where('name', 'mobile-client')->delete();

        return [
            'client' => $client,
            'token' => $client->createToken('mobile-client'),
            'is_new' => $isNew,
        ];
    }
}
