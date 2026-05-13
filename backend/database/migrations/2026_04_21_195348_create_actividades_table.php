<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actividades', function (Blueprint $table) {
            $table->id();

            $table->foreignId('club_id')
                ->nullable()
                ->constrained('clubes')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->timestamp('fecha_inicio');
            $table->timestamp('fecha_fin')->nullable();
            $table->string('lugar')->nullable();
            $table->string('punto_encuentro')->nullable();
            $table->text('material_necesario')->nullable();
            $table->string('modalidad')->nullable();
            $table->decimal('distancia', 10, 2)->nullable();
            $table->unsignedInteger('desnivel_positivo_m')->nullable();
            $table->unsignedInteger('duracion_segundos')->nullable();
            $table->unsignedInteger('ritmo_segundos_por_km')->nullable();
            $table->unsignedSmallInteger('pulsaciones_media')->nullable();
            $table->unsignedSmallInteger('pulsaciones_max')->nullable();
            $table->string('dificultad')->nullable();
            $table->integer('cupo_maximo')->nullable();
            $table->decimal('costo', 12, 2)->nullable();
            $table->string('estado')->default('programada');
            $table->enum('modo_creacion', ['vivo', 'dibujada', 'importada'])
                ->default('dibujada');
            $table->json('track_geojson')->nullable();
            $table->text('motivo_cancelacion')->nullable();
            $table->timestamp('finalizada_at')->nullable();
            $table->boolean('publicada_en_feed')->default(false);

            $table->timestamps();

            $table->index(['user_id', 'club_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actividades');
    }
};
