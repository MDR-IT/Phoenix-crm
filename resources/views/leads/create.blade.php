<x-app-layout>
    <x-slot name="header">Capture lead</x-slot>

    <div class="mb-3">
        <h1 class="h3 mb-1">Manual lead capture</h1>
        <p class="text-secondary mb-0">Enter a new lead. Duplicate phones are flagged before save.</p>
    </div>

    <form method="POST" action="{{ route('leads.store') }}" class="pc-panel">
        @csrf
        @include('leads._form')
        <div class="mt-3 d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-pc-gold">Save</button>
            <button type="submit" name="save_and_another" value="1" class="btn btn-pc-primary">Save and another</button>
            <a href="{{ route('leads.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</x-app-layout>
