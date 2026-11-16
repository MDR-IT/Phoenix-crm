<x-app-layout>
    <x-slot name="header">Edit user</x-slot>

    <div class="pc-panel" style="max-width: 640px;">
        <h1 class="h4 mb-3">Edit {{ $user->name }}</h1>
        <form method="POST" action="{{ route('admin.users.update', $user) }}">
            @csrf
            @method('PUT')
            @include('admin.users._form', ['user' => $user])
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-pc-primary">Save changes</button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</x-app-layout>
