<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clubes', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('nombre');
            $table->string('logo_url')->nullable()->after('descripcion');
            $table->boolean('active')->default(false)->after('email');
            $table->enum('application_status', ['pending', 'approved', 'rejected'])
                ->default('pending')
                ->after('active');
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
                'slug',
                'logo_url',
                'active',
                'application_status',
                'requested_by',
                'approved_by',
                'approved_at',
                'rejection_reason',
            ]);
        });
    }
};
