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

            'socios.view', 'socios.create', 'socios.update', 'socios.delete',

            'actividades.view', 'actividades.create', 'actividades.update', 'actividades.delete',

            'inscripciones.view', 'inscripciones.create', 'inscripciones.delete',

            'grupos.view', 'grupos.create', 'grupos.update', 'grupos.delete',

            'comunicados.view', 'comunicados.create', 'comunicados.update', 'comunicados.delete',

            'cuotas.view', 'cuotas.create', 'cuotas.update', 'cuotas.delete',

            'pagos.view', 'pagos.create', 'pagos.delete',

            'subscriptions.manage',

            'admin.access',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions(Permission::all());

        $adminClub = Role::firstOrCreate(['name' => 'admin_club', 'guard_name' => 'web']);
        $adminClub->syncPermissions([
            'clubes.view', 'clubes.update',
            'socios.view', 'socios.create', 'socios.update', 'socios.delete',
            'actividades.view', 'actividades.create', 'actividades.update', 'actividades.delete',
            'inscripciones.view', 'inscripciones.create', 'inscripciones.delete',
            'grupos.view', 'grupos.create', 'grupos.update', 'grupos.delete',
            'comunicados.view', 'comunicados.create', 'comunicados.update', 'comunicados.delete',
            'cuotas.view', 'cuotas.create', 'cuotas.update', 'cuotas.delete',
            'pagos.view', 'pagos.create', 'pagos.delete',
            'subscriptions.manage',
            'feed.view', 'feed.create', 'feed.update', 'feed.delete',
        ]);

        $guia = Role::firstOrCreate(['name' => 'guia', 'guard_name' => 'web']);
        $guia->syncPermissions([
            'clubes.view',
            'socios.view',
            'actividades.view', 'actividades.create', 'actividades.update',
            'inscripciones.view',
            'grupos.view',
            'comunicados.view', 'comunicados.create', 'comunicados.update', 'comunicados.delete',
            'feed.view', 'feed.create', 'feed.update',
        ]);

        $socio = Role::firstOrCreate(['name' => 'socio', 'guard_name' => 'web']);
        $socio->syncPermissions([
            'feed.view', 'feed.create',
            'actividades.view',
            'inscripciones.view', 'inscripciones.create',
            'grupos.view',
            'comunicados.view',
        ]);
    }
}
