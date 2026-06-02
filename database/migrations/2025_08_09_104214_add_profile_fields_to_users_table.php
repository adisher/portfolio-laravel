<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('profile_picture')->nullable()->after('email');
            $table->text('bio')->nullable()->after('profile_picture');
            $table->string('job_title')->nullable()->after('bio');
            $table->string('location')->nullable()->after('job_title');
            $table->string('website')->nullable()->after('location');
            $table->string('phone')->nullable()->after('website');
            $table->json('notification_preferences')->nullable()->after('phone');
            $table->timestamp('last_login_at')->nullable()->after('notification_preferences');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'profile_picture',
                'bio',
                'job_title',
                'location',
                'website',
                'phone',
                'notification_preferences',
                'last_login_at'
            ]);
        });
    }
};