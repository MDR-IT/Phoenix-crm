<x-app-layout>
    <x-slot name="header">Edit task</x-slot>

    <div class="mb-3">
        <h1 class="h3 mb-1">Edit task</h1>
        <p class="text-secondary mb-0">{{ $task->title }}</p>
    </div>

    <div class="pc-panel" style="max-width: 640px;">
        <form method="POST" action="{{ route('tasks.update', $task) }}">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label class="form-label">Title</label>
                <input type="text" name="title" class="form-control" value="{{ old('title', $task->title) }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3">{{ old('description', $task->description) }}</textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Assignee</label>
                <select name="assigned_to" class="form-select">
                    @foreach ($agents as $agent)
                        <option value="{{ $agent->id }}" @selected((int) old('assigned_to', $task->assigned_to) === (int) $agent->id)>{{ $agent->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="row g-2 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Priority</label>
                    <select name="priority" class="form-select">
                        @foreach ($priorities as $key => $label)
                            <option value="{{ $key }}" @selected(old('priority', $task->priority) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Deadline</label>
                    <input type="datetime-local" name="due_at" class="form-control" value="{{ old('due_at', optional($task->due_at)->format('Y-m-d\TH:i')) }}">
                </div>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-pc-primary">Save</button>
                <a href="{{ route('tasks.index') }}" class="btn btn-outline-secondary">Back</a>
            </div>
        </form>
    </div>
</x-app-layout>
