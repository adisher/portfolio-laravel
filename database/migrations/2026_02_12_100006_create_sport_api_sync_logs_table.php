<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sport_api_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('sync_type');
            $table->string('sport_slug')->nullable();
            $table->string('status');
            $table->integer('records_synced')->default(0);
            $table->integer('api_calls_used')->default(0);
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sport_api_sync_logs');
    }
};
