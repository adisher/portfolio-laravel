<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A real "this article has been through processing" marker.
 *
 * Until now `articles:process` inferred it from "has no category", which is
 * false for any article the keyword detector cannot classify: those stay
 * pending with a null category, so they look unprocessed forever. With no
 * ORDER BY on the selection, every hourly run took the same 200 unusable rows
 * and never reached the ~14,000 behind them. The pipeline stalled completely
 * and silently, because a re-run of already-scored rows writes nothing (an
 * unchanged Eloquent update is a no-op, so even updated_at stayed put).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('collected_articles', function (Blueprint $table) {
            $table->timestamp('processed_at')->nullable()->after('parked_at');

            // The hourly selection filters on status + processed_at and orders
            // by id, so index them together.
            $table->index(['status', 'processed_at', 'id'], 'collected_articles_processing_queue_index');
        });
    }

    public function down(): void
    {
        Schema::table('collected_articles', function (Blueprint $table) {
            $table->dropIndex('collected_articles_processing_queue_index');
            $table->dropColumn('processed_at');
        });
    }
};
