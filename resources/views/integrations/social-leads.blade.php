<x-app-layout>
    <x-slot name="header">Social / digital leads</x-slot>

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h4 mb-1">Social &amp; digital leads</h1>
            <p class="text-secondary small mb-0">Leads captured from WhatsApp, Meta, Instagram, and website forms.</p>
        </div>
        <form method="GET" class="d-flex gap-2 flex-wrap">
            <select name="channel" class="form-select form-select-sm">
                <option value="">All channels</option>
                @foreach ($channels as $ch)
                    <option value="{{ $ch }}" @selected($channel === $ch)>{{ $ch }}</option>
                @endforeach
            </select>
            <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Search">
            <button class="btn btn-sm btn-outline-secondary">Filter</button>
        </form>
    </div>

    <div class="pc-panel">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                <tr>
                    <th>Reference</th>
                    <th>Customer</th>
                    <th>Channel</th>
                    <th>Source</th>
                    <th>Status</th>
                    <th>Assignee</th>
                    <th>Created</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($leads as $lead)
                    <tr>
                        <td><a href="{{ route('leads.show', $lead) }}">{{ $lead->reference }}</a></td>
                        <td>{{ $lead->customer?->full_name }}<div class="small text-secondary">{{ $lead->customer?->phone }}</div></td>
                        <td><span class="badge text-bg-light border">{{ $lead->channel }}</span></td>
                        <td>{{ $lead->leadSource?->name ?: '—' }}</td>
                        <td>{{ $lead->statusLabel() }}</td>
                        <td>{{ $lead->assignee?->name ?: 'Unassigned' }}</td>
                        <td class="small">{{ $lead->created_at?->format('d M Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-secondary">No digital leads yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $leads->links() }}</div>
    </div>
</x-app-layout>
