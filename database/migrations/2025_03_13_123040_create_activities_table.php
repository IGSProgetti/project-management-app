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
        // Crea la tabella activities se non esiste già
        if (!Schema::hasTable('activities')) {
            Schema::create('activities', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->foreignId('project_id')->constrained()->onDelete('cascade');
                $table->foreignId('area_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('resource_id')->constrained()->onDelete('cascade');
                $table->integer('estimated_minutes');
                $table->integer('actual_minutes')->default(0);
                $table->enum('hours_type', ['standard', 'extra'])->default('standard');
                $table->decimal('estimated_cost', 10, 2);
                $table->decimal('actual_cost', 10, 2)->default(0);
                $table->date('due_date')->nullable();
                $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending');
                $table->timestamps();
            });
        } else {
            // Se la tabella esiste già, aggiungi solo la colonna hours_type se non presente
            if (!Schema::hasColumn('activities', 'hours_type')) {
                Schema::table('activities', function (Blueprint $table) {
                    $table->enum('hours_type', ['standard', 'extra'])->default('standard')->after('actual_minutes');
                });
            }
        }

        // Assicurati che la tabella project_resource esista e abbia la colonna hours_type
        if (Schema::hasTable('project_resource')) {
            if (!Schema::hasColumn('project_resource', 'hours_type')) {
                Schema::table('project_resource', function (Blueprint $table) {
                    $table->enum('hours_type', ['standard', 'extra'])->default('standard')->after('hours');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rimuovi la colonna hours_type dalla tabella activities se esiste
        if (Schema::hasColumn('activities', 'hours_type')) {
            Schema::table('activities', function (Blueprint $table) {
                $table->dropColumn('hours_type');
            });
        }

        // Rimuovi la colonna hours_type dalla tabella project_resource se esiste
        if (Schema::hasColumn('project_resource', 'hours_type')) {
            Schema::table('project_resource', function (Blueprint $table) {
                $table->dropColumn('hours_type');
            });
        }
    }
};