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
        Schema::table('resources', function (Blueprint $table) {
            // Campi per il tesoretto ore
            $table->integer('treasure_days')->default(0)->after('is_active');
            $table->decimal('treasure_hours_per_day', 5, 2)->default(0)->after('treasure_days');
            $table->decimal('treasure_total_hours', 8, 2)->default(0)->after('treasure_hours_per_day');
            $table->decimal('treasure_available_hours', 8, 2)->default(0)->after('treasure_total_hours');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('resources', function (Blueprint $table) {
            $table->dropColumn([
                'treasure_days',
                'treasure_hours_per_day', 
                'treasure_total_hours',
                'treasure_available_hours'
            ]);
        });
    }
};