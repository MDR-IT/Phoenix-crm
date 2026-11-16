<x-app-layout>
    <x-slot name="header">Edit follow-up</x-slot>

    <div class="mb-3">
        <h1 class="h3 mb-1">Edit follow-up</h1>
        <p class="text-secondary mb-0">
            {{ $followUp->lead?->reference }} · {{ $followUp->lead?->customer?->full_name }}
        </p>
    </div>

    <div class="pc-panel" style="max-width: 640px;">
        <form method="POST" action="{{ route('follow-ups.update', $followUp) }}">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label class="form-label">Type</label>
                <select name="type" class="form-select">
                    @foreach ($types as $key => $label)
                        <option value="{{ $key }}" @selected(old('type', $followUp->type) === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Scheduled at</label>
                <input type="datetime-local" name="scheduled_at" class="form-control"
                       value="{{ old('scheduled_at', $followUp->scheduled_at?->format('Y-m-d\TH:i')) }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Assignee</label>
                <select name="assigned_to" class="form-select">
                    @foreach ($agents as $agent)
                        <option value="{{ $agent->id }}" @selected((int) old('assigned_to', $followUp->assigned_to) === (int) $agent->id)>{{ $agent->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="3">{{ old('notes', $followUp->notes) }}</textarea>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-pc-primary">Save</button>
                <a href="{{ route('follow-ups.index') }}" class="btn btn-outline-secondary">Back</a>
            </div>
        </form>

        @can('complete', $followUp)
            @if(in_array($followUp->status, ['scheduled', 'missed'], true))
                <hr>
                <form method="POST" action="{{ route('follow-ups.complete', $followUp) }}" class="mb-2">
                    @csrf
                    <label class="form-label">Complete with outcome</label>
                    <textarea name="outcome" class="form-control mb-2" rows="2" placeholder="What happened on the call?"></textarea>
                    <button class="btn btn-outline-success btn-sm">Mark completed</button>
                </form>
                <form method="POST" action="{{ route('follow-ups.cancel', $followUp) }}">
                    @csrf
                    <button class="btn btn-outline-danger btn-sm" onclick="return confirm('Cancel this follow-up?')">Cancel follow-up</button>
                </form>
            @endif
        @endcan
    </div>
</x-app-layout>
