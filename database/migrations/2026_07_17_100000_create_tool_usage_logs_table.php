<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tool_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->string('tool');                          // pexels, indexnow, gsc, safepay, ...
            $table->string('action')->nullable();            // fetch, submit, query, checkout, ...
            $table->unsignedInteger('quantity')->default(1); // units consumed (images, urls, requests)
            $table->string('unit')->nullable();              // images, urls, requests, calls
            $table->decimal('cost_usd', 10, 6)->nullable();  // if the tool has a known cost
            $table->boolean('success')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['created_at', 'tool']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tool_usage_logs');
    }
};
