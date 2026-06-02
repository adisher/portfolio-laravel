<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('auto_publish_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('enabled')->default(true);
            $table->integer('max_posts_per_day')->default(3);
            $table->integer('min_score_for_auto_publish')->default(85);
            $table->integer('require_review_below_score')->default(75);
            $table->json('publish_times')->nullable(); // ['09:00', '13:00', '17:00']
            $table->json('category_weights')->nullable(); // {'ai-machine-learning': 30, ...}
            $table->boolean('ai_enhancement_enabled')->default(true);
            $table->boolean('include_faq_section')->default(true);
            $table->boolean('include_key_insights')->default(true);
            $table->boolean('include_tldr')->default(true);
            $table->string('default_author_name')->nullable();
            $table->integer('posts_published_today')->default(0);
            $table->date('last_publish_date')->nullable();
            $table->timestamps();
        });

        // Insert default settings
        DB::table('auto_publish_settings')->insert([
            'enabled' => true,
            'max_posts_per_day' => 3,
            'min_score_for_auto_publish' => 85,
            'require_review_below_score' => 75,
            'publish_times' => json_encode(['09:00', '13:00', '17:00']),
            'category_weights' => json_encode([
                'ai-machine-learning' => 30,
                'web-development' => 25,
                'tech-news' => 20,
                'programming' => 15,
                'design-ux' => 5,
                'devops-cloud' => 3,
                'career-growth' => 2,
            ]),
            'ai_enhancement_enabled' => true,
            'include_faq_section' => true,
            'include_key_insights' => true,
            'include_tldr' => true,
            'posts_published_today' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auto_publish_settings');
    }
};
