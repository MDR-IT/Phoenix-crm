<?php

namespace App\Services\Integrations;

use App\Models\ChannelInteraction;
use App\Models\IntegrationSetting;
use App\Models\Lead;
use App\Models\WebhookEvent;
use Illuminate\Support\Collection;

class IntegrationHealthService
{
    public function __construct(private readonly IntegrationSettingsService $settingsService) {}

    /**
     * @return array{settings: Collection<int, IntegrationSetting>, stats: array<string, mixed>, unsupported_instagram: list<string>}
     */
    public function dashboard(): array
    {
        $settings = $this->settingsService->ensureDefaults();

        $stats = [
            'webhooks_24h' => WebhookEvent::query()->where('created_at', '>=', now()->subDay())->count(),
            'processed_24h' => WebhookEvent::query()->where('status', WebhookEvent::STATUS_PROCESSED)->where('created_at', '>=', now()->subDay())->count(),
            'failed_open' => WebhookEvent::query()->where('status', WebhookEvent::STATUS_FAILED)->count(),
            'duplicates_24h' => WebhookEvent::query()->where('status', WebhookEvent::STATUS_DUPLICATE)->where('created_at', '>=', now()->subDay())->count(),
            'digital_leads_24h' => Lead::query()->whereIn('channel', ['whatsapp', 'facebook', 'instagram', 'website', 'messenger'])->where('created_at', '>=', now()->subDay())->count(),
            'interactions_24h' => ChannelInteraction::query()->where('created_at', '>=', now()->subDay())->count(),
            'by_provider' => WebhookEvent::query()
                ->selectRaw('provider, status, count(*) as total')
                ->groupBy('provider', 'status')
                ->get()
                ->groupBy('provider'),
        ];

        return [
            'settings' => $settings,
            'stats' => $stats,
            'unsupported_instagram' => InstagramService::UNSUPPORTED_FEATURES,
        ];
    }
}
