<x-app-layout>
    <x-slot name="header">Main dashboard</x-slot>

    <div class="mb-4">
        <h1 class="h3 mb-1">Welcome to Phoenix Capital CRM</h1>
        <p class="text-secondary mb-0">Core CRM is live — capture, assign, and move leads through the Phoenix pipeline.</p>
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

    <div class="pc-panel">
        <h2 class="h5 mb-2">Quick actions</h2>
        <div class="d-flex flex-wrap gap-2">
            @canany(['leads.create', 'leads.manage'])
                <a href="{{ route('leads.create') }}" class="btn btn-pc-gold">Capture lead</a>
            @endcanany
            @canany(['leads.view', 'leads.manage'])
                <a href="{{ route('leads.index') }}" class="btn btn-pc-primary">Lead register</a>
                <a href="{{ route('leads.kanban') }}" class="btn btn-outline-secondary">Pipeline board</a>
            @endcanany
            @can('customers.view')
                <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">Customers</a>
            @endcan
        </div>
    </div>
</x-app-layout>
