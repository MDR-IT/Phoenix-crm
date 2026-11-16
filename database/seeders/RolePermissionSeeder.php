<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (config('phoenix.permissions') as $permission) {
            Permission::findOrCreate($permission);
        }

        $allPermissions = Permission::all();

        foreach (config('phoenix.roles') as $roleName) {
            $role = Role::findOrCreate($roleName);
            $grants = config("phoenix.role_permissions.{$roleName}", []);

            if (in_array('*', $grants, true)) {
                $role->syncPermissions($allPermissions);
                continue;
            }

            $role->syncPermissions($grants);
        }
    }
}
