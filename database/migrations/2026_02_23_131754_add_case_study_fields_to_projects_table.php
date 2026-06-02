<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->text('challenge')->nullable()->after('client_name');
            $table->text('solution')->nullable()->after('challenge');
            $table->text('results')->nullable()->after('solution');
            $table->string('role', 100)->nullable()->after('results');
            $table->string('duration', 50)->nullable()->after('role');
            $table->string('color_primary', 7)->nullable()->after('duration');
            $table->string('color_secondary', 7)->nullable()->after('color_primary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'challenge', 'solution', 'results',
                'role', 'duration',
                'color_primary', 'color_secondary',
            ]);
        });
    }
};
