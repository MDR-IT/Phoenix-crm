<x-app-layout>
    <x-slot name="header">Edit customer</x-slot>

    <div class="mb-3">
        <h1 class="h3 mb-1">Edit {{ $customer->full_name }}</h1>
    </div>

    <form method="POST" action="{{ route('customers.update', $customer) }}" class="pc-panel">
        @csrf
        @method('PUT')
        @include('customers._form', ['customer' => $customer])
        <div class="mt-3 d-flex gap-2">
            <button class="btn btn-pc-gold">Save changes</button>
            <a href="{{ route('customers.show', $customer) }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</x-app-layout>
