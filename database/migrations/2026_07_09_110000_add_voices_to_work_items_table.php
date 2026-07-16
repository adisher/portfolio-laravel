<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_items', function (Blueprint $table) {
            // Real user sentiment (quotes with attribution + source) woven in as
            // social proof. Curated, never AI-invented. Ordered by strength.
            $table->json('voices')->nullable()->after('hooks');
        });
    }

    public function down(): void
    {
        Schema::table('work_items', function (Blueprint $table) {
            $table->dropColumn('voices');
        });
    }
};
