<x-app-layout>
    <x-slot name="header">WhatsApp interactions</x-slot>

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h4 mb-1">WhatsApp interactions</h1>
            <p class="text-secondary small mb-0">Inbound Cloud API messages linked to leads.</p>
        </div>
        <form method="GET" class="d-flex gap-2">
            <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Search body / sender">
            <button class="btn btn-sm btn-outline-secondary">Search</button>
        </form>
    </div>

    <div class="pc-panel">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                <tr>
                    <th>When</th>
                    <th>Sender</th>
                    <th>Type</th>
                    <th>Message</th>
                    <th>Lead</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($interactions as $row)
                    <tr>
                        <td class="small">{{ optional($row->occurred_at)->format('d M Y H:i') ?: '—' }}</td>
                        <td>{{ $row->sender_handle ?: '—' }}</td>
                        <td><span class="badge text-bg-light border">{{ $row->message_type }}</span></td>
                        <td>{{ \Illuminate\Support\Str::limit($row->body, 80) }}</td>
                        <td>
                            @if($row->lead)
                                <a href="{{ route('leads.show', $row->lead) }}">{{ $row->lead->reference }}</a>
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-secondary">No WhatsApp interactions yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $interactions->links() }}</div>
    </div>
</x-app-layout>
