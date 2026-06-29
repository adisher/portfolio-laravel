<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_items', function (Blueprint $table) {
            $table->json('objections')->nullable()->after('pain_points');
            $table->text('call_to_action')->nullable()->after('how_it_helps');
            $table->json('proof_links')->nullable()->after('key_outcomes');
        });
    }

    public function down(): void
    {
        Schema::table('work_items', function (Blueprint $table) {
            $table->dropColumn(['objections', 'call_to_action', 'proof_links']);
        });
    }
};
