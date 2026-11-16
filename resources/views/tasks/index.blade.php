<x-app-layout>
    <x-slot name="header">Task management</x-slot>

    <div class="d-flex justify-content-between align-items-start mb-3 gap-2 flex-wrap">
        <div>
            <h1 class="h3 mb-1">Tasks</h1>
            <p class="text-secondary mb-0">
                Create, assign, and complete work items.
                @if($overdueCount > 0)
                    <span class="badge text-bg-danger">{{ $overdueCount }} overdue</span>
                @endif
            </p>
        </div>
        @can('tasks.create')
            <a href="{{ route('tasks.create') }}" class="btn btn-pc-gold">New task</a>
        @endcan
    </div>

    <div class="pc-panel mb-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Search</label>
                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="Title or description">
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
            <div class="col-md-2">
                <label class="form-label">Due</label>
                <select name="due" class="form-select">
                    <option value="">Any</option>
                    <option value="today" @selected(($filters['due'] ?? '') === 'today')>Today</option>
                    <option value="upcoming" @selected(($filters['due'] ?? '') === 'upcoming')>Upcoming</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Overdue only</label>
                <select name="overdue" class="form-select">
                    <option value="">No</option>
                    <option value="1" @selected(($filters['overdue'] ?? '') === '1')>Yes</option>
                </select>
            </div>
            @can('tasks.view_all')
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
    </div>

    <div class="pc-panel p-0">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead>
                <tr>
                    <th>Task</th>
                    <th>Related</th>
                    <th>Assignee</th>
                    <th>Priority</th>
                    <th>Due</th>
                    <th>Status</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse ($tasks as $task)
                    <tr class="{{ $task->isOverdue() ? 'table-danger' : '' }}">
                        <td>
                            <div class="fw-semibold">{{ $task->title }}</div>
                            <div class="small text-secondary">{{ \Illuminate\Support\Str::limit($task->description, 80) }}</div>
                        </td>
                        <td>
                            @if($task->taskable instanceof \App\Models\Lead)
                                <a href="{{ route('leads.show', $task->taskable) }}">{{ $task->taskable->reference }}</a>
                            @elseif($task->taskable instanceof \App\Models\Customer)
                                <a href="{{ route('customers.show', $task->taskable) }}">{{ $task->taskable->full_name }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $task->assignee?->name ?: '—' }}</td>
                        <td>{{ $task->priorityLabel() }}</td>
                        <td>{{ optional($task->due_at)->format('d M Y H:i') ?: '—' }}</td>
                        <td>{{ config('phoenix.task_statuses.'.$task->status, $task->status) }}</td>
                        <td class="text-end text-nowrap">
                            @can('complete', $task)
                                @if($task->status === 'open')
                                    <form method="POST" action="{{ route('tasks.complete', $task) }}" class="d-inline">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-success">Complete</button>
                                    </form>
                                @endif
                            @endcan
                            @can('update', $task)
                                <a href="{{ route('tasks.edit', $task) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-secondary py-4">No tasks found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($tasks->hasPages())
            <div class="p-3">{{ $tasks->links() }}</div>
        @endif
    </div>
</x-app-layout>
