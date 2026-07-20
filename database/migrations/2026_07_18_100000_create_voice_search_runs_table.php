<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voice_search_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_item_id')->constrained()->cascadeOnDelete();
            $table->string('engine');                          // brave | claude
            $table->json('queries')->nullable();               // the searches performed
            $table->unsignedInteger('candidates_found')->default(0);
            $table->string('status')->default('success');      // success | empty | failed
            $table->decimal('cost_usd', 10, 6)->nullable();
            $table->longText('raw')->nullable();               // raw output / results for transparency
            $table->text('note')->nullable();                  // why empty / error / summary
            $table->timestamps();

            $table->index(['work_item_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voice_search_runs');
    }
};
