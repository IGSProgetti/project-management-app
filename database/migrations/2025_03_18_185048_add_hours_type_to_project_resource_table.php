<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Primo passaggio: aggiungere la colonna hours_type
        if (Schema::hasTable('project_resource') && !Schema::hasColumn('project_resource', 'hours_type')) {
            Schema::table('project_resource', function (Blueprint $table) {
                $table->enum('hours_type', ['standard', 'extra'])->default('standard')->after('hours');
            });
        }

        // Secondo passaggio: aggiungere la colonna default_hours_type alla tabella projects
        if (Schema::hasTable('projects') && !Schema::hasColumn('projects', 'default_hours_type')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->enum('default_hours_type', ['standard', 'extra'])->default('standard')->after('status');
            });
        }

        // Terzo passaggio: per ogni riga con extra_hours > 0, creare una nuova riga con hours_type='extra'
        if (Schema::hasTable('project_resource') && 
            Schema::hasColumn('project_resource', 'extra_hours') && 
            Schema::hasColumn('project_resource', 'hours_type')) {
            
            // Ottiene tutte le righe con extra_hours > 0
            $extraRows = DB::table('project_resource')
                ->where('extra_hours', '>', 0)
                ->get();
            
            foreach ($extraRows as $row) {
                // Inserisci una nuova riga per le ore extra
                DB::table('project_resource')->insert([
                    'project_id' => $row->project_id,
                    'resource_id' => $row->resource_id,
                    'hours' => $row->extra_hours,
                    'hours_type' => 'extra',
                    'adjusted_rate' => $row->extra_adjusted_rate ?: $row->adjusted_rate,
                    'cost' => $row->extra_hours * ($row->extra_adjusted_rate ?: $row->adjusted_rate),
                    'created_at' => $row->created_at,
                    'updated_at' => now()
                ]);
                
                // Aggiorna la riga originale per essere esplicitamente standard
                DB::table('project_resource')
                    ->where('id', $row->id)
                    ->update([
                        'hours_type' => 'standard',
                        'extra_hours' => 0,
                        'extra_adjusted_rate' => null
                    ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Ripristina i dati dal nuovo formato al vecchio formato
        if (Schema::hasTable('project_resource') && 
            Schema::hasColumn('project_resource', 'hours_type') &&
            Schema::hasColumn('project_resource', 'extra_hours')) {
            
            // Trova tutte le righe di tipo "extra"
            $extraRows = DB::table('project_resource')
                ->where('hours_type', 'extra')
                ->get();
            
            foreach ($extraRows as $extraRow) {
                // Trova la corrispondente riga "standard" per lo stesso project/resource
                $standardRow = DB::table('project_resource')
                    ->where('project_id', $extraRow->project_id)
                    ->where('resource_id', $extraRow->resource_id)
                    ->where('hours_type', 'standard')
                    ->first();
                
                if ($standardRow) {
                    // Aggiorna la riga standard con i dati extra
                    DB::table('project_resource')
                        ->where('id', $standardRow->id)
                        ->update([
                            'extra_hours' => $extraRow->hours,
                            'extra_adjusted_rate' => $extraRow->adjusted_rate
                        ]);
                    
                    // Elimina la riga extra
                    DB::table('project_resource')
                        ->where('id', $extraRow->id)
                        ->delete();
                }
            }
        }

        // Rimuovi le colonne aggiunte
        if (Schema::hasTable('projects') && Schema::hasColumn('projects', 'default_hours_type')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->dropColumn('default_hours_type');
            });
        }
        
        if (Schema::hasTable('project_resource') && Schema::hasColumn('project_resource', 'hours_type')) {
            Schema::table('project_resource', function (Blueprint $table) {
                $table->dropColumn('hours_type');
            });
        }
    }
};