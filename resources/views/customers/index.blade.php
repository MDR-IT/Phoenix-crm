<x-app-layout>
    <x-slot name="header">Customers</x-slot>

    <div class="d-flex justify-content-between align-items-center mb-3 gap-2 flex-wrap">
        <div>
            <h1 class="h3 mb-1">Customer register</h1>
            <p class="text-secondary mb-0">Unified customer profiles with normalised phone matching.</p>
        </div>
        @can('customers.create')
            <a href="{{ route('customers.create') }}" class="btn btn-pc-gold">Register customer</a>
        @endcan
    </div>

    <form method="GET" class="pc-panel mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-8">
                <label class="form-label">Search</label>
                <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Name, phone, email">
            </div>
            <div class="col-md-4">
                <button class="btn btn-pc-primary w-100">Search</button>
            </div>
        </div>
    </form>

    <div class="pc-panel p-0">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>County</th>
                    <th>Leads</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse ($customers as $customer)
                    <tr>
                        <td>{{ $customer->full_name }}</td>
                        <td>{{ $customer->phone }}</td>
                        <td>{{ $customer->email ?: '—' }}</td>
                        <td>{{ $customer->county?->name ?: '—' }}</td>
                        <td>{{ $customer->leads_count }}</td>
                        <td class="text-end">
                            <a href="{{ route('customers.show', $customer) }}" class="btn btn-sm btn-outline-secondary">Open</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-secondary py-4">No customers found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $customers->links() }}</div>
</x-app-layout>
