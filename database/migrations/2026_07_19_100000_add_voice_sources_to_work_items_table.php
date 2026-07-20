<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_items', function (Blueprint $table) {
            // Allowlist of community domains to search for user voices. Per product,
            // because the communities differ. Empty falls back to config default.
            $table->json('voice_sources')->nullable()->after('screenshots');
        });
    }

    public function down(): void
    {
        Schema::table('work_items', function (Blueprint $table) {
            $table->dropColumn('voice_sources');
        });
    }
};
