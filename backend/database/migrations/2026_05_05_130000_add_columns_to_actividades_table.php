<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('actividades', function (Blueprint $table) {
            $table->string('punto_encuentro')->nullable()->after('lugar');
            $table->text('material_necesario')->nullable()->after('punto_encuentro');
            $table->enum('modo_creacion', ['vivo', 'dibujada', 'importada'])
                ->default('dibujada')
                ->after('estado');
            $table->json('track_geojson')->nullable()->after('modo_creacion');
            $table->text('motivo_cancelacion')->nullable()->after('track_geojson');
            $table->timestamp('finalizada_at')->nullable()->after('motivo_cancelacion');
            $table->boolean('publicada_en_feed')->default(false)->after('finalizada_at');
        });
    }

    public function down(): void
    {
        Schema::table('actividades', function (Blueprint $table) {
            $table->dropColumn([
                'punto_encuentro',
                'material_necesario',
                'modo_creacion',
                'track_geojson',
                'motivo_cancelacion',
                'finalizada_at',
                'publicada_en_feed',
            ]);
        });
    }
};
