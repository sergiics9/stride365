<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('actividades', function (Blueprint $table) {
            $table->id();

            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->timestamp('fecha_inicio');
            $table->timestamp('fecha_fin')->nullable();
            $table->string('lugar')->nullable();
            $table->string('modalidad')->nullable();
            $table->decimal('distancia', 10, 2)->nullable();
            $table->string('dificultad')->nullable();
            $table->integer('cupo_maximo')->nullable();
            $table->decimal('costo', 12, 2)->nullable();
            $table->string('estado')->default('programada');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actividades');
    }
};
