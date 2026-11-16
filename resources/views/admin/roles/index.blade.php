<x-app-layout>
    <x-slot name="header">Roles &amp; permissions</x-slot>

    <div class="mb-3">
        <h1 class="h4 mb-1">Roles</h1>
        <p class="text-secondary small mb-0">Permissions are configurable per role. Users cannot exceed their grants.</p>
    </div>

    <div class="pc-panel">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                <tr>
                    <th>Role</th>
                    <th>Permissions</th>
                    <th class="text-end">Actions</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($roles as $role)
                    <tr>
                        <td class="fw-semibold">{{ $role->name }}</td>
                        <td>
                            @forelse ($role->permissions as $permission)
                                <span class="badge badge-role mb-1">{{ $permission->name }}</span>
                            @empty
                                <span class="text-secondary">No permissions</span>
                            @endforelse
                        </td>
                        <td class="text-end">
                            @can('roles.update')
                                <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-sm btn-outline-secondary">Configure</a>
                            @endcan
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
