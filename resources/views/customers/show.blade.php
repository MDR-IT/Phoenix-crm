<x-app-layout>
    <x-slot name="header">Customer profile</x-slot>

    <div class="d-flex justify-content-between align-items-start mb-3 gap-2 flex-wrap">
        <div>
            <h1 class="h3 mb-1">{{ $customer->full_name }}</h1>
            <p class="text-secondary mb-0">{{ $customer->phone }} @if($customer->email)· {{ $customer->email }}@endif</p>
        </div>
        <div class="d-flex gap-2">
            @can('customers.update')
                <a href="{{ route('customers.edit', $customer) }}" class="btn btn-outline-secondary">Edit</a>
            @endcan
            @canany(['leads.create', 'leads.manage'])
                <a href="{{ route('leads.create', ['customer_id' => $customer->id]) }}" class="btn btn-pc-gold">New lead</a>
            @endcanany
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="pc-panel">
                <h2 class="h5">Profile</h2>
                <dl class="row mb-0 small">
                    <dt class="col-5">Gender</dt><dd class="col-7">{{ config('phoenix.genders.'.$customer->gender, '—') }}</dd>
                    <dt class="col-5">County</dt><dd class="col-7">{{ $customer->county?->name ?: '—' }}</dd>
                    <dt class="col-5">Town</dt><dd class="col-7">{{ $customer->town ?: '—' }}</dd>
                    <dt class="col-5">Alt phone</dt><dd class="col-7">{{ $customer->phone_alt ?: '—' }}</dd>
                    <dt class="col-5">Consent</dt><dd class="col-7">{{ $customer->consent_marketing ? 'Yes' : 'No' }}</dd>
                    <dt class="col-5">Normalised</dt><dd class="col-7"><code>{{ $customer->phone_normalized ?: '—' }}</code></dd>
                </dl>
                @if($customer->notes)
                    <hr>
                    <p class="mb-0">{{ $customer->notes }}</p>
                @endif
            </div>
        </div>
        <div class="col-lg-8">
            <div class="pc-panel p-0">
                <div class="p-3 border-bottom"><h2 class="h5 mb-0">Leads</h2></div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Status</th>
                            <th>Product</th>
                            <th>Assignee</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($customer->leads as $lead)
                            <tr>
                                <td>{{ $lead->reference }}</td>
                                <td>{{ $lead->statusLabel() }}</td>
                                <td>{{ $lead->product?->name ?: '—' }}</td>
                                <td>{{ $lead->assignee?->name ?: '—' }}</td>
                                <td class="text-end"><a href="{{ route('leads.show', $lead) }}">Open</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-secondary py-3">No leads yet.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
