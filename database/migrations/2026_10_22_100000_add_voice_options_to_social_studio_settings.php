<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * How the cloned voice speaks: an expressiveness preset, and the owner's
 * pronunciation list ("word = how to say it") applied before speech is made.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('social_studio_settings', function (Blueprint $table) {
            $table->string('voice_style')->default('natural')->after('voice_uploaded_at');
            $table->text('pronunciations')->nullable()->after('voice_style');
        });
    }

    public function down(): void
    {
        Schema::table('social_studio_settings', function (Blueprint $table) {
            $table->dropColumn(['voice_style', 'pronunciations']);
        });
    }
};
