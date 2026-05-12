<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('pagos');
        Schema::dropIfExists('cuotas');
        Schema::dropIfExists('informes');
    }

    public function down(): void
    {
        // Irreversible: las tablas originales se crean en migraciones anteriores del proyecto.
    }
};
