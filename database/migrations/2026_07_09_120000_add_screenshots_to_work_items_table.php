<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_items', function (Blueprint $table) {
            // Canonical screenshot library for this product: "slug — description".
            // The generator may only emit [[screenshot: slug]] markers from this list.
            $table->json('screenshots')->nullable()->after('voices');
        });
    }

    public function down(): void
    {
        Schema::table('work_items', function (Blueprint $table) {
            $table->dropColumn('screenshots');
        });
    }
};
