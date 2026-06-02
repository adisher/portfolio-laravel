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
        Schema::table('collected_articles', function (Blueprint $table) {
            $table->foreignId('assigned_category_id')->nullable()->after('relevance_score')->constrained('categories')->nullOnDelete();
            $table->decimal('category_confidence', 5, 2)->default(0)->after('assigned_category_id');
            $table->boolean('is_duplicate')->default(false)->after('category_confidence');
            $table->foreignId('duplicate_of_id')->nullable()->after('is_duplicate')->constrained('collected_articles')->nullOnDelete();
            $table->boolean('ai_enhanced')->default(false)->after('duplicate_of_id');
            $table->json('ai_generated_content')->nullable()->after('ai_enhanced');
            $table->timestamp('scheduled_publish_at')->nullable()->after('ai_generated_content');
            $table->json('seo_data')->nullable()->after('scheduled_publish_at');

            $table->index(['status', 'relevance_score', 'is_duplicate']);
            $table->index('scheduled_publish_at');
            $table->index('assigned_category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('collected_articles', function (Blueprint $table) {
            $table->dropForeign(['assigned_category_id']);
            $table->dropForeign(['duplicate_of_id']);
            $table->dropColumn([
                'assigned_category_id',
                'category_confidence',
                'is_duplicate',
                'duplicate_of_id',
                'ai_enhanced',
                'ai_generated_content',
                'scheduled_publish_at',
                'seo_data'
            ]);
        });
    }
};
