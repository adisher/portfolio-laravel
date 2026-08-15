<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tracks when a platform access token expires (LinkedIn member tokens last
 * ~60 days) and when we last emailed a reminder about it, so the
 * social:token-reminders command can warn once before it dies. Non-expiring
 * tokens (Facebook Page tokens) leave token_expires_at null and are ignored.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('social_accounts', function (Blueprint $table) {
            $table->timestamp('token_expires_at')->nullable()->after('last_posted_at');
            $table->timestamp('token_reminded_at')->nullable()->after('token_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('social_accounts', function (Blueprint $table) {
            $table->dropColumn(['token_expires_at', 'token_reminded_at']);
        });
    }
};
