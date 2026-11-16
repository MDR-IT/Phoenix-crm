<x-app-layout>
    <x-slot name="header">Add user</x-slot>

    <div class="pc-panel" style="max-width: 640px;">
        <h1 class="h4 mb-3">Create staff user</h1>
        <form method="POST" action="{{ route('admin.users.store') }}">
            @csrf
            @include('admin.users._form', ['user' => null])
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-pc-primary">Create user</button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</x-app-layout>
