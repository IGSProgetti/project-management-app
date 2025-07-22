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
        // Aggiungi flag ai clienti
        Schema::table('clients', function (Blueprint $table) {
            $table->boolean('created_from_tasks')->default(false)->after('notes');
            $table->timestamp('tasks_created_at')->nullable()->after('created_from_tasks');
        });

        // Aggiungi flag ai progetti
        Schema::table('projects', function (Blueprint $table) {
            $table->boolean('created_from_tasks')->default(false)->after('status');
            $table->timestamp('tasks_created_at')->nullable()->after('created_from_tasks');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['created_from_tasks', 'tasks_created_at']);
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['created_from_tasks', 'tasks_created_at']);
        });
    }
};
