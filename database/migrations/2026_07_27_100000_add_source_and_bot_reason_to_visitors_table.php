<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visitors', function (Blueprint $table) {
            // Traffic source (App\Support\TrafficSource): the acquisition channel
            // and the specific origin. Persisted (not computed at read-time) so
            // reporting is an indexed GROUP BY, not a referrer re-parse per query.
            $table->string('source', 20)->nullable()->after('utm_campaign');
            $table->string('source_detail')->nullable()->after('source');

            // Why a visitor was flagged as a bot (App\Support\BotDetector).
            // null = human. Lets the report split human vs bot AND surface which
            // AI crawlers read which content. Distinct from the is_bot boolean,
            // which stays as the fast filter.
            $table->string('bot_reason', 30)->nullable()->after('is_bot');

            $table->index(['source', 'created_at']);
            $table->index(['bot_reason', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('visitors', function (Blueprint $table) {
            $table->dropIndex(['source', 'created_at']);
            $table->dropIndex(['bot_reason', 'created_at']);
            $table->dropColumn(['source', 'source_detail', 'bot_reason']);
        });
    }
};
