<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('rss_sources', function (Blueprint $table) {
            $table->foreignId('target_category_id')->nullable()->after('category')->constrained('categories')->nullOnDelete();
            $table->json('keyword_filters')->nullable()->after('target_category_id'); // include/exclude keywords
            $table->integer('min_quality_score')->default(60)->after('keyword_filters');
            $table->boolean('auto_publish')->default(false)->after('min_quality_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rss_sources', function (Blueprint $table) {
            $table->dropForeign(['target_category_id']);
            $table->dropColumn(['target_category_id', 'keyword_filters', 'min_quality_score', 'auto_publish']);
        });
    }
};
