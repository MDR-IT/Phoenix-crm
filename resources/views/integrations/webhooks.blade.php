<x-app-layout>
    <x-slot name="header">Webhook monitor</x-slot>

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h4 mb-1">Webhook monitoring</h1>
            <p class="text-secondary small mb-0">Idempotent ingress log for WhatsApp, Meta, Instagram, and website.</p>
        </div>
        <form method="GET" class="d-flex gap-2 flex-wrap">
            <select name="provider" class="form-select form-select-sm">
                <option value="">All providers</option>
                @foreach (['whatsapp','meta','instagram','website'] as $p)
                    <option value="{{ $p }}" @selected($provider === $p)>{{ $p }}</option>
                @endforeach
            </select>
            <select name="status" class="form-select form-select-sm">
                <option value="">All statuses</option>
                @foreach (['received','queued','processing','processed','failed','duplicate','rejected'] as $s)
                    <option value="{{ $s }}" @selected($status === $s)>{{ $s }}</option>
                @endforeach
            </select>
            <button class="btn btn-sm btn-outline-secondary">Filter</button>
        </form>
    </div>

    <div class="pc-panel">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Provider</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Sandbox</th>
                    <th>Attempts</th>
                    <th>Lead</th>
                    <th>When</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($events as $event)
                    <tr>
                        <td><a href="{{ route('integrations.webhooks.show', $event) }}">#{{ $event->id }}</a></td>
                        <td>{{ $event->provider }}</td>
                        <td class="small">{{ $event->event_type ?: '—' }}</td>
                        <td><span class="badge text-bg-light border">{{ $event->status }}</span></td>
                        <td>{{ $event->used_sandbox ? 'Yes' : 'No' }}</td>
                        <td>{{ $event->attempts }}</td>
                        <td>
                            @if($event->lead)
                                <a href="{{ route('leads.show', $event->lead) }}">{{ $event->lead->reference }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td class="small">{{ $event->created_at?->format('d M Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-secondary">No webhook events yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $events->links() }}</div>
    </div>
</x-app-layout>
