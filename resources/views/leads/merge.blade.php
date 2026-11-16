<x-app-layout>
    <x-slot name="header">Merge leads</x-slot>

    <div class="mb-3">
        <h1 class="h3 mb-1">Merge into {{ $primary->reference }}</h1>
        <p class="text-secondary mb-0">Secondary lead data fills blank fields on the primary lead, then the secondary is archived.</p>
    </div>

    <div class="pc-panel mb-3">
        <strong>Primary:</strong> {{ $primary->reference }} — {{ $primary->customer?->full_name }} ({{ $primary->customer?->phone }})
    </div>

    <form method="GET" class="pc-panel mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-8">
                <label class="form-label">Find secondary lead</label>
                <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Reference, name, phone">
            </div>
            <div class="col-md-4">
                <button class="btn btn-pc-primary w-100">Search</button>
            </div>
        </div>
    </form>

    <form method="POST" action="{{ route('leads.merge.store', $primary) }}" class="pc-panel">
        @csrf
        <label class="form-label">Secondary lead to merge (will be archived)</label>
        <select name="secondary_lead_id" class="form-select mb-3" required>
            <option value="">Select…</option>
            @foreach ($candidates as $candidate)
                <option value="{{ $candidate->id }}">
                    {{ $candidate->reference }} — {{ $candidate->customer?->full_name }} ({{ $candidate->customer?->phone }})
                </option>
            @endforeach
        </select>
        <div class="d-flex gap-2">
            <button class="btn btn-pc-gold" onclick="return confirm('Merge and archive the secondary lead?')">Merge leads</button>
            <a href="{{ route('leads.show', $primary) }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</x-app-layout>
