<?php

namespace Database\Seeders;

use App\Models\Club;
use App\Models\ClubUser;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Usuarios de prueba para cada “rol” del sistema.
 *
 * Roles globales (Spatie): super_admin, usuario.
 * Roles por club (pivot club_user): admin_club, socio; guía = socio con is_guide=true.
 *
 * Contraseña común (solo entornos locales): password
 */
class DemoUsersSeeder extends Seeder
{
    public const DEMO_PASSWORD = 'password';

    public function run(): void
    {
        $super = User::query()->updateOrCreate(
            ['email' => 'superadmin@demo.local'],
            [
                'nombre' => 'Super',
                'apellido' => 'Admin',
                'password' => Hash::make(self::DEMO_PASSWORD),
                'fecha_alta' => now()->toDateString(),
                'estado' => 'activo',
            ]
        );
        $super->syncRoles(['super_admin']);

        $usuario = User::query()->updateOrCreate(
            ['email' => 'usuario@demo.local'],
            [
                'nombre' => 'Usuario',
                'apellido' => 'Feed',
                'password' => Hash::make(self::DEMO_PASSWORD),
                'fecha_alta' => now()->toDateString(),
                'estado' => 'activo',
            ]
        );
        $usuario->syncRoles(['usuario']);

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@demo.local'],
            [
                'nombre' => 'Admin',
                'apellido' => 'Club',
                'password' => Hash::make(self::DEMO_PASSWORD),
                'fecha_alta' => now()->toDateString(),
                'estado' => 'activo',
            ]
        );
        $admin->syncRoles(['usuario']);

        $socio = User::query()->updateOrCreate(
            ['email' => 'socio@demo.local'],
            [
                'nombre' => 'Socio',
                'apellido' => 'Prueba',
                'password' => Hash::make(self::DEMO_PASSWORD),
                'fecha_alta' => now()->toDateString(),
                'estado' => 'activo',
            ]
        );
        $socio->syncRoles(['usuario']);

        $guia = User::query()->updateOrCreate(
            ['email' => 'guia@demo.local'],
            [
                'nombre' => 'Guía',
                'apellido' => 'Prueba',
                'password' => Hash::make(self::DEMO_PASSWORD),
                'fecha_alta' => now()->toDateString(),
                'estado' => 'activo',
            ]
        );
        $guia->syncRoles(['usuario']);

        $club = Club::query()->updateOrCreate(
            ['slug' => 'club-demo-seed'],
            [
                'nombre' => 'Club Demo (semillas)',
                'descripcion' => 'Club de prueba generado por DemoUsersSeeder.',
                'email' => 'club-demo@demo.local',
                'active' => true,
                'application_status' => Club::STATUS_APPROVED,
                'requested_by' => $admin->id,
                'approved_by' => $super->id,
                'approved_at' => now(),
            ]
        );

        ClubUser::query()->updateOrCreate(
            [
                'user_id' => $admin->id,
                'club_id' => $club->id,
                'role' => ClubUser::ROLE_ADMIN,
            ],
            [
                'is_guide' => false,
                'status' => ClubUser::STATUS_ACTIVE,
                'subscription_name' => ClubUser::buildSubscriptionName('club', $club->id),
                'subscribed_at' => now(),
                'current_period_end' => now()->addYear(),
            ]
        );

        ClubUser::query()->updateOrCreate(
            [
                'user_id' => $socio->id,
                'club_id' => $club->id,
                'role' => ClubUser::ROLE_SOCIO,
            ],
            [
                'is_guide' => false,
                'status' => ClubUser::STATUS_ACTIVE,
                'subscription_name' => ClubUser::buildSubscriptionName('socio', $club->id),
                'joined_at' => now()->toDateString(),
                'subscribed_at' => now(),
                'current_period_end' => now()->addYear(),
            ]
        );

        ClubUser::query()->updateOrCreate(
            [
                'user_id' => $guia->id,
                'club_id' => $club->id,
                'role' => ClubUser::ROLE_SOCIO,
            ],
            [
                'is_guide' => true,
                'status' => ClubUser::STATUS_ACTIVE,
                'subscription_name' => ClubUser::buildSubscriptionName('socio', $club->id),
                'joined_at' => now()->toDateString(),
                'subscribed_at' => now(),
                'current_period_end' => now()->addYear(),
            ]
        );

        $this->command?->info('Demo users (password: '.self::DEMO_PASSWORD.'):');
        $this->command?->table(
            ['Email', 'Acceso'],
            [
                ['superadmin@demo.local', 'Spatie: super_admin'],
                ['usuario@demo.local', 'Spatie: usuario (sin club)'],
                ['admin@demo.local', 'Spatie: usuario + pivot admin_club del club demo'],
                ['socio@demo.local', 'Spatie: usuario + pivot socio activo'],
                ['guia@demo.local', 'Spatie: usuario + pivot socio activo + is_guide'],
            ]
        );
        $this->command?->info('Club demo slug: '.$club->slug.' (id '.$club->id.')');
    }
}
