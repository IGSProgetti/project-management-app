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
        Schema::table('hours_redistributions', function (Blueprint $table) {
            $table->decimal('standard_hours', 8, 2)->nullable()->after('hours');
            $table->decimal('extra_hours', 8, 2)->nullable()->after('standard_hours');
            $table->decimal('extra_hourly_rate', 8, 2)->nullable()->after('hourly_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hours_redistributions', function (Blueprint $table) {
            $table->dropColumn(['standard_hours', 'extra_hours', 'extra_hourly_rate']);
        });
    }
};
