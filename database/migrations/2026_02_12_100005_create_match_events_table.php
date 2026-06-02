<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sport_match_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_type');
            $table->string('period')->nullable();
            $table->string('match_time')->nullable();
            $table->string('player_name')->nullable();
            $table->text('description')->nullable();
            $table->json('data')->nullable();
            $table->integer('minute')->nullable();
            $table->dateTime('occurred_at')->nullable();
            $table->timestamps();

            $table->index(['sport_match_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_events');
    }
};
