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
        Schema::create('ai_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->string('service'); // content_transform, seo_generate, categorize
            $table->string('model')->default('claude-3-haiku-20240307');
            $table->integer('input_tokens');
            $table->integer('output_tokens');
            $table->decimal('cost_usd', 10, 6);
            $table->foreignId('collected_article_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('blog_post_id')->nullable()->constrained()->nullOnDelete();
            $table->json('request_data')->nullable();
            $table->json('response_data')->nullable();
            $table->boolean('success')->default(true);
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['created_at', 'service']);
            $table->index('cost_usd');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_usage_logs');
    }
};
