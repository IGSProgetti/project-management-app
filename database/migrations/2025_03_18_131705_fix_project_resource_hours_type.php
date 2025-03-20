<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // Parte 1: Gestione del campo hours_type nella tabella project_resource
        // Controlla se la colonna hours_type esiste già
        if (!Schema::hasColumn('project_resource', 'hours_type')) {
            // Se non esiste, aggiungila
            Schema::table('project_resource', function (Blueprint $table) {
                $table->enum('hours_type', ['standard', 'extra'])->default('standard')->after('hours');
            });
        } else {
            // Se esiste ma ha problemi, ricreala
            Schema::table('project_resource', function (Blueprint $table) {
                $table->dropColumn('hours_type');
            });
            Schema::table('project_resource', function (Blueprint $table) {
                $table->enum('hours_type', ['standard', 'extra'])->default('standard')->after('hours');
            });
        }

        // Parte 2: Aggiunta del campo default_hours_type alla tabella projects
        if (Schema::hasTable('projects') && !Schema::hasColumn('projects', 'default_hours_type')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->enum('default_hours_type', ['standard', 'extra'])->default('standard')->after('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        // Rimuovi il campo hours_type dalla tabella project_resource
        if (Schema::hasColumn('project_resource', 'hours_type')) {
            Schema::table('project_resource', function (Blueprint $table) {
                $table->dropColumn('hours_type');
            });
        }

        // Rimuovi il campo default_hours_type dalla tabella projects
        if (Schema::hasTable('projects') && Schema::hasColumn('projects', 'default_hours_type')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->dropColumn('default_hours_type');
            });
        }
    }
};