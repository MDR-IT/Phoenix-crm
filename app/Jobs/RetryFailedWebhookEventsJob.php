<?php

namespace App\Jobs;

use App\Models\WebhookEvent;
use App\Services\Integrations\InstagramService;
use App\Services\Integrations\LeadIngestionPipeline;
use App\Services\Integrations\MetaLeadService;
use App\Services\Integrations\NormalizedLeadPayload;
use App\Services\Integrations\WebsiteLeadService;
use App\Services\Integrations\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class RetryFailedWebhookEventsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ?int $limit = 50,
        public ?int $webhookEventId = null,
    ) {}

    public function handle(
        LeadIngestionPipeline $pipeline,
        WhatsAppService $whatsApp,
        MetaLeadService $meta,
        InstagramService $instagram,
        WebsiteLeadService $website,
    ): int {
        $query = WebhookEvent::query()
            ->where('status', WebhookEvent::STATUS_FAILED)
            ->where('attempts', '<', 8)
            ->orderBy('id');

        if ($this->webhookEventId) {
            $query->where('id', $this->webhookEventId);
        } else {
            $query->where(function ($q) {
                $q->whereNull('next_retry_at')->orWhere('next_retry_at', '<=', now());
            })->limit($this->limit ?? 50);
        }

        $events = $query->get();

        $retried = 0;

        foreach ($events as $event) {
            try {
                $payload = $this->rebuildPayload($event, $whatsApp, $meta, $instagram, $website);
                if (! $payload) {
                    $event->markFailed('Unable to rebuild payload for retry', 60);
                    continue;
                }

                $pipeline->process($event, $payload);
                $retried++;
            } catch (\Throwable $e) {
                Log::warning('Webhook retry failed', [
                    'id' => $event->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $retried;
    }

    private function rebuildPayload(
        WebhookEvent $event,
        WhatsAppService $whatsApp,
        MetaLeadService $meta,
        InstagramService $instagram,
        WebsiteLeadService $website,
    ): ?NormalizedLeadPayload {
        $raw = $event->payload ?? [];

        return match ($event->provider) {
            'whatsapp' => $whatsApp->normalizeMessage($raw['entry'] ?? $raw, (bool) $event->used_sandbox),
            'meta' => $this->rebuildMeta($event, $meta, $raw),
            'instagram' => $instagram->normalize($raw, (bool) $event->used_sandbox),
            'website' => $website->normalize($raw, (string) ($event->external_id ?: 'retry-'.$event->id), (bool) $event->used_sandbox),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    private function rebuildMeta(WebhookEvent $event, MetaLeadService $meta, array $raw): ?NormalizedLeadPayload
    {
        if (($event->event_type ?? '') === 'leadgen') {
            $value = $raw['value'] ?? $raw;
            $leadgenId = (string) ($value['leadgen_id'] ?? $event->external_id);

            return new NormalizedLeadPayload(
                channel: 'facebook',
                externalId: $leadgenId,
                message: 'Facebook Lead Ad retry',
                messageType: 'lead_form',
                meta: $value,
                raw: $value,
                eventType: 'leadgen',
            );
        }

        $items = $meta->extractEvents(['entry' => [['messaging' => [$raw], 'changes' => [$raw]]]]);
        if ($items !== []) {
            return $items[0]['payload'];
        }

        return null;
    }
}
