<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-channel publish log: one row per (article, account) attempt. This is the
 * source of truth for "has this article gone to this specific channel yet",
 * which the single blog_posts.posted_at flag (used by the n8n API) cannot
 * express once multiple platforms are in play.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blog_post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('social_account_id')->constrained()->cascadeOnDelete();
            $table->string('platform');                       // denormalised for easy filtering
            $table->string('status')->default('pending');     // pending | posted | failed
            $table->string('trigger')->default('manual');     // manual | auto
            $table->text('message')->nullable();              // the composed caption sent
            $table->string('external_id')->nullable();        // platform post id
            $table->string('external_url')->nullable();       // permalink when resolvable
            $table->text('error')->nullable();                // failure reason
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            // A given article is only ever posted once to a given account.
            $table->unique(['blog_post_id', 'social_account_id']);
            $table->index(['social_account_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_posts');
    }
};
