<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('collected_articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rss_source_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('url')->unique();
            $table->string('author')->nullable();
            $table->timestamp('published_at');
            $table->json('content_data')->nullable(); // Store full article data
            $table->decimal('relevance_score', 5, 2)->default(0); // 0-100 scoring
            $table->enum('status', ['pending', 'approved', 'rejected', 'published'])->default('pending');
            $table->foreignId('blog_post_id')->nullable()->constrained()->onDelete('set null');
            $table->text('curator_notes')->nullable();
            $table->timestamps();
            
            $table->index(['status', 'relevance_score']);
            $table->index(['published_at', 'relevance_score']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('collected_articles');
    }
};