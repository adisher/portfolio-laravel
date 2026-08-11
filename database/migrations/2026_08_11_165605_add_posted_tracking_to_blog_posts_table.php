<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tracks whether a published article has already been handed out to the
 * external posting automation (n8n / social), so the "claim next" API can
 * drain the catalogue exactly once and never repeat an article.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            // Null = still in the queue. Set = already posted (claimed).
            $table->timestamp('posted_at')->nullable()->after('published_at');
            // Optional label for where it went (e.g. "n8n", "twitter"). Future-proofs
            // the per-platform social feature; the API sets it when provided.
            $table->string('posted_via')->nullable()->after('posted_at');
            // Speeds up the "oldest unposted published" lookup the claim endpoint runs.
            $table->index(['status', 'posted_at', 'published_at'], 'blog_posts_posting_queue_index');
        });
    }

    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropIndex('blog_posts_posting_queue_index');
            $table->dropColumn(['posted_at', 'posted_via']);
        });
    }
};
