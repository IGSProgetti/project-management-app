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
        Schema::table('activities', function (Blueprint $table) {
            // Aggiungi la colonna has_multiple_resources
            if (!Schema::hasColumn('activities', 'has_multiple_resources')) {
                $table->boolean('has_multiple_resources')->default(false)->after('resource_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            if (Schema::hasColumn('activities', 'has_multiple_resources')) {
                $table->dropColumn('has_multiple_resources');
            }
        });
    }
};