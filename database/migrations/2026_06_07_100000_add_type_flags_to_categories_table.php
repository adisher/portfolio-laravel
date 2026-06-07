<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->boolean('for_blog')->default(false)->after('is_active');
            $table->boolean('for_projects')->default(false)->after('for_blog');
        });

        // Back-fill: mark categories used by published blog posts
        DB::statement("
            UPDATE categories SET for_blog = 1
            WHERE id IN (
                SELECT DISTINCT category_id FROM blog_posts
                WHERE category_id IS NOT NULL
            )
        ");

        // Back-fill: mark categories used by projects
        DB::statement("
            UPDATE categories SET for_projects = 1
            WHERE id IN (
                SELECT DISTINCT category_id FROM projects
                WHERE category_id IS NOT NULL
            )
        ");

        // Back-fill: known blog categories that may not have posts yet,
        // so they still appear in the blog UI once content is published.
        DB::table('categories')
            ->whereIn('slug', [
                'web-development', 'ai-machine-learning', 'tech-news',
                'programming', 'design-ux', 'devops-cloud', 'career-growth',
            ])
            ->update(['for_blog' => true]);
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['for_blog', 'for_projects']);
        });
    }
};
