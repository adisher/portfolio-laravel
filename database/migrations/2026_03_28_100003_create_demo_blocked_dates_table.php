<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demo_blocked_dates', function (Blueprint $table) {
            $table->id();
            $table->date('blocked_date');
            $table->time('start_time')->nullable(); // null = full day block
            $table->time('end_time')->nullable();
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->index('blocked_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demo_blocked_dates');
    }
};
