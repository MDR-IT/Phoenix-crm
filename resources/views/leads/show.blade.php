<x-app-layout>
    <x-slot name="header">Lead profile</x-slot>

    <div class="d-flex justify-content-between align-items-start mb-3 gap-2 flex-wrap">
        <div>
            <h1 class="h3 mb-1">{{ $lead->reference }}</h1>
            <p class="text-secondary mb-0">
                {{ $lead->customer?->full_name }} · {{ $lead->statusLabel() }} · {{ $lead->priorityLabel() }}
            </p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @can('follow_ups.create')
                <a href="{{ route('follow-ups.create', ['lead_id' => $lead->id]) }}" class="btn btn-pc-gold">Schedule follow-up</a>
            @endcan
            @can('tasks.create')
                <a href="{{ route('tasks.create', ['lead_id' => $lead->id]) }}" class="btn btn-outline-secondary">Add task</a>
            @endcan
            @can('update', $lead)
                <a href="{{ route('leads.edit', $lead) }}" class="btn btn-outline-secondary">Edit</a>
            @endcan
            @can('merge', App\Models\Lead::class)
                <a href="{{ route('leads.merge', $lead) }}" class="btn btn-outline-secondary">Merge</a>
            @endcan
            @can('archive', $lead)
                <form method="POST" action="{{ route('leads.destroy', $lead) }}" onsubmit="return confirm('Archive this lead?')">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-outline-danger">Archive</button>
                </form>
            @endcan
        </div>
    </div>

    @if(($duplicates ?? collect())->isNotEmpty())
        <div class="alert alert-warning">
            <strong>Possible duplicates:</strong>
            @foreach ($duplicates as $dup)
                <a href="{{ route('leads.show', $dup) }}">{{ $dup->reference }}</a>@if(!$loop->last), @endif
            @endforeach
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="pc-panel mb-3">
                <h2 class="h5">Customer</h2>
                <p class="mb-1"><a href="{{ route('customers.show', $lead->customer) }}">{{ $lead->customer?->full_name }}</a></p>
                <p class="mb-1">{{ $lead->customer?->phone }}</p>
                <p class="mb-1">{{ $lead->customer?->email ?: '—' }}</p>
                <p class="mb-0 small text-secondary">{{ $lead->county?->name ?: '—' }}{{ $lead->town ? ', '.$lead->town : '' }}</p>
            </div>

            <div class="pc-panel mb-3">
                <h2 class="h5">Lead details</h2>
                <dl class="row small mb-0">
                    <dt class="col-5">Product</dt><dd class="col-7">{{ $lead->product?->name ?: '—' }}</dd>
                    <dt class="col-5">Source</dt><dd class="col-7">{{ $lead->leadSource?->name ?: '—' }}</dd>
                    <dt class="col-5">Campaign</dt><dd class="col-7">{{ $lead->campaign ?: '—' }}</dd>
                    <dt class="col-5">Assignee</dt><dd class="col-7">{{ $lead->assignee?->name ?: 'Unassigned' }}</dd>
                    <dt class="col-5">Follow-up</dt><dd class="col-7">{{ optional($lead->follow_up_at)->format('d M Y H:i') ?: '—' }}</dd>
                    <dt class="col-5">Last contact</dt><dd class="col-7">{{ optional($lead->last_contacted_at)->format('d M Y H:i') ?: 'Never' }}</dd>
                    <dt class="col-5">Consent</dt><dd class="col-7">{{ $lead->consent_contact ? 'Yes' : 'No' }}</dd>
                    <dt class="col-5">Channel</dt><dd class="col-7">{{ $lead->channel }}</dd>
                    <dt class="col-5">Created by</dt><dd class="col-7">{{ $lead->creator?->name ?: '—' }}</dd>
                </dl>
                @if($lead->description)
                    <hr><p class="mb-0">{{ $lead->description }}</p>
                @endif
            </div>

            @can('notes.create')
                <div class="pc-panel mb-3">
                    <h2 class="h5">Add note</h2>
                    <form method="POST" action="{{ route('leads.notes.store', $lead) }}">
                        @csrf
                        <textarea name="body" class="form-control mb-2" rows="3" required placeholder="Call notes, observations…">{{ old('body') }}</textarea>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="is_pinned" value="1" id="pin-note">
                            <label class="form-check-label" for="pin-note">Pin note</label>
                        </div>
                        <button class="btn btn-pc-primary btn-sm">Save note</button>
                    </form>
                </div>
            @endcan

            @can('follow_ups.create')
                <div class="pc-panel mb-3">
                    <h2 class="h5">Quick follow-up</h2>
                    <form method="POST" action="{{ route('follow-ups.store') }}">
                        @csrf
                        <input type="hidden" name="lead_id" value="{{ $lead->id }}">
                        <input type="hidden" name="from_lead" value="1">
                        <select name="type" class="form-select mb-2">
                            @foreach ($followUpTypes as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <input type="datetime-local" name="scheduled_at" class="form-control mb-2" required value="{{ old('scheduled_at', now()->addDay()->format('Y-m-d\TH:i')) }}">
                        <select name="assigned_to" class="form-select mb-2">
                            @foreach ($agents as $agent)
                                <option value="{{ $agent->id }}" @selected((int) ($lead->assigned_to ?: auth()->id()) === (int) $agent->id)>{{ $agent->name }}</option>
                            @endforeach
                        </select>
                        <input type="text" name="notes" class="form-control mb-2" placeholder="Callback reason">
                        <button class="btn btn-pc-gold btn-sm">Schedule</button>
                    </form>
                </div>
            @endcan

            @can('tasks.create')
                <div class="pc-panel mb-3">
                    <h2 class="h5">Quick task</h2>
                    <form method="POST" action="{{ route('tasks.store') }}">
                        @csrf
                        <input type="hidden" name="lead_id" value="{{ $lead->id }}">
                        <input type="text" name="title" class="form-control mb-2" required placeholder="Task title">
                        <input type="datetime-local" name="due_at" class="form-control mb-2" value="{{ now()->addDay()->format('Y-m-d\TH:i') }}">
                        <select name="priority" class="form-select mb-2">
                            @foreach ($priorities as $key => $label)
                                <option value="{{ $key }}" @selected($key === 'medium')>{{ $label }}</option>
                            @endforeach
                        </select>
                        <select name="assigned_to" class="form-select mb-2">
                            @foreach ($agents as $agent)
                                <option value="{{ $agent->id }}" @selected((int) ($lead->assigned_to ?: auth()->id()) === (int) $agent->id)>{{ $agent->name }}</option>
                            @endforeach
                        </select>
                        <button class="btn btn-outline-secondary btn-sm">Create task</button>
                    </form>
                </div>
            @endcan

            @can('update', $lead)
                <div class="pc-panel mb-3">
                    <h2 class="h5">Move status</h2>
                    <form method="POST" action="{{ route('leads.status', $lead) }}">
                        @csrf
                        @method('PATCH')
                        <select name="status" class="form-select mb-2">
                            @foreach ($statuses as $key => $label)
                                <option value="{{ $key }}" @selected($lead->status === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <input type="text" name="notes" class="form-control mb-2" placeholder="Optional note">
                        <button class="btn btn-pc-primary btn-sm">Update status</button>
                    </form>
                </div>
            @endcan

            @can('assign', $lead)
                <div class="pc-panel">
                    <h2 class="h5">Assign</h2>
                    <form method="POST" action="{{ route('leads.assign', $lead) }}">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label">Mode</label>
                            <select name="mode" class="form-select" id="assign-mode">
                                <option value="manual">Manual</option>
                                <option value="round_robin">Round-robin</option>
                                <option value="product">By product</option>
                                <option value="region">By region</option>
                                <option value="team">By team</option>
                            </select>
                        </div>
                        <div class="mb-2" id="manual-assignee">
                            <label class="form-label">Assignee</label>
                            <select name="assigned_to" class="form-select">
                                <option value="">Unassigned</option>
                                @foreach ($agents as $agent)
                                    <option value="{{ $agent->id }}" @selected((int) $lead->assigned_to === (int) $agent->id)>{{ $agent->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2 d-none" id="team-picker">
                            <label class="form-label">Team</label>
                            <select name="team_id" class="form-select">
                                <option value="">—</option>
                                @foreach ($teams as $team)
                                    <option value="{{ $team->id }}">{{ $team->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <input type="text" name="notes" class="form-control mb-2" placeholder="Assignment note">
                        <button class="btn btn-pc-gold btn-sm">Assign</button>
                    </form>
                </div>
            @endcan
        </div>

        <div class="col-lg-8">
            <div class="pc-panel mb-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h5 mb-0">Activity timeline</h2>
                    <span class="small text-secondary">Chronological · includes notes, tasks, meetings</span>
                </div>
                <div class="pc-timeline">
                    @foreach ($timeline as $item)
                        <div class="pc-timeline-item {{ (($item['meta'] ?? [])['placeholder'] ?? false) ? 'pc-timeline-placeholder' : '' }}">
                            <div class="pc-timeline-badge">{{ $item['type_label'] }}</div>
                            <div class="pc-timeline-body">
                                <div class="fw-semibold">{{ $item['title'] }}</div>
                                @if($item['body'])
                                    <div class="small mt-1">{{ $item['body'] }}</div>
                                @endif
                                <div class="small text-secondary mt-1">
                                    @if($item['occurred_at'])
                                        {{ $item['occurred_at']->format('d M Y H:i') }}
                                    @else
                                        Coming in Phase 4
                                    @endif
                                    @if($item['user_name']) · {{ $item['user_name'] }}@endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <div class="pc-panel p-0 h-100">
                        <div class="p-3 border-bottom"><h2 class="h6 mb-0">Open follow-ups</h2></div>
                        <ul class="list-group list-group-flush small">
                            @forelse ($lead->followUps->whereIn('status', ['scheduled', 'missed']) as $fu)
                                <li class="list-group-item d-flex justify-content-between gap-2">
                                    <div>
                                        <div class="fw-semibold">{{ $fu->typeLabel() }}</div>
                                        <div class="text-secondary">{{ $fu->scheduled_at->format('d M Y H:i') }} · {{ $fu->assignee?->name ?: '—' }}</div>
                                        @if($fu->isOverdue())<span class="badge text-bg-danger">Overdue</span>@endif
                                    </div>
                                    @can('complete', $fu)
                                        <form method="POST" action="{{ route('follow-ups.complete', $fu) }}">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-success">Done</button>
                                        </form>
                                    @endcan
                                </li>
                            @empty
                                <li class="list-group-item text-secondary">No open follow-ups.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="pc-panel p-0 h-100">
                        <div class="p-3 border-bottom"><h2 class="h6 mb-0">Open tasks</h2></div>
                        <ul class="list-group list-group-flush small">
                            @forelse ($lead->tasks->where('status', 'open') as $task)
                                <li class="list-group-item d-flex justify-content-between gap-2">
                                    <div>
                                        <div class="fw-semibold">{{ $task->title }}</div>
                                        <div class="text-secondary">{{ optional($task->due_at)->format('d M Y H:i') ?: 'No deadline' }} · {{ $task->assignee?->name ?: '—' }}</div>
                                        @if($task->isOverdue())<span class="badge text-bg-danger">Overdue</span>@endif
                                    </div>
                                    @can('complete', $task)
                                        <form method="POST" action="{{ route('tasks.complete', $task) }}">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-success">Done</button>
                                        </form>
                                    @endcan
                                </li>
                            @empty
                                <li class="list-group-item text-secondary">No open tasks.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>

            <div class="pc-panel mb-3 p-0">
                <div class="p-3 border-bottom"><h2 class="h5 mb-0">Notes</h2></div>
                <ul class="list-group list-group-flush">
                    @forelse ($lead->noteEntries as $note)
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between gap-2">
                                <div>
                                    @if($note->is_pinned)<span class="badge text-bg-warning me-1">Pinned</span>@endif
                                    <div>{{ $note->body }}</div>
                                    <div class="small text-secondary mt-1">{{ $note->user?->name }} · {{ $note->created_at?->format('d M Y H:i') }}</div>
                                </div>
                                @can('delete', $note)
                                    <form method="POST" action="{{ route('notes.destroy', $note) }}" onsubmit="return confirm('Delete this note?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                @endcan
                            </div>
                        </li>
                    @empty
                        <li class="list-group-item text-secondary">No notes yet.</li>
                    @endforelse
                </ul>
            </div>

            <div class="pc-panel p-0">
                <div class="p-3 border-bottom"><h2 class="h5 mb-0">Audit history</h2></div>
                <div class="table-responsive">
                    <table class="table mb-0 small">
                        <thead><tr><th>Field</th><th>Old</th><th>New</th><th>User</th><th>When</th></tr></thead>
                        <tbody>
                        @forelse ($lead->audits as $audit)
                            <tr>
                                <td><code>{{ $audit->field }}</code></td>
                                <td>{{ $audit->old_value ?: '—' }}</td>
                                <td>{{ $audit->new_value ?: '—' }}</td>
                                <td>{{ $audit->user?->name ?: '—' }}</td>
                                <td>{{ $audit->created_at?->format('d M Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-secondary text-center py-3">No audit entries yet.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            const mode = document.getElementById('assign-mode');
            const manual = document.getElementById('manual-assignee');
            const team = document.getElementById('team-picker');
            if (mode) {
                mode.addEventListener('change', () => {
                    manual.classList.toggle('d-none', mode.value !== 'manual');
                    team.classList.toggle('d-none', mode.value !== 'team');
                });
            }
        </script>
    @endpush
</x-app-layout>
