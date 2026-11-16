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
                    <dt class="col-5">Consent</dt><dd class="col-7">{{ $lead->consent_contact ? 'Yes' : 'No' }}</dd>
                    <dt class="col-5">Channel</dt><dd class="col-7">{{ $lead->channel }}</dd>
                    <dt class="col-5">Created by</dt><dd class="col-7">{{ $lead->creator?->name ?: '—' }}</dd>
                </dl>
                @if($lead->description)
                    <hr><p class="mb-0">{{ $lead->description }}</p>
                @endif
                @if($lead->notes)
                    <hr><p class="mb-0"><strong>Notes:</strong> {{ $lead->notes }}</p>
                @endif
            </div>

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
            <div class="pc-panel mb-3 p-0">
                <div class="p-3 border-bottom"><h2 class="h5 mb-0">Status history</h2></div>
                <div class="table-responsive">
                    <table class="table mb-0 small">
                        <thead><tr><th>From</th><th>To</th><th>By</th><th>When</th><th>Notes</th></tr></thead>
                        <tbody>
                        @forelse ($lead->statusHistories as $row)
                            <tr>
                                <td>{{ config('phoenix.lead_statuses.'.$row->from_status, $row->from_status ?: '—') }}</td>
                                <td>{{ config('phoenix.lead_statuses.'.$row->to_status, $row->to_status) }}</td>
                                <td>{{ $row->user?->name ?: '—' }}</td>
                                <td>{{ $row->created_at?->format('d M Y H:i') }}</td>
                                <td>{{ $row->notes }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-secondary text-center py-3">No status changes yet.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="pc-panel mb-3 p-0">
                <div class="p-3 border-bottom"><h2 class="h5 mb-0">Assignment history</h2></div>
                <div class="table-responsive">
                    <table class="table mb-0 small">
                        <thead><tr><th>From</th><th>To</th><th>By</th><th>Method</th><th>When</th></tr></thead>
                        <tbody>
                        @forelse ($lead->assignmentHistories as $row)
                            <tr>
                                <td>{{ $row->fromUser?->name ?: '—' }}</td>
                                <td>{{ $row->toUser?->name ?: '—' }}</td>
                                <td>{{ $row->assigner?->name ?: '—' }}</td>
                                <td>{{ $row->method }}</td>
                                <td>{{ $row->created_at?->format('d M Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-secondary text-center py-3">No assignments yet.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
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
