<x-app-layout>
    <x-slot name="header">Schedule follow-up</x-slot>

    <div class="mb-3">
        <h1 class="h3 mb-1">Schedule follow-up</h1>
        <p class="text-secondary mb-0">Callbacks, follow-ups, and meetings against a lead.</p>
    </div>

    <div class="pc-panel" style="max-width: 640px;">
        <form method="POST" action="{{ route('follow-ups.store') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">Lead</label>
                <select name="lead_id" class="form-select @error('lead_id') is-invalid @enderror" required>
                    <option value="">Select lead…</option>
                    @foreach ($leads as $item)
                        <option value="{{ $item->id }}" @selected((int) old('lead_id', $lead?->id) === (int) $item->id)>
                            {{ $item->reference }} — {{ $item->customer?->full_name }}
                        </option>
                    @endforeach
                </select>
                @error('lead_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label">Type</label>
                <select name="type" class="form-select">
                    @foreach ($types as $key => $label)
                        <option value="{{ $key }}" @selected(old('type') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Scheduled at</label>
                <input type="datetime-local" name="scheduled_at" class="form-control @error('scheduled_at') is-invalid @enderror"
                       value="{{ old('scheduled_at', now()->addDay()->format('Y-m-d\TH:i')) }}" required>
                @error('scheduled_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label">Assignee</label>
                <select name="assigned_to" class="form-select">
                    @foreach ($agents as $agent)
                        <option value="{{ $agent->id }}" @selected((int) old('assigned_to', $lead?->assigned_to ?: auth()->id()) === (int) $agent->id)>{{ $agent->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-pc-gold">Schedule</button>
                <a href="{{ route('follow-ups.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</x-app-layout>
