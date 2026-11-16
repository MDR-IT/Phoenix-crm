<?php

namespace App\Services\Integrations;

use App\Models\Activity;
use App\Models\ChannelInteraction;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\User;
use App\Models\WebhookEvent;
use App\Notifications\DigitalLeadReceivedNotification;
use App\Services\ActivityLogger;
use App\Services\DuplicateLeadDetector;
use App\Services\LeadAssignmentService;
use App\Services\LeadService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Common digital lead ingestion pipeline:
 * normalize → source → duplicate check → customer/lead → interaction → assign → notify → webhook log.
 * Auth / signature verification happen in controllers before this pipeline runs.
 */
class LeadIngestionPipeline
{
    public function __construct(
        private readonly LeadService $leadService,
        private readonly LeadAssignmentService $assignmentService,
        private readonly DuplicateLeadDetector $duplicateDetector,
        private readonly ActivityLogger $activityLogger,
        private readonly IntegrationSettingsService $settingsService,
    ) {}

    /**
     * @return array{lead: ?Lead, interaction: ?ChannelInteraction, webhook: WebhookEvent, duplicate: bool}
     */
    public function process(WebhookEvent $event, NormalizedLeadPayload $payload, ?User $systemActor = null): array
    {
        $event->markProcessing();

        if ($payload->isUnsupported) {
            $interaction = $this->storeUnsupportedInteraction($event, $payload);
            $event->markProcessed(null, $interaction->id);
            $this->settingsService->markSuccess($event->provider);

            return [
                'lead' => null,
                'interaction' => $interaction,
                'webhook' => $event->fresh(),
                'duplicate' => false,
            ];
        }

        // Idempotent: another event with same external id already created a lead.
        if ($event->status === WebhookEvent::STATUS_DUPLICATE || $this->isAlreadyProcessed($event)) {
            $prior = $this->findProcessedSibling($event);
            $event->markDuplicate($prior?->lead_id);

            return [
                'lead' => $prior?->lead,
                'interaction' => $prior?->channelInteraction,
                'webhook' => $event->fresh(),
                'duplicate' => true,
            ];
        }

        try {
            return DB::transaction(function () use ($event, $payload, $systemActor) {
                $actor = $systemActor ?? $this->resolveSystemActor();
                $source = $this->resolveSource($payload->channel);

                $duplicates = $this->duplicateDetector->findPossibleDuplicates(
                    $payload->phone,
                    null,
                    $payload->email
                );

                $data = $payload->toLeadData();
                $data['lead_source_id'] = $source?->id;
                $data['priority'] = 'medium';
                $data['status'] = 'new';

                $setting = $this->settingsService->get($event->provider);
                $strategy = $setting->config['assignment_strategy'] ?? 'round_robin';

                if ($strategy && $strategy !== 'none') {
                    $data['assignment_strategy'] = $strategy;
                }

                $result = $this->leadService->createFromChannel($data, $payload->channel, $actor, [
                    'external_id' => $payload->externalId,
                    'provider' => $event->provider,
                    'event_type' => $payload->eventType,
                    'sandbox' => $event->used_sandbox,
                ]);

                /** @var Lead $lead */
                $lead = $result['lead'];

                $interaction = ChannelInteraction::create([
                    'lead_id' => $lead->id,
                    'customer_id' => $lead->customer_id,
                    'webhook_event_id' => $event->id,
                    'channel' => $payload->channel,
                    'direction' => 'inbound',
                    'external_id' => $payload->externalId,
                    'message_type' => $payload->messageType,
                    'body' => $payload->message,
                    'sender_handle' => $payload->senderHandle,
                    'payload' => [
                        'meta' => $payload->meta,
                        'possible_duplicates' => $duplicates->pluck('reference')->all(),
                    ],
                    'is_unsupported' => false,
                    'occurred_at' => $payload->occurredAt ?? now(),
                ]);

                $this->activityLogger->log(
                    $lead,
                    Activity::TYPE_CHANNEL_INTERACTION,
                    ucfirst($payload->channel).' interaction',
                    $actor,
                    $payload->message,
                    $interaction,
                    [
                        'channel' => $payload->channel,
                        'external_id' => $payload->externalId,
                        'message_type' => $payload->messageType,
                        'sender_handle' => $payload->senderHandle,
                        'sandbox' => $event->used_sandbox,
                    ],
                    $payload->occurredAt
                );

                $this->notifyStaff($lead, $payload, $setting->config['notify_roles'] ?? []);

                $event->markProcessed($lead->id, $interaction->id);
                $this->settingsService->markSuccess($event->provider);

                return [
                    'lead' => $lead->fresh(['customer', 'assignee', 'leadSource']),
                    'interaction' => $interaction,
                    'webhook' => $event->fresh(),
                    'duplicate' => false,
                ];
            });
        } catch (\Throwable $e) {
            Log::error('Lead ingestion failed', [
                'webhook_event_id' => $event->id,
                'provider' => $event->provider,
                'error' => $e->getMessage(),
            ]);
            $event->markFailed($e->getMessage());
            $this->settingsService->markError($event->provider, $e->getMessage());

            throw $e;
        }
    }

    private function storeUnsupportedInteraction(WebhookEvent $event, NormalizedLeadPayload $payload): ChannelInteraction
    {
        return ChannelInteraction::create([
            'webhook_event_id' => $event->id,
            'channel' => $payload->channel,
            'direction' => 'inbound',
            'external_id' => $payload->externalId,
            'message_type' => $payload->messageType ?? 'unsupported',
            'body' => $payload->message,
            'sender_handle' => $payload->senderHandle,
            'payload' => $payload->meta,
            'is_unsupported' => true,
            'unsupported_reason' => $payload->unsupportedReason,
            'occurred_at' => $payload->occurredAt ?? now(),
        ]);
    }

    private function isAlreadyProcessed(WebhookEvent $event): bool
    {
        if (blank($event->external_id)) {
            return false;
        }

        return WebhookEvent::query()
            ->where('provider', $event->provider)
            ->where('external_id', $event->external_id)
            ->where('id', '!=', $event->id)
            ->whereIn('status', [WebhookEvent::STATUS_PROCESSED, WebhookEvent::STATUS_DUPLICATE])
            ->exists();
    }

    private function findProcessedSibling(WebhookEvent $event): ?WebhookEvent
    {
        if (blank($event->external_id)) {
            return null;
        }

        return WebhookEvent::query()
            ->with(['lead', 'channelInteraction'])
            ->where('provider', $event->provider)
            ->where('external_id', $event->external_id)
            ->where('id', '!=', $event->id)
            ->whereIn('status', [WebhookEvent::STATUS_PROCESSED, WebhookEvent::STATUS_DUPLICATE])
            ->latest('id')
            ->first();
    }

    private function resolveSource(string $channel): ?LeadSource
    {
        $code = match ($channel) {
            'whatsapp' => 'whatsapp',
            'facebook', 'messenger' => 'facebook',
            'instagram' => 'instagram',
            'website' => 'website',
            default => $channel,
        };

        return LeadSource::query()->where('code', $code)->where('is_active', true)->first();
    }

    private function resolveSystemActor(): User
    {
        $email = config('phoenix.integrations.system_user_email', 'integrations@phoenixcapital.local');
        $user = User::query()->where('email', $email)->first();

        if ($user) {
            return $user;
        }

        $admin = User::query()->role('Super Administrator')->first();
        if ($admin) {
            return $admin;
        }

        return User::query()->orderBy('id')->firstOrFail();
    }

    /**
     * @param  list<string>  $roles
     */
    private function notifyStaff(Lead $lead, NormalizedLeadPayload $payload, array $roles): void
    {
        $recipients = User::query()
            ->where('is_active', true)
            ->where(function ($q) use ($lead, $roles) {
                if ($lead->assigned_to) {
                    $q->where('id', $lead->assigned_to);
                }
                if ($roles !== []) {
                    $q->orWhereHas('roles', fn ($r) => $r->whereIn('name', $roles));
                }
            })
            ->get()
            ->unique('id');

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new DigitalLeadReceivedNotification($lead, $payload->channel, $payload->message));
    }
}
