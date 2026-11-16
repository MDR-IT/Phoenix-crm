<?php

namespace App\Services\Integrations;

/**
 * Meta / WhatsApp Cloud API webhook signature verification (X-Hub-Signature-256).
 * Official algorithm: HMAC-SHA256 of raw body with app secret, prefixed with "sha256=".
 */
class SignatureVerifier
{
    public function verifyMetaHubSignature(?string $signatureHeader, string $rawBody, ?string $appSecret): bool
    {
        if (blank($signatureHeader) || blank($appSecret)) {
            return false;
        }

        $expected = 'sha256='.hash_hmac('sha256', $rawBody, $appSecret);

        return hash_equals($expected, $signatureHeader);
    }

    public function verifyWebsiteApiKey(?string $provided, ?string $expected): bool
    {
        if (blank($provided) || blank($expected)) {
            return false;
        }

        return hash_equals((string) $expected, (string) $provided);
    }
}
