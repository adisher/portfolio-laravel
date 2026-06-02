<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->string('source_type')->default('original')->after('user_id');
            $table->string('original_url')->nullable()->after('source_type');
            $table->string('original_author')->nullable()->after('original_url');
            $table->string('original_publication')->nullable()->after('original_author');
            $table->date('original_published_at')->nullable()->after('original_publication');
            $table->text('curator_notes')->nullable()->after('original_published_at');
        });
    }

    public function down()
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropColumn([
                'source_type', 'original_url', 'original_author',
                'original_publication', 'original_published_at', 'curator_notes',
            ]);
        });
    }
};
