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
        // Aggiungi colonne extra a resources solo se non esistono già
        if (Schema::hasTable('resources')) {
            if (!Schema::hasColumn('resources', 'extra_hours_day')) {
                Schema::table('resources', function (Blueprint $table) {
                    $table->decimal('extra_hours_day', 5, 2)->nullable()->after('working_hours_day');
                });
            }
            
            if (!Schema::hasColumn('resources', 'extra_cost_price')) {
                Schema::table('resources', function (Blueprint $table) {
                    $table->decimal('extra_cost_price', 10, 2)->nullable()->after('selling_price');
                });
            }
            
            if (!Schema::hasColumn('resources', 'extra_selling_price')) {
                Schema::table('resources', function (Blueprint $table) {
                    $table->decimal('extra_selling_price', 10, 2)->nullable()->after('extra_cost_price');
                });
            }
        }

        // Aggiungi colonne extra a project_resource solo se non esistono già
        if (Schema::hasTable('project_resource')) {
            if (!Schema::hasColumn('project_resource', 'extra_hours')) {
                Schema::table('project_resource', function (Blueprint $table) {
                    $table->decimal('extra_hours', 8, 2)->default(0)->after('hours');
                });
            }
            
            if (!Schema::hasColumn('project_resource', 'extra_adjusted_rate')) {
                Schema::table('project_resource', function (Blueprint $table) {
                    $table->decimal('extra_adjusted_rate', 10, 2)->nullable()->after('adjusted_rate');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rimuovi colonne da resources
        if (Schema::hasTable('resources')) {
            $columnsToRemove = ['extra_hours_day', 'extra_cost_price', 'extra_selling_price'];
            
            Schema::table('resources', function (Blueprint $table) use ($columnsToRemove) {
                $table->dropColumn($columnsToRemove);
            });
        }

        // Rimuovi colonne da project_resource
        if (Schema::hasTable('project_resource')) {
            $columnsToRemove = ['extra_hours', 'extra_adjusted_rate'];
            
            Schema::table('project_resource', function (Blueprint $table) use ($columnsToRemove) {
                $table->dropColumn($columnsToRemove);
            });
        }
    }
};