<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('name');      // Original filename
            $table->string('file_name'); // Stored filename
            $table->string('mime_type');
            $table->string('extension');
            $table->bigInteger('size');                // File size in bytes
            $table->string('disk')->default('public'); // Storage disk
            $table->string('path');                    // File path
            $table->string('folder')->default('/');    // Folder organization
            $table->json('metadata')->nullable();      // Width, height, etc.
            $table->json('variants')->nullable();      // Thumbnails, optimized versions
            $table->string('alt_text')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['disk', 'folder']);
            $table->index(['mime_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
