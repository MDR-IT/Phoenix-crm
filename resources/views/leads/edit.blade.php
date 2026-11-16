<x-app-layout>
    <x-slot name="header">Edit lead</x-slot>

    <div class="mb-3">
        <h1 class="h3 mb-1">Edit {{ $lead->reference }}</h1>
        <p class="text-secondary mb-0">Changes are written to the audit history.</p>
    </div>

    <form method="POST" action="{{ route('leads.update', $lead) }}" class="pc-panel">
        @csrf
        @method('PUT')
        @include('leads._form', ['lead' => $lead])
        <div class="mt-3 d-flex gap-2">
            <button class="btn btn-pc-gold">Save changes</button>
            <a href="{{ route('leads.show', $lead) }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</x-app-layout>
