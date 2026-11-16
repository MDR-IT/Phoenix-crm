<x-app-layout>
    <x-slot name="header">Webhook #{{ $event->id }}</x-slot>

    <div class="mb-3">
        <a href="{{ route('integrations.webhooks.index') }}" class="small">&larr; Webhook monitor</a>
        <h1 class="h4 mb-1 mt-2">Webhook event #{{ $event->id }}</h1>
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="pc-panel mb-3">
                <dl class="row small mb-0">
                    <dt class="col-5">Provider</dt><dd class="col-7">{{ $event->provider }}</dd>
                    <dt class="col-5">Event type</dt><dd class="col-7">{{ $event->event_type ?: '—' }}</dd>
                    <dt class="col-5">Status</dt><dd class="col-7">{{ $event->status }}</dd>
                    <dt class="col-5">External ID</dt><dd class="col-7 text-break">{{ $event->external_id ?: '—' }}</dd>
                    <dt class="col-5">Idempotency</dt><dd class="col-7 text-break">{{ $event->idempotency_key }}</dd>
                    <dt class="col-5">Signature</dt><dd class="col-7">{{ $event->signature_valid === null ? '—' : ($event->signature_valid ? 'Valid' : 'Invalid') }}</dd>
                    <dt class="col-5">Sandbox</dt><dd class="col-7">{{ $event->used_sandbox ? 'Yes (pending configuration)' : 'No (live credentials)' }}</dd>
                    <dt class="col-5">Attempts</dt><dd class="col-7">{{ $event->attempts }}</dd>
                    <dt class="col-5">Lead</dt>
                    <dd class="col-7">
                        @if($event->lead)
                            <a href="{{ route('leads.show', $event->lead) }}">{{ $event->lead->reference }}</a>
                        @else
                            —
                        @endif
                    </dd>
                    <dt class="col-5">Error</dt><dd class="col-7 text-danger">{{ $event->last_error ?: '—' }}</dd>
                </dl>

                @can('integrations.manage')
                    @if($event->status === 'failed')
                        <form method="POST" action="{{ route('integrations.webhooks.retry', $event) }}" class="mt-3">
                            @csrf
                            <button class="btn btn-sm btn-pc-gold">Retry now</button>
                        </form>
                    @endif
                @endcan
            </div>
        </div>
        <div class="col-lg-7">
            <div class="pc-panel">
                <h2 class="h6">Payload</h2>
                <pre class="small mb-0" style="white-space: pre-wrap; max-height: 420px; overflow: auto;">{{ json_encode($event->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>
        </div>
    </div>
</x-app-layout>
