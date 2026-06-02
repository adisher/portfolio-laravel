<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained()->onDelete('cascade');
            $table->foreignId('visitor_id')->nullable()->constrained()->onDelete('set null');
            $table->string('source_page')->nullable(); // Page where contact form was submitted
            $table->string('referrer')->nullable();
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->integer('pages_viewed_before_contact')->default(0);
            $table->integer('time_on_site_before_contact')->default(0); // seconds
            $table->json('viewed_projects')->nullable(); // Array of project IDs viewed
            $table->json('viewed_blog_posts')->nullable(); // Array of blog post IDs viewed
            $table->boolean('is_returning_visitor')->default(false);
            $table->timestamps();

            $table->index(['created_at', 'source_page']);
            $table->index(['utm_source', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_analytics');
    }
};