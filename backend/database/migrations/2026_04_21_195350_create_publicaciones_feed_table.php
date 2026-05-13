<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publicaciones_feed', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('actividad_id')
                ->nullable()
                ->constrained('actividades')
                ->nullOnDelete();

            $table->string('titulo')->nullable();
            $table->text('resumen')->nullable();
            $table->text('contenido')->nullable();
            $table->text('imagen_url')->nullable();
            $table->string('tipo')->nullable();
            $table->string('visibilidad')->nullable();
            $table->timestamp('fecha_publicacion')->useCurrent();
            $table->string('estado')->default('activo');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publicaciones_feed');
    }
};
