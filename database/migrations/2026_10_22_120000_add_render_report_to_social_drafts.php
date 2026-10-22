<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-scene result of the render's speech check (what Whisper heard, which
 * words did not match, takes tried), shown on the reel review page.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('social_drafts', function (Blueprint $table) {
            $table->json('render_report')->nullable()->after('error');
        });
    }

    public function down(): void
    {
        Schema::table('social_drafts', function (Blueprint $table) {
            $table->dropColumn('render_report');
        });
    }
};
