<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            // Two view metrics kept side by side:
            //   views      = HUMAN views (bots excluded) — the honest number
            //   raw_views  = TOTAL page loads (bots included) — for comparison
            // The old `views` counter was raw/bot-inclusive and overstated real
            // readership ~4x; it now means human, with raw preserved here.
            $table->unsignedBigInteger('raw_views')->default(0)->after('views');
        });
    }

    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropColumn('raw_views');
        });
    }
};
