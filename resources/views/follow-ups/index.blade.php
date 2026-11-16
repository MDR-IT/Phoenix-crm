<x-app-layout>
    <x-slot name="header">Follow-up worklist</x-slot>

    <div class="d-flex justify-content-between align-items-start mb-3 gap-2 flex-wrap">
        <div>
            <h1 class="h3 mb-1">Follow-ups &amp; callbacks</h1>
            <p class="text-secondary mb-0">Today, overdue, and upcoming work across your leads.</p>
        </div>
        @can('follow_ups.create')
            <a href="{{ route('follow-ups.create') }}" class="btn btn-pc-gold">Schedule follow-up</a>
        @endcan
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="pc-kpi">
                <div class="label">Today</div>
                <div class="value">{{ $counts['today'] }}</div>
                <div class="hint">Due before midnight</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="pc-kpi">
                <div class="label">Overdue</div>
                <div class="value">{{ $counts['overdue'] }}</div>
                <div class="hint">Missed or past due</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="pc-kpi">
                <div class="label">Upcoming</div>
                <div class="value">{{ $counts['upcoming'] }}</div>
                <div class="hint">After today</div>
            </div>
        </div>
    </div>

    <div class="pc-panel mb-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Search</label>
                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="Lead, phone, notes">
            </div>
            <div class="col-md-2">
                <label class="form-label">Bucket</label>
                <select name="bucket" class="form-select">
                    <option value="">All open / filtered</option>
                    <option value="today" @selected(($filters['bucket'] ?? '') === 'today')>Today</option>
                    <option value="overdue" @selected(($filters['bucket'] ?? '') === 'overdue')>Overdue</option>
                    <option value="upcoming" @selected(($filters['bucket'] ?? '') === 'upcoming')>Upcoming</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Type</label>
                <select name="type" class="form-select">
                    <option value="">All</option>
                    @foreach ($types as $key => $label)
                        <option value="{{ $key }}" @selected(($filters['type'] ?? '') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    @foreach ($statuses as $key => $label)
                        <option value="{{ $key }}" @selected(($filters['status'] ?? '') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            @can('follow_ups.view_all')
                <div class="col-md-2">
                    <label class="form-label">Assignee</label>
                    <select name="assigned_to" class="form-select">
                        <option value="">All</option>
                        @foreach ($agents as $agent)
                            <option value="{{ $agent->id }}" @selected((string) ($filters['assigned_to'] ?? '') === (string) $agent->id)>{{ $agent->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endcan
            <div class="col-md-1">
                <button class="btn btn-pc-primary w-100">Filter</button>
            </div>
        </form>
        <div class="pc-bucket-tabs d-flex flex-wrap gap-2 mt-3">
            <a href="{{ route('follow-ups.index', ['bucket' => 'today']) }}" class="btn btn-sm btn-outline-secondary {{ ($filters['bucket'] ?? '') === 'today' ? 'active' : '' }}">Today ({{ $counts['today'] }})</a>
            <a href="{{ route('follow-ups.index', ['bucket' => 'overdue']) }}" class="btn btn-sm btn-outline-secondary {{ ($filters['bucket'] ?? '') === 'overdue' ? 'active' : '' }}">Overdue ({{ $counts['overdue'] }})</a>
            <a href="{{ route('follow-ups.index', ['bucket' => 'upcoming']) }}" class="btn btn-sm btn-outline-secondary {{ ($filters['bucket'] ?? '') === 'upcoming' ? 'active' : '' }}">Upcoming ({{ $counts['upcoming'] }})</a>
        </div>
    </div>

    <div class="pc-panel p-0">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead>
                <tr>
                    <th>When</th>
                    <th>Lead</th>
                    <th>Type</th>
                    <th>Assignee</th>
                    <th>Status</th>
                    <th>Notes</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse ($followUps as $followUp)
                    <tr class="{{ $followUp->isOverdue() ? 'table-danger' : '' }}">
                        <td>{{ $followUp->scheduled_at->format('d M Y H:i') }}</td>
                        <td>
                            <a href="{{ route('leads.show', $followUp->lead) }}">{{ $followUp->lead?->reference }}</a>
                            <div class="small text-secondary">{{ $followUp->lead?->customer?->full_name }}</div>
                        </td>
                        <td>{{ $followUp->typeLabel() }}</td>
                        <td>{{ $followUp->assignee?->name ?: '—' }}</td>
                        <td>{{ $followUp->statusLabel() }}</td>
                        <td class="small">{{ \Illuminate\Support\Str::limit($followUp->notes, 60) }}</td>
                        <td class="text-end text-nowrap">
                            @can('complete', $followUp)
                                @if(in_array($followUp->status, ['scheduled', 'missed'], true))
                                    <form method="POST" action="{{ route('follow-ups.complete', $followUp) }}" class="d-inline">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-success">Complete</button>
                                    </form>
                                @endif
                            @endcan
                            @can('update', $followUp)
                                <a href="{{ route('follow-ups.edit', $followUp) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-secondary py-4">No follow-ups in this view.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($followUps->hasPages())
            <div class="p-3">{{ $followUps->links() }}</div>
        @endif
    </div>
</x-app-layout>
