<x-app-layout>
    <x-slot name="header">Configure role</x-slot>

    <div class="pc-panel" style="max-width: 720px;">
        <h1 class="h4 mb-2">{{ $role->name }}</h1>
        <p class="text-secondary small mb-4">Tick the permissions this role may use.</p>

        <form method="POST" action="{{ route('admin.roles.update', $role) }}">
            @csrf
            @method('PUT')

            <div class="row g-2 mb-4">
                @foreach ($permissions as $permission)
                    <div class="col-12 col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permission->name }}" id="perm_{{ $permission->id }}" @checked($role->hasPermissionTo($permission->name) || old('permissions') && in_array($permission->name, old('permissions'), true))>
                            <label class="form-check-label" for="perm_{{ $permission->id }}">{{ $permission->name }}</label>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-pc-primary">Save permissions</button>
                <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</x-app-layout>
