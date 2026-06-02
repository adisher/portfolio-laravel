<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('short_name')->nullable();
            $table->string('abbreviation', 10)->nullable();
            $table->foreignId('sport_id')->constrained()->cascadeOnDelete();
            $table->string('api_team_id')->nullable();
            $table->string('logo')->nullable();
            $table->string('country')->nullable();
            $table->string('country_code', 5)->nullable();
            $table->string('primary_color')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
