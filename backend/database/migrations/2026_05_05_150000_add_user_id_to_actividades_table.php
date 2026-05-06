<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('actividades', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->after('club_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->index(['user_id', 'club_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::table('actividades', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropIndex(['user_id', 'club_id', 'estado']);
            $table->dropColumn('user_id');
        });
    }
};
