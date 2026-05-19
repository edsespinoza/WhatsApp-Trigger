<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->index('evolution_message_id');
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->index('opted_in');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->dropIndex(['evolution_message_id']);
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->dropIndex(['opted_in']);
        });
    }
};
