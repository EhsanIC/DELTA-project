<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Seed the application's roles and permissions.
     */
    public function run(): void
    {
        $permissions = [
            'create-opportunity',
            'edit-opportunity',
            'manage-inventory',
            'manage-capacity',
            'manage-finance',
            'view-dashboard',
            'view-alerts',
            'view-events',
            'view-settings',
            'edit-settings',
        ];

        foreach ($permissions as $permissionName) {
            Permission::query()->firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $rolePermissions = [
            'sales' => [
                'create-opportunity',
                'edit-opportunity',
                'view-dashboard',
                'view-alerts',
            ],
            'operations' => [
                'manage-inventory',
                'manage-capacity',
                'view-dashboard',
                'view-alerts',
            ],
            'finance' => [
                'manage-finance',
                'view-dashboard',
                'view-alerts',
            ],
            'admin' => $permissions,
        ];

        foreach ($rolePermissions as $roleName => $permissionNames) {
            $role = Role::query()->firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);
            $role->syncPermissions($permissionNames);
        }
    }
}
