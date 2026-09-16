<?php

namespace App\Services;

/**
 * Store-review accounts: fixed phone + code pairs that skip the SMS gateway so
 * App Store / Google Play reviewers can sign in without a Turkmen SIM card.
 * Disabled unless OTP_TEST_PHONES and OTP_TEST_CODE are both set.
 * Always disabled in production.
 */
class OtpTestAccountService
{
    /**
     * Whether the phone is configured as a store-review bypass account.
     */
    public function isTestPhone(string $phone): bool
    {
        if (app()->isProduction()) {
            return false;
        }

        return in_array($this->digits($phone), $this->allowedPhones(), true);
    }

    /**
     * Whether the submitted code matches the configured test bypass code.
     */
    public function matches(string $phone, string $code): bool
    {
        $expected = (string) config('services.otp.test_code');

        if ($expected === '' || ! $this->isTestPhone($phone)) {
            return false;
        }

        return hash_equals($expected, $code);
    }

    /** @return list<string> */
    private function allowedPhones(): array
    {
        return array_values(array_filter(array_map(
            fn (string $phone): string => $this->digits($phone),
            (array) config('services.otp.test_phones', []),
        )));
    }

    private function digits(string $phone): string
    {
        return (string) preg_replace('/\D/', '', $phone);
    }
}
