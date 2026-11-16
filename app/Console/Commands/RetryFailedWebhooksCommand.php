<?php

namespace App\Console\Commands;

use App\Jobs\RetryFailedWebhookEventsJob;
use Illuminate\Console\Command;

class RetryFailedWebhooksCommand extends Command
{
    protected $signature = 'phoenix:retry-failed-webhooks {--limit=50} {--sync : Run inline instead of queueing}';

    protected $description = 'Retry failed digital-channel webhook events';

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $job = new RetryFailedWebhookEventsJob($limit);

        if ($this->option('sync') || config('queue.default') === 'sync') {
            $count = $job->handle(
                app(\App\Services\Integrations\LeadIngestionPipeline::class),
                app(\App\Services\Integrations\WhatsAppService::class),
                app(\App\Services\Integrations\MetaLeadService::class),
                app(\App\Services\Integrations\InstagramService::class),
                app(\App\Services\Integrations\WebsiteLeadService::class),
            );
            $this->info("Retried {$count} failed webhook event(s).");
        } else {
            RetryFailedWebhookEventsJob::dispatch($limit);
            $this->info("Queued retry for up to {$limit} failed webhook event(s).");
        }

        return self::SUCCESS;
    }
}
