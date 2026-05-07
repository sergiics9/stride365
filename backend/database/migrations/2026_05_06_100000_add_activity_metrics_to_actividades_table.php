<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('actividades', function (Blueprint $table) {
            $table->unsignedInteger('desnivel_positivo_m')->nullable()->after('distancia');
            $table->unsignedInteger('duracion_segundos')->nullable()->after('desnivel_positivo_m');
            $table->unsignedInteger('ritmo_segundos_por_km')->nullable()->after('duracion_segundos');
            $table->unsignedSmallInteger('pulsaciones_media')->nullable()->after('ritmo_segundos_por_km');
            $table->unsignedSmallInteger('pulsaciones_max')->nullable()->after('pulsaciones_media');
        });
    }

    public function down(): void
    {
        Schema::table('actividades', function (Blueprint $table) {
            $table->dropColumn([
                'desnivel_positivo_m',
                'duracion_segundos',
                'ritmo_segundos_por_km',
                'pulsaciones_media',
                'pulsaciones_max',
            ]);
        });
    }
};
