<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comunicados', function (Blueprint $table) {
            $table->foreignId('club_id')
                ->nullable()
                ->after('id')
                ->constrained('clubes')
                ->cascadeOnDelete();

            $table->dropForeign(['grupo_id']);
            $table->foreignId('grupo_id')
                ->nullable()
                ->change();
            $table->foreign('grupo_id')->references('id')->on('grupos')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('comunicados', function (Blueprint $table) {
            $table->dropForeign(['club_id']);
            $table->dropColumn('club_id');
        });
    }
};
