<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demo_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('company')->nullable();
            $table->string('plan_interest')->nullable();
            $table->text('message')->nullable();
            $table->dateTime('scheduled_at'); // stored in UTC
            $table->unsignedSmallInteger('duration_minutes')->default(30);
            $table->enum('status', ['confirmed', 'cancelled', 'completed', 'no_show'])->default('confirmed');
            $table->string('cancellation_token', 64)->unique();
            $table->timestamp('reminder_24h_sent_at')->nullable();
            $table->timestamp('reminder_1h_sent_at')->nullable();
            $table->timestamp('follow_up_sent_at')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();

            $table->index(['scheduled_at', 'status']);
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demo_bookings');
    }
};
