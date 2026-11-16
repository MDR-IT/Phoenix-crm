<?php

namespace App\Services\Integrations;

use App\Models\WebhookEvent;
use Illuminate\Support\Str;

class WebhookEventLogger
{
    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $headers
     */
    public function record(
        string $provider,
        array $payload,
        ?string $externalId = null,
        ?string $eventType = null,
        ?bool $signatureValid = null,
        array $headers = [],
        bool $usedSandbox = false,
        ?string $idempotencyKey = null,
    ): WebhookEvent {
        $key = $idempotencyKey ?: $this->buildIdempotencyKey($provider, $externalId, $payload);

        $existing = WebhookEvent::query()->where('idempotency_key', $key)->first();
        if ($existing) {
            return $existing;
        }

        return WebhookEvent::create([
            'provider' => $provider,
            'event_type' => $eventType,
            'external_id' => $externalId,
            'idempotency_key' => $key,
            'signature_valid' => $signatureValid,
            'headers' => $this->sanitizeHeaders($headers),
            'payload' => $payload,
            'status' => WebhookEvent::STATUS_RECEIVED,
            'used_sandbox' => $usedSandbox,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function buildIdempotencyKey(string $provider, ?string $externalId, array $payload): string
    {
        if (filled($externalId)) {
            return $provider.':'.$externalId;
        }

        return $provider.':'.hash('sha256', json_encode($payload) ?: Str::uuid()->toString());
    }

    /**
     * @param  array<string, mixed>  $headers
     * @return array<string, mixed>
     */
    private function sanitizeHeaders(array $headers): array
    {
        $allowed = [
            'content-type',
            'user-agent',
            'x-hub-signature',
            'x-hub-signature-256',
            'x-request-id',
            'x-phoenix-api-key',
            'x-forwarded-for',
        ];

        $out = [];
        foreach ($headers as $key => $value) {
            $lower = strtolower((string) $key);
            if (in_array($lower, $allowed, true)) {
                $out[$lower] = is_array($value) ? ($value[0] ?? null) : $value;
            }
        }

        return $out;
    }
}
