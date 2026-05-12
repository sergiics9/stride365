<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('comunicados', function (Blueprint $table) {
            $table->dropForeign(['grupo_id']);
        });

        Schema::table('comunicados', function (Blueprint $table) {
            $table->dropColumn('grupo_id');
        });

        Schema::dropIfExists('grupo_user');
        Schema::dropIfExists('grupos');
    }

    public function down(): void
    {
        // Irreversible en entornos ya migrados; ver migraciones históricas create_grupos / grupo_user / comunicados.
    }
};
