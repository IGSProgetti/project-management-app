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
            // Aggiungi il campo resource_id dopo activity_id
            $table->foreignId('resource_id')
                  ->nullable()
                  ->after('activity_id')
                  ->constrained()
                  ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // Rimuovi la foreign key prima di eliminare la colonna
            $table->dropForeign(['resource_id']);
            $table->dropColumn('resource_id');
        });
    }
};
