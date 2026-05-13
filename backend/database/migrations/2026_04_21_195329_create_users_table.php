<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            $table->string('nombre')->nullable();
            $table->string('apellido')->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->string('sexo')->nullable();
            $table->string('telefono')->nullable();
            $table->string('email')->unique();
            $table->string('password');
            $table->text('foto_url')->nullable();
            $table->string('direccion')->nullable();
            $table->date('fecha_alta')->nullable();
            $table->string('estado')->default('activo');

            $table->timestamps();
        });

        Schema::table('clubes', function (Blueprint $table) {
            $table->foreignId('requested_by')
                ->nullable()
                ->after('application_status')
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('approved_by')
                ->nullable()
                ->after('requested_by')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->text('rejection_reason')->nullable()->after('approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('clubes', function (Blueprint $table) {
            $table->dropForeign(['requested_by']);
            $table->dropForeign(['approved_by']);
            $table->dropColumn([
                'requested_by',
                'approved_by',
                'approved_at',
                'rejection_reason',
            ]);
        });

        Schema::dropIfExists('users');
    }
};
