<?php

namespace App\Services\Integrations;

use App\Models\IntegrationSetting;
use App\Models\WebhookEvent;
use App\Services\Integrations\Sandbox\SandboxAdapter;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * WhatsApp Business Cloud API (official Graph API only).
 *
 * Outbound docs: POST https://graph.facebook.com/{version}/{phone-number-id}/messages
 * Webhooks: Meta Hub subscribe to whatsapp_business_account.
 */
class WhatsAppService
{
    public const PROVIDER = 'whatsapp';

    /** Official Graph API base — do not invent alternate hosts. */
    public const GRAPH_BASE = 'https://graph.facebook.com';

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

    public function verifyChallenge(string $mode, string $token, string $challenge): ?string
    {
        $setting = $this->setting();
        $expected = $setting->credentials()['verify_token']
            ?? config('phoenix.integrations.whatsapp.verify_token');

        if ($mode === 'subscribe' && filled($expected) && hash_equals((string) $expected, $token)) {
            return $challenge;
        }

        // Sandbox / pending: accept configured env token for local testing only.
        $envToken = config('phoenix.integrations.whatsapp.verify_token');
        if ($mode === 'subscribe' && filled($envToken) && hash_equals((string) $envToken, $token)) {
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
        $appSecret = $creds['app_secret'] ?? config('phoenix.integrations.whatsapp.app_secret');
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
                $event = $this->logger->record(
                    self::PROVIDER,
                    $payload,
                    null,
                    'signature_failed',
                    false,
                    $headers,
                    false
                );
                $event->markRejected('Invalid X-Hub-Signature-256');
                $this->settingsService->markError(self::PROVIDER, 'Invalid webhook signature');

                return $event;
            }
        }

        $messages = $this->extractMessages($payload);
        $lastEvent = null;

        if ($messages === []) {
            $lastEvent = $this->logger->record(
                self::PROVIDER,
                $payload,
                'status-'.Str::uuid(),
                'status_or_empty',
                $signatureValid,
                $headers,
                $usedSandbox
            );
            $lastEvent->markProcessed();

            return $lastEvent;
        }

        foreach ($messages as $message) {
            $externalId = $message['id'] ?? ('wa-'.hash('sha256', json_encode($message)));
            $event = $this->logger->record(
                self::PROVIDER,
                ['entry' => $message, 'root_object' => $payload['object'] ?? null],
                $externalId,
                'message',
                $signatureValid ?? true,
                $headers,
                $usedSandbox,
                self::PROVIDER.':'.$externalId
            );

            if (in_array($event->status, [WebhookEvent::STATUS_PROCESSED, WebhookEvent::STATUS_DUPLICATE], true)
                && $event->wasRecentlyCreated === false
                && $event->processed_at) {
                $lastEvent = $event;
                continue;
            }

            $normalized = $this->normalizeMessage($message, $usedSandbox);

            if ($queue && config('queue.default') !== 'sync') {
                $event->forceFill(['status' => WebhookEvent::STATUS_QUEUED])->save();
                \App\Jobs\ProcessWebhookEventJob::dispatch($event->id, $normalized);
            } else {
                $this->pipeline->process($event, $normalized);
            }

            $lastEvent = $event->fresh();
        }

        return $lastEvent ?? $this->logger->record(self::PROVIDER, $payload, null, 'empty', $signatureValid, $headers, $usedSandbox);
    }

    /**
     * Optional reply via official Cloud API messages endpoint.
     * Returns false when credentials are missing (sandbox cannot send live messages).
     *
     * @return array{ok: bool, sandbox: bool, response?: array<string, mixed>, error?: string}
     */
    public function sendTextMessage(string $toWaId, string $body): array
    {
        if (! $this->isLiveConfigured()) {
            return $this->sandbox->simulateWhatsAppSend($toWaId, $body);
        }

        $creds = $this->setting()->credentials();
        $version = config('phoenix.integrations.whatsapp.graph_version', 'v21.0');
        $url = self::GRAPH_BASE.'/'.$version.'/'.$creds['phone_number_id'].'/messages';

        try {
            $response = $this->http($creds['access_token'])->post($url, [
                'messaging_product' => 'whatsapp',
                'to' => $toWaId,
                'type' => 'text',
                'text' => ['body' => $body],
            ]);

            if ($response->failed()) {
                return ['ok' => false, 'sandbox' => false, 'error' => $response->body()];
            }

            return ['ok' => true, 'sandbox' => false, 'response' => $response->json() ?? []];
        } catch (\Throwable $e) {
            return ['ok' => false, 'sandbox' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array<string, mixed>>
     */
    public function extractMessages(array $payload): array
    {
        $messages = [];
        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $value = $change['value'] ?? [];
                foreach ($value['messages'] ?? [] as $msg) {
                    $msg['_contacts'] = $value['contacts'] ?? [];
                    $msg['_metadata'] = $value['metadata'] ?? [];
                    $messages[] = $msg;
                }
            }
        }

        return $messages;
    }

    /**
     * @param  array<string, mixed>  $message
     */
    public function normalizeMessage(array $message, bool $sandbox = false): NormalizedLeadPayload
    {
        if ($sandbox && blank($message['from'] ?? null)) {
            return $this->sandbox->sampleWhatsAppPayload();
        }

        $type = $message['type'] ?? 'text';
        $body = null;
        $unsupported = false;
        $reason = null;

        if ($type === 'text') {
            $body = $message['text']['body'] ?? null;
        } elseif ($type === 'button') {
            $body = $message['button']['text'] ?? null;
        } elseif ($type === 'interactive') {
            $body = $message['interactive']['button_reply']['title']
                ?? $message['interactive']['list_reply']['title']
                ?? null;
        } else {
            // Media / unsupported inbound types — still create a lead stub with note.
            $unsupported = ! in_array($type, ['text', 'button', 'interactive'], true);
            $reason = $unsupported ? "Inbound WhatsApp message type [{$type}] stored as interaction; media download not implemented in Phase 4." : null;
            $body = '[WhatsApp '.$type.' message]';
        }

        $profileName = $message['_contacts'][0]['profile']['name'] ?? null;
        $waId = $message['from'] ?? null;

        return new NormalizedLeadPayload(
            channel: 'whatsapp',
            externalId: (string) ($message['id'] ?? Str::uuid()),
            fullName: $profileName,
            phone: $waId ? '+'.$waId : null,
            message: $body,
            messageType: $type,
            senderHandle: $waId,
            occurredAt: isset($message['timestamp']) ? now()->setTimestamp((int) $message['timestamp']) : now(),
            meta: [
                'metadata' => $message['_metadata'] ?? [],
                'sandbox' => $sandbox,
            ],
            raw: $message,
            isUnsupported: false,
            unsupportedReason: $reason,
            eventType: 'whatsapp.message',
        );
    }

    private function http(string $token): PendingRequest
    {
        return Http::withToken($token)
            ->acceptJson()
            ->timeout(20);
    }
}
