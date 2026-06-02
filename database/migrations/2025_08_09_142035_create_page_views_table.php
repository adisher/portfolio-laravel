<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visitor_id')->constrained()->onDelete('cascade');
            $table->string('url');
            $table->string('page_title')->nullable();
            $table->string('page_type')->nullable(); // home, about, project, blog, contact
            $table->string('content_type')->nullable(); // project, blog_post, page
            $table->unsignedBigInteger('content_id')->nullable(); // ID of project/blog post
            $table->string('method')->default('GET');
            $table->integer('load_time')->nullable(); // milliseconds
            $table->integer('time_on_page')->nullable(); // seconds
            $table->boolean('is_bounce')->default(false);
            $table->string('exit_page')->nullable();
            $table->timestamps();

            $table->index(['url', 'created_at']);
            $table->index(['page_type', 'created_at']);
            $table->index(['content_type', 'content_id']);
            $table->index(['created_at', 'is_bounce']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_views');
    }
};