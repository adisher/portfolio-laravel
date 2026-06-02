<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('rss_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('url');
            $table->string('category')->default('general'); // dev, design, tech, etc.
            $table->boolean('active')->default(true);
            $table->integer('priority')->default(5);         // 1-10 scale
            $table->integer('fetch_frequency')->default(60); // minutes
            $table->timestamp('last_fetched_at')->nullable();
            $table->json('metadata')->nullable(); // Store source-specific settings
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('rss_sources');
    }
};
