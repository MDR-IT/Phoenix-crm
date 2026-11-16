<?php

namespace App\Services\Integrations;

use App\Models\IntegrationSetting;
use App\Models\WebhookEvent;
use App\Services\Integrations\Sandbox\SandboxAdapter;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Meta Facebook Lead Ads + supported Messenger webhooks via official Graph API.
 *
 * Lead retrieval: GET https://graph.facebook.com/{version}/{leadgen-id}?access_token=…
 * Webhooks: page leadgen / messages fields on the Meta App dashboard.
 */
class MetaLeadService
{
    public const PROVIDER = 'meta';

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
        $expected = $this->setting()->credentials()['verify_token']
            ?? config('phoenix.integrations.meta.verify_token');

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
        $appSecret = $creds['app_secret'] ?? config('phoenix.integrations.meta.app_secret');
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
                $this->settingsService->markError(self::PROVIDER, 'Invalid webhook signature');

                return $event;
            }
        }

        $items = $this->extractEvents($payload);
        $last = null;

        if ($items === []) {
            $last = $this->logger->record(self::PROVIDER, $payload, 'meta-empty-'.Str::uuid(), 'empty', $signatureValid, $headers, $usedSandbox);
            $last->markProcessed();

            return $last;
        }

        foreach ($items as $item) {
            $externalId = $item['external_id'];
            $event = $this->logger->record(
                self::PROVIDER,
                $item['raw'],
                $externalId,
                $item['event_type'],
                $signatureValid ?? true,
                $headers,
                $usedSandbox,
                self::PROVIDER.':'.$externalId
            );

            if ($event->processed_at && ! $event->wasRecentlyCreated) {
                $last = $event;
                continue;
            }

            $normalized = $item['payload'];
            if ($usedSandbox && blank($normalized->phone) && blank($normalized->email)) {
                $normalized = $this->sandbox->sampleMetaLeadPayload($item['event_type']);
            }

            if ($queue && config('queue.default') !== 'sync') {
                $event->forceFill(['status' => WebhookEvent::STATUS_QUEUED])->save();
                \App\Jobs\ProcessWebhookEventJob::dispatch($event->id, $normalized);
            } else {
                // Enrich Lead Ads from Graph when live.
                if (! $usedSandbox && ($item['event_type'] ?? '') === 'leadgen') {
                    $normalized = $this->enrichLeadgen($normalized, $externalId, $creds['access_token'] ?? '');
                }
                $this->pipeline->process($event, $normalized);
            }

            $last = $event->fresh();
        }

        return $last ?? $this->logger->record(self::PROVIDER, $payload, null, 'empty', $signatureValid, $headers, $usedSandbox);
    }

    /**
     * Fetch leadgen field data from official Graph API.
     *
     * @return array<string, mixed>|null
     */
    public function fetchLeadgen(string $leadgenId, string $accessToken): ?array
    {
        $version = config('phoenix.integrations.meta.graph_version', 'v21.0');
        $url = self::GRAPH_BASE.'/'.$version.'/'.$leadgenId;

        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->timeout(20)
            ->get($url, ['fields' => 'created_time,field_data,ad_id,form_id,campaign_name']);

        if ($response->failed()) {
            return null;
        }

        return $response->json();
    }

    public function enrichLeadgen(NormalizedLeadPayload $payload, string $leadgenId, string $token): NormalizedLeadPayload
    {
        if (blank($token)) {
            return $payload;
        }

        $data = $this->fetchLeadgen($leadgenId, $token);
        if (! $data) {
            return $payload;
        }

        $fields = [];
        foreach ($data['field_data'] ?? [] as $field) {
            $name = strtolower((string) ($field['name'] ?? ''));
            $value = $field['values'][0] ?? null;
            $fields[$name] = $value;
        }

        $fullName = $fields['full_name'] ?? $fields['name'] ?? null;
        $first = $fields['first_name'] ?? null;
        $last = $fields['last_name'] ?? null;
        $phone = $fields['phone_number'] ?? $fields['phone'] ?? $payload->phone;
        $email = $fields['email'] ?? $payload->email;

        return new NormalizedLeadPayload(
            channel: 'facebook',
            externalId: $payload->externalId,
            firstName: $first,
            lastName: $last,
            fullName: $fullName,
            phone: $phone,
            email: $email,
            message: $payload->message ?? 'Facebook Lead Ad submission',
            messageType: 'lead_form',
            campaign: $data['campaign_name'] ?? $payload->campaign,
            consentContact: true,
            occurredAt: isset($data['created_time']) ? new \DateTimeImmutable($data['created_time']) : $payload->occurredAt,
            meta: array_merge($payload->meta, ['graph' => $data, 'fields' => $fields]),
            raw: $payload->raw,
            eventType: 'leadgen',
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array{external_id: string, event_type: string, payload: NormalizedLeadPayload, raw: array<string, mixed>}>
     */
    public function extractEvents(array $payload): array
    {
        $items = [];

        foreach ($payload['entry'] ?? [] as $entry) {
            // Lead Ads (page leadgen changes)
            foreach ($entry['changes'] ?? [] as $change) {
                if (($change['field'] ?? '') === 'leadgen') {
                    $value = $change['value'] ?? [];
                    $leadgenId = (string) ($value['leadgen_id'] ?? Str::uuid());
                    $items[] = [
                        'external_id' => $leadgenId,
                        'event_type' => 'leadgen',
                        'raw' => $change,
                        'payload' => new NormalizedLeadPayload(
                            channel: 'facebook',
                            externalId: $leadgenId,
                            message: 'Facebook Lead Ad (pending Graph enrichment)',
                            messageType: 'lead_form',
                            campaign: isset($value['ad_id']) ? 'ad:'.$value['ad_id'] : null,
                            meta: ['page_id' => $value['page_id'] ?? null, 'form_id' => $value['form_id'] ?? null],
                            raw: $value,
                            eventType: 'leadgen',
                        ),
                    ];
                }
            }

            // Supported Messenger messages on the Page
            foreach ($entry['messaging'] ?? [] as $messaging) {
                $mid = $messaging['message']['mid'] ?? ('msg-'.Str::uuid());
                $text = $messaging['message']['text'] ?? null;
                $sender = $messaging['sender']['id'] ?? null;

                if ($text === null && isset($messaging['message']['attachments'])) {
                    $items[] = [
                        'external_id' => (string) $mid,
                        'event_type' => 'messenger',
                        'raw' => $messaging,
                        'payload' => new NormalizedLeadPayload(
                            channel: 'messenger',
                            externalId: (string) $mid,
                            fullName: 'Messenger User',
                            message: '[Messenger attachment]',
                            messageType: 'attachment',
                            senderHandle: $sender,
                            meta: ['psid' => $sender],
                            raw: $messaging,
                            isUnsupported: false,
                            unsupportedReason: 'Messenger attachments are logged; file download not implemented in Phase 4.',
                            eventType: 'messenger',
                        ),
                    ];
                    continue;
                }

                if ($text === null) {
                    continue;
                }

                $items[] = [
                    'external_id' => (string) $mid,
                    'event_type' => 'messenger',
                    'raw' => $messaging,
                    'payload' => new NormalizedLeadPayload(
                        channel: 'messenger',
                        externalId: (string) $mid,
                        fullName: 'Messenger User',
                        message: $text,
                        messageType: 'text',
                        senderHandle: $sender,
                        phone: null,
                        meta: ['psid' => $sender],
                        raw: $messaging,
                        eventType: 'messenger',
                    ),
                ];
            }
        }

        return $items;
    }
}
