<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('actividades', function (Blueprint $table) {
            $table->foreignId('club_id')
                ->after('id')
                ->nullable()
                ->constrained('clubes')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('actividades', function (Blueprint $table) {
            $table->dropConstrainedForeignId('club_id');
        });
    }
};
