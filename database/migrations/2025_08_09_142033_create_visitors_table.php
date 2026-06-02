<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitors', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->unique();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable(); // Changed to text for longer user agents
            $table->string('device_type')->nullable(); // mobile, tablet, desktop
            $table->string('browser')->nullable();
            $table->string('platform')->nullable(); // OS
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->text('referrer')->nullable(); // Changed to text for longer URLs
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->timestamp('first_visit_at')->nullable(); // Made nullable
            $table->timestamp('last_activity_at')->nullable(); // Made nullable
            $table->integer('page_views')->default(1);
            $table->integer('session_duration')->default(0); // seconds
            $table->boolean('is_bot')->default(false);
            $table->timestamps();

            $table->index(['created_at', 'is_bot']);
            $table->index(['country', 'created_at']);
            $table->index(['device_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitors');
    }
};