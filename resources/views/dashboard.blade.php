<x-app-layout>
    <x-slot name="header">Main dashboard</x-slot>

    <div class="mb-4">
        <h1 class="h3 mb-1">Welcome to Phoenix Capital CRM</h1>
        <p class="text-secondary mb-0">Follow-ups, tasks, and uncontacted reminders are live on the dashboard.</p>
    </div>

    <div class="row g-3 mb-4">
        @foreach ($kpis as $kpi)
            <div class="col-12 col-md-6 col-xl-3">
                <div class="pc-kpi">
                    <div class="label">{{ $kpi['label'] }}</div>
                    <div class="value">{{ $kpi['value'] }}</div>
                    <div class="hint">{{ $kpi['hint'] }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="pc-panel mb-4">
        <h2 class="h5 mb-2">Quick actions</h2>
        <div class="d-flex flex-wrap gap-2">
            @canany(['leads.create', 'leads.manage'])
                <a href="{{ route('leads.create') }}" class="btn btn-pc-gold">Capture lead</a>
            @endcanany
            @can('follow_ups.view')
                <a href="{{ route('follow-ups.index') }}" class="btn btn-pc-primary">Follow-up worklist</a>
            @endcan
            @can('tasks.view')
                <a href="{{ route('tasks.index') }}" class="btn btn-outline-secondary">Tasks @if(($openTaskCount ?? 0) > 0)<span class="badge text-bg-secondary">{{ $openTaskCount }}</span>@endif</a>
            @endcan
            @canany(['leads.view', 'leads.manage'])
                <a href="{{ route('leads.index') }}" class="btn btn-outline-secondary">Lead register</a>
                <a href="{{ route('leads.kanban') }}" class="btn btn-outline-secondary">Pipeline board</a>
            @endcanany
            @can('customers.view')
                <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">Customers</a>
            @endcan
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-4">
            <div class="pc-panel p-0 h-100">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                    <h2 class="h6 mb-0">Follow-ups today</h2>
                    @can('follow_ups.view')
                        <a href="{{ route('follow-ups.index', ['bucket' => 'today']) }}" class="small">View all</a>
                    @endcan
                </div>
                <ul class="list-group list-group-flush small">
                    @forelse ($followUpsToday as $fu)
                        <li class="list-group-item">
                            <a href="{{ route('leads.show', $fu->lead) }}">{{ $fu->lead?->reference }}</a>
                            · {{ $fu->typeLabel() }}
                            <div class="text-secondary">{{ $fu->scheduled_at->format('H:i') }} · {{ $fu->lead?->customer?->full_name }}</div>
                        </li>
                    @empty
                        <li class="list-group-item text-secondary">Nothing due today.</li>
                    @endforelse
                </ul>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="pc-panel p-0 h-100">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                    <h2 class="h6 mb-0">Overdue follow-ups</h2>
                    @can('follow_ups.view')
                        <a href="{{ route('follow-ups.index', ['bucket' => 'overdue']) }}" class="small">View all</a>
                    @endcan
                </div>
                <ul class="list-group list-group-flush small">
                    @forelse ($followUpsOverdue as $fu)
                        <li class="list-group-item">
                            <a href="{{ route('leads.show', $fu->lead) }}">{{ $fu->lead?->reference }}</a>
                            <span class="badge text-bg-danger">Overdue</span>
                            <div class="text-secondary">{{ $fu->scheduled_at->format('d M H:i') }} · {{ $fu->assignee?->name ?: 'Unassigned' }}</div>
                        </li>
                    @empty
                        <li class="list-group-item text-secondary">No overdue follow-ups.</li>
                    @endforelse
                </ul>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="pc-panel p-0 h-100">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                    <h2 class="h6 mb-0">Upcoming follow-ups</h2>
                    @can('follow_ups.view')
                        <a href="{{ route('follow-ups.index', ['bucket' => 'upcoming']) }}" class="small">View all</a>
                    @endcan
                </div>
                <ul class="list-group list-group-flush small">
                    @forelse ($followUpsUpcoming as $fu)
                        <li class="list-group-item">
                            <a href="{{ route('leads.show', $fu->lead) }}">{{ $fu->lead?->reference }}</a>
                            <div class="text-secondary">{{ $fu->scheduled_at->format('d M Y H:i') }} · {{ $fu->typeLabel() }}</div>
                        </li>
                    @empty
                        <li class="list-group-item text-secondary">Nothing scheduled ahead.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="pc-panel p-0">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                    <h2 class="h6 mb-0">Overdue tasks</h2>
                    @can('tasks.view')
                        <a href="{{ route('tasks.index', ['overdue' => 1]) }}" class="small">View all</a>
                    @endcan
                </div>
                <ul class="list-group list-group-flush small">
                    @forelse ($overdueTasks as $task)
                        <li class="list-group-item d-flex justify-content-between gap-2">
                            <div>
                                <div class="fw-semibold">{{ $task->title }}</div>
                                <div class="text-secondary">Due {{ $task->due_at->format('d M Y H:i') }} · {{ $task->assignee?->name ?: '—' }}</div>
                            </div>
                            @can('complete', $task)
                                <form method="POST" action="{{ route('tasks.complete', $task) }}">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-success">Done</button>
                                </form>
                            @endcan
                        </li>
                    @empty
                        <li class="list-group-item text-secondary">No overdue tasks.</li>
                    @endforelse
                </ul>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="pc-panel p-0">
                <div class="p-3 border-bottom">
                    <h2 class="h6 mb-0">Uncontacted leads (&gt; {{ $uncontactedHours }}h)</h2>
                </div>
                <ul class="list-group list-group-flush small">
                    @forelse ($uncontactedLeads as $lead)
                        <li class="list-group-item">
                            <a href="{{ route('leads.show', $lead) }}">{{ $lead->reference }}</a>
                            · {{ $lead->customer?->full_name }}
                            <div class="text-secondary">Created {{ $lead->created_at->diffForHumans() }} · {{ $lead->statusLabel() }}</div>
                        </li>
                    @empty
                        <li class="list-group-item text-secondary">All recent leads have been contacted.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>
