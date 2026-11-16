<?php

namespace App\Services\Integrations;

use App\Models\IntegrationSetting;
use App\Models\WebhookEvent;
use App\Services\Integrations\Sandbox\SandboxAdapter;
use Illuminate\Support\Str;

/**
 * Instagram via supported Meta Graph / webhook APIs only.
 *
 * Supported in Phase 4:
 * - Instagram Messaging webhooks delivered through the Meta App (instagram object / messages)
 *
 * Explicitly unsupported (not scraped / not unofficial):
 * - Organic comment scraping
 * - Story mention scraping without official webhook subscription
 * - Direct Instagram login / private API
 * - Unofficial “WhatsApp Web” style Instagram clients
 */
class InstagramService
{
    public const PROVIDER = 'instagram';

    /** @var list<string> */
    public const UNSUPPORTED_FEATURES = [
        'Organic feed / hashtag comment scraping',
        'Story mention capture without Meta webhook subscription',
        'Unofficial Instagram private APIs or browser automation',
        'DM sync via personal Instagram login cookies',
    ];

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
        return $this->setting()->isLiveReady($this->setting()->requiredCredentialKeys());
    }

    /**
     * @return list<string>
     */
    public function unsupportedFeatures(): array
    {
        return self::UNSUPPORTED_FEATURES;
    }

    public function verifyChallenge(string $mode, string $token, string $challenge): ?string
    {
        $expected = $this->setting()->credentials()['verify_token']
            ?? config('phoenix.integrations.instagram.verify_token');

        if ($mode === 'subscribe' && filled($expected) && hash_equals((string) $expected, $token)) {
            return $challenge;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $headers
     */
    public function handleWebhook(array $payload, string $rawBody, array $headers = [], bool $queue = true): WebhookEvent
    {
        $setting = $this->setting();
        $creds = $setting->credentials();
        $appSecret = $creds['app_secret'] ?? config('phoenix.integrations.instagram.app_secret');
        $signature = $headers['x-hub-signature-256'] ?? $headers['X-Hub-Signature-256'] ?? null;

        $usedSandbox = ! $this->isLiveConfigured();
        $signatureValid = null;

        if (! $usedSandbox) {
            $signatureValid = $this->signatures->verifyMetaHubSignature(
                is_array($signature) ? ($signature[0] ?? null) : $signature,
                $rawBody,
                $appSecret
            );
            if (! $signatureValid) {
                $event = $this->logger->record(self::PROVIDER, $payload, null, 'signature_failed', false, $headers);
                $event->markRejected('Invalid X-Hub-Signature-256');

                return $event;
            }
        }

        $object = $payload['object'] ?? null;

        // Only process official Instagram messaging objects.
        if ($object && $object !== 'instagram' && $object !== 'page') {
            $event = $this->logger->record(self::PROVIDER, $payload, 'unsupported-'.Str::uuid(), (string) $object, $signatureValid, $headers, $usedSandbox);
            $normalized = new NormalizedLeadPayload(
                channel: 'instagram',
                externalId: (string) $event->idempotency_key,
                message: 'Unsupported Instagram webhook object: '.$object,
                messageType: 'unsupported',
                isUnsupported: true,
                unsupportedReason: 'Webhook object ['.$object.'] is not in the supported Instagram Messaging set. '.
                    'Unsupported features: '.implode('; ', self::UNSUPPORTED_FEATURES),
                eventType: 'unsupported',
                raw: $payload,
            );
            $this->pipeline->process($event, $normalized);

            return $event->fresh();
        }

        $messages = $this->extractMessages($payload);
        $last = null;

        if ($messages === []) {
            $last = $this->logger->record(self::PROVIDER, $payload, 'ig-empty-'.Str::uuid(), 'empty', $signatureValid, $headers, $usedSandbox);
            $last->markProcessed();

            return $last;
        }

        foreach ($messages as $message) {
            $externalId = (string) ($message['id'] ?? $message['mid'] ?? Str::uuid());
            $event = $this->logger->record(
                self::PROVIDER,
                $message,
                $externalId,
                'instagram_message',
                $signatureValid ?? true,
                $headers,
                $usedSandbox,
                self::PROVIDER.':'.$externalId
            );

            if ($event->processed_at && ! $event->wasRecentlyCreated) {
                $last = $event;
                continue;
            }

            $normalized = $this->normalize($message, $usedSandbox);

            if ($queue && config('queue.default') !== 'sync') {
                $event->forceFill(['status' => WebhookEvent::STATUS_QUEUED])->save();
                \App\Jobs\ProcessWebhookEventJob::dispatch($event->id, $normalized);
            } else {
                $this->pipeline->process($event, $normalized);
            }

            $last = $event->fresh();
        }

        return $last ?? $this->logger->record(self::PROVIDER, $payload, null, 'empty', $signatureValid, $headers, $usedSandbox);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array<string, mixed>>
     */
    public function extractMessages(array $payload): array
    {
        $messages = [];

        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['messaging'] ?? [] as $messaging) {
                if (! empty($messaging['message'])) {
                    $msg = $messaging['message'];
                    $msg['sender'] = $messaging['sender']['id'] ?? null;
                    $messages[] = $msg;
                }
            }

            // Some IG payloads nest under changes → value → messages
            foreach ($entry['changes'] ?? [] as $change) {
                foreach (($change['value']['messages'] ?? []) as $msg) {
                    $messages[] = $msg;
                }
            }
        }

        return $messages;
    }

    /**
     * @param  array<string, mixed>  $message
     */
    public function normalize(array $message, bool $sandbox = false): NormalizedLeadPayload
    {
        if ($sandbox && blank($message['text'] ?? null) && blank($message['sender'] ?? null)) {
            return $this->sandbox->sampleInstagramPayload();
        }

        $text = $message['text'] ?? null;
        $hasAttachments = ! empty($message['attachments']);

        if ($text === null && $hasAttachments) {
            return new NormalizedLeadPayload(
                channel: 'instagram',
                externalId: (string) ($message['mid'] ?? $message['id'] ?? Str::uuid()),
                fullName: 'Instagram User',
                message: '[Instagram attachment]',
                messageType: 'attachment',
                senderHandle: $message['sender'] ?? null,
                meta: ['note' => 'Attachment logged; download not implemented'],
                raw: $message,
                isUnsupported: false,
                unsupportedReason: 'Instagram attachment media download is not implemented; interaction is logged only.',
                eventType: 'instagram_message',
            );
        }

        return new NormalizedLeadPayload(
            channel: 'instagram',
            externalId: (string) ($message['mid'] ?? $message['id'] ?? Str::uuid()),
            fullName: 'Instagram User',
            message: $text ?? '[Instagram message]',
            messageType: 'text',
            senderHandle: $message['sender'] ?? null,
            meta: ['sandbox' => $sandbox],
            raw: $message,
            eventType: 'instagram_message',
        );
    }
}
