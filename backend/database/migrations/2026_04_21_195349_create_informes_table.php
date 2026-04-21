<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('informes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('nombre');
            $table->string('tipo')->nullable();
            $table->json('parametros')->nullable();
            $table->text('archivo_url')->nullable();
            $table->timestamp('fecha_generacion')->useCurrent();
            $table->string('estado')->default('generado');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('informes');
    }
};
