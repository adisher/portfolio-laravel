<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->json('metrics')->nullable()->after('status');
            $table->string('primary_metric_value')->nullable()->after('metrics');
            $table->string('primary_metric_label')->nullable()->after('primary_metric_value');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['metrics', 'primary_metric_value', 'primary_metric_label']);
        });
    }
};
