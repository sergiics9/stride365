<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            DemoUsersSeeder::class,
            ClubSeeder::class,
            ActividadSeeder::class,
            ComunicadoSeeder::class,
            PublicacionFeedSeeder::class,
        ]);
    }
}
