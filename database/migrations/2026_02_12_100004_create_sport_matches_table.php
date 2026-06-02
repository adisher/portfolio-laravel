<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sport_matches', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->foreignId('sport_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tournament_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('home_team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('away_team_id')->constrained('teams')->cascadeOnDelete();
            $table->string('api_match_id')->nullable()->unique();
            $table->enum('status', [
                'scheduled', 'live', 'completed', 'postponed', 'cancelled', 'abandoned',
            ])->default('scheduled');
            $table->string('match_type')->nullable();
            $table->string('venue')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->dateTime('scheduled_at');
            $table->dateTime('started_at')->nullable();
            $table->dateTime('ended_at')->nullable();
            $table->string('current_period')->nullable();
            $table->string('match_time')->nullable();
            $table->json('home_score')->nullable();
            $table->json('away_score')->nullable();
            $table->json('period_scores')->nullable();
            $table->string('result_summary')->nullable();
            $table->string('toss')->nullable();
            $table->json('metadata')->nullable();
            $table->string('featured_image')->nullable();
            $table->text('meta_description')->nullable();
            $table->unsignedInteger('views')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->timestamps();

            $table->index('status');
            $table->index('scheduled_at');
            $table->index(['sport_id', 'status']);
            $table->index(['status', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sport_matches');
    }
};
