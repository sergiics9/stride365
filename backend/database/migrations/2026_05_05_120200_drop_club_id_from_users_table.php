<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'club_id')) {
                $table->dropForeign(['club_id']);
                $table->dropColumn('club_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('club_id')
                ->nullable()
                ->after('id')
                ->constrained('clubes')
                ->nullOnDelete();
        });
    }
};
