<x-app-layout>
    <x-slot name="header">New task</x-slot>

    <div class="mb-3">
        <h1 class="h3 mb-1">Create task</h1>
        <p class="text-secondary mb-0">Assign work with an optional deadline and lead link.</p>
    </div>

    <div class="pc-panel" style="max-width: 640px;">
        <form method="POST" action="{{ route('tasks.store') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">Title</label>
                <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}" required>
                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Related lead (optional)</label>
                <select name="lead_id" class="form-select">
                    <option value="">None</option>
                    @foreach ($leads as $item)
                        <option value="{{ $item->id }}" @selected((int) old('lead_id', $lead?->id) === (int) $item->id)>
                            {{ $item->reference }} — {{ $item->customer?->full_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Assignee</label>
                <select name="assigned_to" class="form-select">
                    @foreach ($agents as $agent)
                        <option value="{{ $agent->id }}" @selected((int) old('assigned_to', $lead?->assigned_to ?: auth()->id()) === (int) $agent->id)>{{ $agent->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="row g-2 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Priority</label>
                    <select name="priority" class="form-select">
                        @foreach ($priorities as $key => $label)
                            <option value="{{ $key }}" @selected(old('priority', 'medium') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Deadline</label>
                    <input type="datetime-local" name="due_at" class="form-control" value="{{ old('due_at', now()->addDay()->format('Y-m-d\TH:i')) }}">
                </div>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-pc-gold">Create task</button>
                <a href="{{ route('tasks.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</x-app-layout>
