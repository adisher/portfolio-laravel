<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Platform-native social content built from a blog post (docs/social-distribution-plan.md).
 *
 * A draft is one piece of content (a reel today; carousels later) with its
 * script and per-platform captions. Each platform it may go to is a target row,
 * approved and scheduled independently, so Instagram can go out while YouTube
 * still waits for review.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blog_post_id')->constrained()->cascadeOnDelete();
            $table->string('kind')->default('reel');
            // scripting -> draft -> rendering -> ready (review) | render_failed; rejected
            $table->string('status')->default('draft');
            $table->json('script')->nullable();   // ['scenes' => [...]]
            $table->json('captions')->nullable(); // ['instagram' => [...], 'youtube' => [...]]
            $table->string('video_path')->nullable();
            $table->string('cover_path')->nullable();
            $table->unsignedSmallInteger('duration_seconds')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('render_requested_at')->nullable();
            $table->timestamp('rendered_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        Schema::create('social_draft_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('social_draft_id')->constrained()->cascadeOnDelete();
            $table->string('platform'); // instagram | youtube
            // pending -> approved (scheduled_for set) -> posted | failed; rejected
            $table->string('status')->default('pending');
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->string('external_id')->nullable();
            $table->string('external_url')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->unique(['social_draft_id', 'platform']);
            $table->index(['status', 'scheduled_for']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_draft_targets');
        Schema::dropIfExists('social_drafts');
    }
};
