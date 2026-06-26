<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->default('product'); // product | service | project | skill
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('active')->default(true);
            $table->integer('sort_order')->default(0);

            // Positioning / manual content
            $table->string('tagline')->nullable();
            $table->text('target_audience')->nullable();
            $table->text('how_it_helps')->nullable();
            $table->string('tech_stack')->nullable();
            $table->string('url')->nullable();
            $table->text('notes')->nullable();

            // Structured list fields (JSON)
            $table->json('pain_points')->nullable();
            $table->json('key_outcomes')->nullable();
            $table->json('differentiators')->nullable();
            $table->json('target_keywords')->nullable();
            $table->json('article_angles')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_items');
    }
};
