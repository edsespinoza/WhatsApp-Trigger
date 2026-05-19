<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')
                ->constrained('whatsapp_messages')
                ->cascadeOnDelete();
            // Eventos da Evolution API: queued, sent, delivered, read, failed, webhook_received
            $table->string('event', 50);
            $table->json('payload')->nullable();
            $table->timestamp('created_at');

            $table->index(['message_id', 'event']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logs');
    }
};
