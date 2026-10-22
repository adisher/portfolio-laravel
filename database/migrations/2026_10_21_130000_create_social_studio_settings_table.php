<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One-row settings for the Social Studio render pipeline: the owner's voice
 * sample (private storage) and the GitHub connection that starts renders.
 * Tokens are stored encrypted by the model.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_studio_settings', function (Blueprint $table) {
            $table->id();
            $table->text('github_token')->nullable();       // fine-grained PAT, encrypted
            $table->text('render_token')->nullable();       // shared with the render workflow, encrypted
            $table->timestamp('render_token_synced_at')->nullable(); // saved as a GitHub Actions secret
            $table->string('voice_sample_path')->nullable(); // on the local (private) disk
            $table->string('voice_sample_name')->nullable();
            $table->timestamp('voice_uploaded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_studio_settings');
    }
};
