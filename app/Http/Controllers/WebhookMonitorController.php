<?php

namespace App\Http\Controllers;

use App\Jobs\RetryFailedWebhookEventsJob;
use App\Models\WebhookEvent;
use Illuminate\Http\Request;

class WebhookMonitorController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(
            auth()->user()?->can('integrations.view') || auth()->user()?->can('integrations.manage'),
            403
        );

        $query = WebhookEvent::query()
            ->with(['lead'])
            ->orderByDesc('id');

        if ($provider = $request->string('provider')->toString()) {
            $query->where('provider', $provider);
        }
        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        return view('integrations.webhooks', [
            'events' => $query->paginate(30)->withQueryString(),
            'provider' => $provider,
            'status' => $status,
        ]);
    }

    public function show(WebhookEvent $webhook)
    {
        abort_unless(
            auth()->user()?->can('integrations.view') || auth()->user()?->can('integrations.manage'),
            403
        );

        $webhook->load(['lead.customer', 'channelInteraction']);

        return view('integrations.webhook-show', [
            'event' => $webhook,
        ]);
    }

    public function retry(WebhookEvent $webhook)
    {
        abort_unless(auth()->user()?->can('integrations.manage'), 403);

        if ($webhook->status !== WebhookEvent::STATUS_FAILED) {
            return back()->with('warning', 'Only failed events can be retried.');
        }

        $webhook->forceFill(['next_retry_at' => now()])->save();

        $count = (new RetryFailedWebhookEventsJob(1, $webhook->id))->handle(
            app(\App\Services\Integrations\LeadIngestionPipeline::class),
            app(\App\Services\Integrations\WhatsAppService::class),
            app(\App\Services\Integrations\MetaLeadService::class),
            app(\App\Services\Integrations\InstagramService::class),
            app(\App\Services\Integrations\WebsiteLeadService::class),
        );

        return back()->with(
            $count > 0 ? 'success' : 'warning',
            $count > 0 ? 'Webhook event retried.' : 'Retry did not process the event.'
        );
    }
}
