<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('plan', ['free', 'starter', 'pro', 'enterprise'])->default('free');
            // Limite conforme plano: 50 / 2000 / 10000 / -1 (ilimitado)
            $table->integer('messages_limit')->default(50);
            $table->unsignedInteger('messages_sent')->default(0);
            $table->date('period_start');
            $table->date('period_end');
            $table->string('stripe_subscription_id')->nullable();
            $table->enum('status', ['active', 'cancelled', 'past_due', 'trialing'])->default('active');
            $table->timestamps();

            $table->unique('user_id');
            $table->index(['status', 'period_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
