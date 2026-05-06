<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('club_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('club_id')->constrained('clubes')->cascadeOnDelete();
            $table->enum('role', ['admin_club', 'socio']);
            $table->boolean('is_guide')->default(false);
            $table->enum('status', ['pending', 'active', 'cancelled', 'grace', 'inactive'])
                ->default('pending');
            $table->string('subscription_name')->nullable();
            $table->string('stripe_subscription_id')->nullable();
            $table->timestamp('subscribed_at')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->date('joined_at')->nullable();
            $table->date('left_at')->nullable();
            $table->string('left_reason')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'club_id', 'role']);
            $table->index(['club_id', 'role']);
            $table->index('subscription_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('club_user');
    }
};
