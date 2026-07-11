<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_items', function (Blueprint $table) {
            // Opening hooks for generated articles: real events (with source),
            // ordered by priority. The generator opens with the chosen one.
            $table->json('hooks')->nullable()->after('article_angles');
        });
    }

    public function down(): void
    {
        Schema::table('work_items', function (Blueprint $table) {
            $table->dropColumn('hooks');
        });
    }
};
