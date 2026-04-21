<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cuota_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->timestamp('fecha_pago')->useCurrent();
            $table->decimal('monto_pagado', 12, 2);
            $table->string('metodo_pago')->nullable();
            $table->string('referencia')->nullable();
            $table->string('estado')->default('confirmado');
            $table->text('observaciones')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
