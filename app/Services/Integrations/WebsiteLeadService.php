<?php

namespace App\Services\Integrations;

use App\Models\IntegrationSetting;
use App\Models\WebhookEvent;
use App\Services\Integrations\Sandbox\SandboxAdapter;
use Illuminate\Support\Str;

/**
 * Secure website form lead capture for phoenixcapital site embeds.
 */
class WebsiteLeadService
{
    public const PROVIDER = 'website';

    public function __construct(
        private readonly IntegrationSettingsService $settingsService,
        private readonly WebhookEventLogger $logger,
        private readonly SignatureVerifier $signatures,
        private readonly SandboxAdapter $sandbox,
        private readonly LeadIngestionPipeline $pipeline,
    ) {}

    public function setting(): IntegrationSetting
    {
        return $this->settingsService->get(self::PROVIDER);
    }

    public function isLiveConfigured(): bool
    {
        return $this->setting()->isLiveReady(['api_key']);
    }

    public function expectedApiKey(): ?string
    {
        return $this->setting()->credentials()['api_key']
            ?? config('phoenix.integrations.website.api_key');
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $headers
     */
    public function handleSubmission(array $input, array $headers = [], bool $queue = true): WebhookEvent
    {
        $apiKey = $headers['x-phoenix-api-key']
            ?? $headers['X-Phoenix-Api-Key']
            ?? ($input['api_key'] ?? null);

        if (is_array($apiKey)) {
            $apiKey = $apiKey[0] ?? null;
        }

        // Honeypot spam protection
        if (filled($input['website_url'] ?? null) || filled($input['company_fax'] ?? null)) {
            $event = $this->logger->record(self::PROVIDER, $input, 'spam-'.Str::uuid(), 'spam', false, $headers);
            $event->markRejected('Honeypot field filled — treated as spam');

            return $event;
        }

        $expected = $this->expectedApiKey();
        $usedSandbox = ! $this->isLiveConfigured();

        // When live credentials exist, API key is mandatory.
        if (! $usedSandbox) {
            $valid = $this->signatures->verifyWebsiteApiKey(
                is_string($apiKey) ? $apiKey : null,
                $expected
            );
            if (! $valid) {
                $event = $this->logger->record(self::PROVIDER, $this->redact($input), null, 'auth_failed', false, $headers);
                $event->markRejected('Invalid or missing API key');
                $this->settingsService->markError(self::PROVIDER, 'Invalid website API key');

                return $event;
            }
        } elseif (filled($expected) && filled($apiKey)) {
            // Sandbox with env key still validates when a key is provided.
            if (! $this->signatures->verifyWebsiteApiKey((string) $apiKey, $expected)) {
                $event = $this->logger->record(self::PROVIDER, $this->redact($input), null, 'auth_failed', false, $headers, true);
                $event->markRejected('Invalid API key');

                return $event;
            }
        }

        $externalId = (string) ($input['idempotency_key'] ?? $input['external_id'] ?? ('web-'.Str::uuid()));
        $event = $this->logger->record(
            self::PROVIDER,
            $this->redact($input),
            $externalId,
            'form_submit',
            true,
            $headers,
            $usedSandbox,
            self::PROVIDER.':'.$externalId
        );

        if (! $event->wasRecentlyCreated && in_array($event->status, [
            WebhookEvent::STATUS_PROCESSED,
            WebhookEvent::STATUS_DUPLICATE,
            WebhookEvent::STATUS_QUEUED,
            WebhookEvent::STATUS_PROCESSING,
        ], true)) {
            $event->setAttribute('idempotent_replay', true);

            return $event;
        }

        $normalized = $this->normalize($input, $externalId, $usedSandbox);

        if ($queue && config('queue.default') !== 'sync') {
            $event->forceFill(['status' => WebhookEvent::STATUS_QUEUED])->save();
            \App\Jobs\ProcessWebhookEventJob::dispatch($event->id, $normalized);
        } else {
            $this->pipeline->process($event, $normalized);
        }

        return $event->fresh();
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function normalize(array $input, string $externalId, bool $sandbox = false): NormalizedLeadPayload
    {
        if ($sandbox && blank($input['phone'] ?? null) && blank($input['email'] ?? null) && blank($input['first_name'] ?? null)) {
            return $this->sandbox->sampleWebsitePayload();
        }

        $fullName = $input['full_name'] ?? null;
        if (! $fullName && filled($input['first_name'] ?? null)) {
            $fullName = trim(($input['first_name'] ?? '').' '.($input['last_name'] ?? ''));
        }

        return new NormalizedLeadPayload(
            channel: 'website',
            externalId: $externalId,
            firstName: $input['first_name'] ?? null,
            lastName: $input['last_name'] ?? null,
            fullName: $fullName,
            phone: $input['phone'] ?? null,
            email: $input['email'] ?? null,
            message: $input['message'] ?? $input['notes'] ?? 'Website form submission',
            messageType: 'form',
            campaign: $input['campaign'] ?? $input['utm_campaign'] ?? null,
            productHint: $input['product'] ?? null,
            consentContact: filter_var($input['consent'] ?? $input['consent_contact'] ?? true, FILTER_VALIDATE_BOOLEAN),
            occurredAt: now(),
            meta: [
                'utm_source' => $input['utm_source'] ?? null,
                'utm_medium' => $input['utm_medium'] ?? null,
                'page_url' => $input['page_url'] ?? null,
                'sandbox' => $sandbox,
            ],
            raw: $this->redact($input),
            eventType: 'form_submit',
        );
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function redact(array $input): array
    {
        unset($input['api_key']);

        return $input;
    }
}
