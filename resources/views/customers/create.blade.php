<x-app-layout>
    <x-slot name="header">Register customer</x-slot>

    <div class="mb-3">
        <h1 class="h3 mb-1">Register customer</h1>
        <p class="text-secondary mb-0">Create a unified customer record for lead linking.</p>
    </div>

    <form method="POST" action="{{ route('customers.store') }}" class="pc-panel">
        @csrf
        @include('customers._form')
        <div class="mt-3 d-flex gap-2">
            <button class="btn btn-pc-gold">Save customer</button>
            <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</x-app-layout>
