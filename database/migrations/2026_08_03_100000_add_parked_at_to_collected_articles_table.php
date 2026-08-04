<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('collected_articles', function (Blueprint $table) {
            // "Parked" articles are approved-but-below-the-publish-quality-bar
            // items diverted into a reuse pool: kept intact (not deleted), never
            // published to this blog, and exportable as one set (WHERE parked_at
            // IS NOT NULL) for a second blog or other use. Reversible — null it
            // to return an article to the active flow.
            $table->timestamp('parked_at')->nullable()->after('scheduled_publish_at');
            $table->index(['parked_at', 'relevance_score']);
        });
    }

    public function down(): void
    {
        Schema::table('collected_articles', function (Blueprint $table) {
            $table->dropIndex(['parked_at', 'relevance_score']);
            $table->dropColumn('parked_at');
        });
    }
};
