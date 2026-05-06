<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('publicaciones_feed', function (Blueprint $table) {
            $table->foreignId('actividad_id')
                ->nullable()
                ->after('user_id')
                ->constrained('actividades')
                ->nullOnDelete();
            $table->text('resumen')->nullable()->after('titulo');
            $table->text('contenido')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('publicaciones_feed', function (Blueprint $table) {
            $table->dropForeign(['actividad_id']);
            $table->dropColumn(['actividad_id', 'resumen']);
        });
    }
};
