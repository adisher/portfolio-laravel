<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demo_availability', function (Blueprint $table) {
            $table->id();
            $table->json('days_of_week')->default('[1,2,3,4,5]'); // 1=Mon … 7=Sun
            $table->time('start_time')->default('10:00:00');
            $table->time('end_time')->default('17:00:00');
            $table->unsignedSmallInteger('slot_duration')->default(30);  // minutes
            $table->unsignedSmallInteger('buffer_minutes')->default(15); // gap between slots
            $table->string('timezone', 50)->default('Asia/Karachi');
            $table->unsignedSmallInteger('max_per_day')->default(4);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demo_availability');
    }
};
