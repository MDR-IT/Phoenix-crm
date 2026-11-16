<x-app-layout>
    <x-slot name="header">Main dashboard</x-slot>

    <div class="mb-4">
        <h1 class="h3 mb-1">Welcome to Phoenix Capital CRM</h1>
        <p class="text-secondary mb-0">Foundation phase is live. Lead, call centre, and integration modules arrive in later phases.</p>
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
        <h2 class="h5 mb-2">Phase 1 checklist</h2>
        <ul class="mb-0">
            <li>Authentication (login, logout, password reset)</li>
            <li>Role-based access for Phoenix Capital staff roles</li>
            <li>User and role administration for authorised staff</li>
            <li>Branded shell ready for CRM modules</li>
        </ul>
    </div>
</x-app-layout>
