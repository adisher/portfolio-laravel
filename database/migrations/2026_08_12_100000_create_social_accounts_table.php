<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A connected social destination (a Facebook Page for now; X / LinkedIn /
 * Instagram later). Credentials are stored in an encrypted JSON column and
 * decrypted in memory only when a driver publishes. Each account carries its
 * own caption template and its own auto-post gate so channels can behave
 * independently.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('platform');                 // facebook | x | linkedin | instagram
            $table->string('name');                      // human label, e.g. "Adil Sher (FB Page)"
            $table->text('credentials')->nullable();     // encrypted: page_id, access_token, ...
            $table->text('caption_template')->nullable(); // {title} {excerpt} {url} {hashtags}
            $table->boolean('is_active')->default(true);  // connected & usable at all
            $table->boolean('auto_post_enabled')->default(false); // participates in social:publish
            $table->unsignedInteger('min_human_views')->default(5); // auto-post eligibility gate
            $table->timestamp('last_posted_at')->nullable();
            $table->timestamps();

            $table->index(['platform', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
    }
};
