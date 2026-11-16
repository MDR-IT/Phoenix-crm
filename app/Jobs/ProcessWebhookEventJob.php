<?php

namespace App\Jobs;

use App\Models\WebhookEvent;
use App\Services\Integrations\LeadIngestionPipeline;
use App\Services\Integrations\NormalizedLeadPayload;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessWebhookEventJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public function __construct(
        public int $webhookEventId,
        public NormalizedLeadPayload $payload,
    ) {}

    public function handle(LeadIngestionPipeline $pipeline): void
    {
        $event = WebhookEvent::query()->find($this->webhookEventId);
        if (! $event) {
            return;
        }

        if (in_array($event->status, [WebhookEvent::STATUS_PROCESSED, WebhookEvent::STATUS_DUPLICATE, WebhookEvent::STATUS_REJECTED], true)) {
            return;
        }

        try {
            $pipeline->process($event, $this->payload);
        } catch (\Throwable $e) {
            Log::warning('ProcessWebhookEventJob failed', [
                'webhook_event_id' => $event->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function backoff(): array
    {
        return [30, 120, 300, 900];
    }
}
