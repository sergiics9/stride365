<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'feed.view', 'feed.create', 'feed.update', 'feed.delete',
            'clubes.view', 'clubes.create', 'clubes.update', 'clubes.delete',
            'clubes.approve', 'clubes.reject',
            'subscriptions.manage',
            'admin.access',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions(Permission::all());

        $usuario = Role::firstOrCreate(['name' => 'usuario', 'guard_name' => 'web']);
        $usuario->syncPermissions([
            'feed.view',
        ]);

        Role::where('name', 'admin_club')->where('guard_name', 'web')->delete();
        Role::where('name', 'guia')->where('guard_name', 'web')->delete();
        Role::where('name', 'socio')->where('guard_name', 'web')->delete();
    }
}
