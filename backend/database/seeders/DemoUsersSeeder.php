<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Crea 27 usuarios de demo con nombres españoles realistas.
 * Contraseña universal: password
 *
 * Roles globales (Spatie):
 *   super_admin → superadmin@stride.local
 *   usuario     → todos los demás
 */
class DemoUsersSeeder extends Seeder
{
    public const PASSWORD = 'password';

    /** [email, nombre, apellido, sexo, telefono, fecha_nacimiento, direccion, rol_global] */
    private const USERS = [
        // ── Super Admin ──────────────────────────────────────────────────
        ['superadmin@stride.local', 'Carlos',    'Ruiz Martínez',    'M', '600 100 001', '1980-03-15', 'Calle Mayor 1, Madrid',                  'super_admin'],

        // ── 10 Administradores de club ───────────────────────────────────
        ['admin1@stride.local',  'Miguel',    'Fernández López',  'M', '600 100 011', '1978-06-22', 'Calle Pinar 4, Cercedilla, Madrid',        'usuario'],
        ['admin2@stride.local',  'Ana',       'García Sánchez',   'F', '600 100 012', '1985-11-08', 'Passeig de Gràcia 90, Barcelona',          'usuario'],
        ['admin3@stride.local',  'Pedro',     'Martínez Gómez',   'M', '600 100 013', '1972-04-30', 'Calle Alhambra 8, Granada',                'usuario'],
        ['admin4@stride.local',  'Lucía',     'Jiménez Torres',   'F', '600 100 014', '1990-09-17', 'Paseo del Parque 3, Málaga',               'usuario'],
        ['admin5@stride.local',  'David',     'López Rodríguez',  'M', '600 100 015', '1983-01-25', 'Rúa do Franco 7, Santiago de Compostela',  'usuario'],
        ['admin6@stride.local',  'María',     'González Blanco',  'F', '600 100 016', '1988-07-12', 'Marina Real 22, Valencia',                 'usuario'],
        ['admin7@stride.local',  'Javier',    'Pérez Hernández',  'M', '600 100 017', '1975-12-03', 'Plaza Mayor 1, Jaca, Huesca',              'usuario'],
        ['admin8@stride.local',  'Elena',     'Sánchez Díaz',     'F', '600 100 018', '1992-05-19', 'Puerto Chico s/n, Santander',              'usuario'],
        ['admin9@stride.local',  'Alejandro', 'Flores Ruiz',      'M', '600 100 019', '1981-08-07', 'Calle Trujillo 14, Mérida',                'usuario'],
        ['admin10@stride.local', 'Carmen',    'Moreno Vargas',    'F', '600 100 020', '1986-02-14', 'Avenida Constitución 5, Sevilla',          'usuario'],

        // ── 14 Socios ────────────────────────────────────────────────────
        ['socio1@stride.local',  'Antonio',   'Ramos Cruz',       'M', '600 200 001', '1991-03-28', 'Calle Cervantes 12, Alcobendas',           'usuario'],
        ['socio2@stride.local',  'Isabel',    'Navarro Gil',      'F', '600 200 002', '1994-10-05', 'Avenida Diagonal 445, Barcelona',          'usuario'],
        ['socio3@stride.local',  'Roberto',   'Castro Medina',    'M', '600 200 003', '1987-07-16', 'Calle San Juan 33, Granada',               'usuario'],
        ['socio4@stride.local',  'Cristina',  'Ortega Campos',    'F', '600 200 004', '1996-01-22', 'Calle Larios 7, Málaga',                   'usuario'],
        ['socio5@stride.local',  'Fernando',  'Rubio Iglesias',   'M', '600 200 005', '1989-04-11', 'Calle del Obradoiro 2, Santiago',          'usuario'],
        ['socio6@stride.local',  'Sandra',    'Molina Serrano',   'F', '600 200 006', '1993-08-30', 'Calle Colón 15, Valencia',                 'usuario'],
        ['socio7@stride.local',  'Raúl',      'Cano Fuentes',     'M', '600 200 007', '1984-11-19', 'Avenida Pirineos 8, Jaca',                 'usuario'],
        ['socio8@stride.local',  'Natalia',   'Pardo Aguilar',    'F', '600 200 008', '1997-06-03', 'Paseo de Pereda 10, Santander',            'usuario'],
        ['socio9@stride.local',  'Marcos',    'Vega Mora',        'M', '600 200 009', '1982-09-25', 'Calle Trujillo 5, Mérida',                 'usuario'],
        ['socio10@stride.local', 'Laura',     'Santos Guerrero',  'F', '600 200 010', '1995-02-08', 'Calle Betis 21, Sevilla',                  'usuario'],
        ['socio11@stride.local', 'Víctor',    'Peña Delgado',     'M', '600 200 011', '1988-05-14', 'Calle Sierpes 44, Sevilla',                'usuario'],
        ['socio12@stride.local', 'Silvia',    'Cabrera León',     'F', '600 200 012', '1990-12-27', 'Gran Vía 56, Bilbao',                      'usuario'],
        ['socio13@stride.local', 'Daniel',    'Giménez Pastor',   'M', '600 200 013', '1993-07-09', 'Calle Tetuán 30, Pamplona',                'usuario'],
        ['socio14@stride.local', 'Marta',     'Herrero Bravo',    'F', '600 200 014', '1986-03-21', 'Calle Mayor 18, Zaragoza',                 'usuario'],

        // ── 2 Usuarios libres (sin club) ─────────────────────────────────
        ['libre1@stride.local',  'Pablo',     'Sierra Núñez',     'M', '600 300 001', '1992-10-14', 'Calle Alcalá 120, Madrid',                 'usuario'],
        ['libre2@stride.local',  'Rosa',      'Domingo Reyes',    'F', '600 300 002', '1998-04-02', 'Calle Fuencarral 88, Madrid',              'usuario'],
    ];

    public function run(): void
    {
        foreach (self::USERS as [
            $email, $nombre, $apellido, $sexo, $telefono, $fechaNac, $direccion, $rolGlobal
        ]) {
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'nombre'          => $nombre,
                    'apellido'        => $apellido,
                    'sexo'            => $sexo,
                    'telefono'        => $telefono,
                    'fecha_nacimiento' => $fechaNac,
                    'direccion'       => $direccion,
                    'password'        => Hash::make(self::PASSWORD),
                    'fecha_alta'      => now()->subMonths(rand(1, 20))->toDateString(),
                    'estado'          => 'activo',
                ]
            );

            $user->syncRoles([$rolGlobal]);
        }

        $this->command?->info(sprintf(
            'Creados %d usuarios (contraseña: %s)',
            count(self::USERS),
            self::PASSWORD
        ));

        $this->command?->table(
            ['Email', 'Nombre', 'Rol'],
            array_map(
                fn($u) => [$u[0], $u[1] . ' ' . $u[2], $u[7]],
                self::USERS
            )
        );
    }

    /** Helper para obtener un usuario por email desde otros seeders. */
    public static function get(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }
}
