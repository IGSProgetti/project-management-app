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
        Schema::table('tasks', function (Blueprint $table) {
            // Aggiungiamo i campi per i minuti stimati ed effettivi
            $table->integer('estimated_minutes')->default(0)->after('order');
            $table->integer('actual_minutes')->default(0)->after('estimated_minutes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['estimated_minutes', 'actual_minutes']);
        });
    }
};
