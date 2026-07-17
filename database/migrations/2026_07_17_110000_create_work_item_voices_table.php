<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_item_voices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_item_id')->constrained()->cascadeOnDelete();
            $table->text('quote');
            $table->string('attribution')->nullable();       // who said it (e.g. r/musicmarketing, @handle)
            $table->string('source_url', 1000)->nullable();   // primary post URL
            $table->foreignId('media_id')->nullable()->constrained('media')->nullOnDelete(); // attached screenshot
            $table->string('status')->default('candidate');   // candidate | approved
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('meta')->nullable();                 // search snippet, origin, etc.
            $table->timestamps();

            $table->index(['work_item_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_item_voices');
    }
};
