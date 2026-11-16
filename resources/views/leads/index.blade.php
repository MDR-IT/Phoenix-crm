<x-app-layout>
    <x-slot name="header">Lead register</x-slot>

    <div class="d-flex justify-content-between align-items-center mb-3 gap-2 flex-wrap">
        <div>
            <h1 class="h3 mb-1">Lead register</h1>
            <p class="text-secondary mb-0">Search, filter, and export leads in your permitted scope.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @canany(['leads.create', 'leads.manage'])
                <a href="{{ route('leads.create') }}" class="btn btn-pc-gold">Capture lead</a>
            @endcanany
            <a href="{{ route('leads.kanban') }}" class="btn btn-outline-secondary">Pipeline</a>
            @canany(['leads.export', 'leads.manage'])
                <a href="{{ route('leads.export', request()->query() + ['format' => 'csv']) }}" class="btn btn-outline-secondary">CSV</a>
                <a href="{{ route('leads.export', request()->query() + ['format' => 'excel']) }}" class="btn btn-outline-secondary">Excel</a>
            @endcanany
        </div>
    </div>

    <form method="GET" class="pc-panel mb-3">
        <div class="row g-2">
            <div class="col-md-3">
                <label class="form-label">Search</label>
                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="Name, phone, ref">
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
                <label class="form-label">Priority</label>
                <select name="priority" class="form-select">
                    <option value="">All</option>
                    @foreach ($priorities as $key => $label)
                        <option value="{{ $key }}" @selected(($filters['priority'] ?? '') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Product</label>
                <select name="product_id" class="form-select">
                    <option value="">All</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}" @selected((string) ($filters['product_id'] ?? '') === (string) $product->id)>{{ $product->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Source</label>
                <select name="lead_source_id" class="form-select">
                    <option value="">All</option>
                    @foreach ($sources as $source)
                        <option value="{{ $source->id }}" @selected((string) ($filters['lead_source_id'] ?? '') === (string) $source->id)>{{ $source->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">County</label>
                <select name="county_id" class="form-select">
                    <option value="">All</option>
                    @foreach ($counties as $county)
                        <option value="{{ $county->id }}" @selected((string) ($filters['county_id'] ?? '') === (string) $county->id)>{{ $county->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Assignee</label>
                <select name="assigned_to" class="form-select">
                    <option value="">All</option>
                    @foreach ($agents as $agent)
                        <option value="{{ $agent->id }}" @selected((string) ($filters['assigned_to'] ?? '') === (string) $agent->id)>{{ $agent->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Archived</label>
                <select name="archived" class="form-select">
                    <option value="">Active</option>
                    <option value="1" @selected(($filters['archived'] ?? '') === '1')>Archived only</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-pc-primary w-100">Filter</button>
            </div>
        </div>
    </form>

    <div class="pc-panel p-0">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead>
                <tr>
                    <th>Reference</th>
                    <th>Customer</th>
                    <th>Phone</th>
                    <th>Product</th>
                    <th>Status</th>
                    <th>Priority</th>
                    <th>Assignee</th>
                    <th>Created</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse ($leads as $lead)
                    <tr>
                        <td><code>{{ $lead->reference }}</code></td>
                        <td>{{ $lead->customer?->full_name }}</td>
                        <td>{{ $lead->customer?->phone }}</td>
                        <td>{{ $lead->product?->name ?: '—' }}</td>
                        <td><span class="badge badge-role">{{ $lead->statusLabel() }}</span></td>
                        <td>{{ $lead->priorityLabel() }}</td>
                        <td>{{ $lead->assignee?->name ?: '—' }}</td>
                        <td>{{ $lead->created_at?->format('d M Y') }}</td>
                        <td class="text-end"><a href="{{ route('leads.show', $lead) }}" class="btn btn-sm btn-outline-secondary">Open</a></td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-secondary py-4">No leads match your filters.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $leads->links() }}</div>
</x-app-layout>
