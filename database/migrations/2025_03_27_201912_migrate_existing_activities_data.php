<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migra i dati dalle attività esistenti alla tabella pivot
        $activities = DB::table('activities')->whereNotNull('resource_id')->get();
        
        foreach ($activities as $activity) {
            // Verifica se esiste già una relazione (non dovrebbe, ma per sicurezza)
            $exists = DB::table('activity_resource')
                ->where('activity_id', $activity->id)
                ->where('resource_id', $activity->resource_id)
                ->exists();
            
            if (!$exists) {
                // Crea la relazione nella tabella pivot
                DB::table('activity_resource')->insert([
                    'activity_id' => $activity->id,
                    'resource_id' => $activity->resource_id,
                    'estimated_minutes' => $activity->estimated_minutes,
                    'actual_minutes' => $activity->actual_minutes,
                    'hours_type' => $activity->hours_type,
                    'estimated_cost' => $activity->estimated_cost,
                    'actual_cost' => $activity->actual_cost,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                // Aggiorna il flag has_multiple_resources
                DB::table('activities')
                    ->where('id', $activity->id)
                    ->update([
                        'has_multiple_resources' => false
                    ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // La down migration in questo caso non rimuove dati dalla tabella pivot
    }
};