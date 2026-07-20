<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_items', function (Blueprint $table) {
            // Curated places to hunt for real user reviews/comments, as
            // "Label - https://url - what to look for". Manual research list.
            $table->json('research_sources')->nullable()->after('voice_sources');
        });
    }

    public function down(): void
    {
        Schema::table('work_items', function (Blueprint $table) {
            $table->dropColumn('research_sources');
        });
    }
};
