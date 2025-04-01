<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Task;
use App\Models\Activity;
use Illuminate\Support\Facades\DB;

class TaskResourceSeeder extends Seeder
{
    /**
     * Run the database seeds per assegnare risorse ai task esistenti.
     */
    public function run(): void
    {
        $this->command->info('Inizializzazione assegnazione risorse ai task...');
        
        // Conta il numero totale di task
        $totalTasks = Task::whereNull('resource_id')->count();
        $this->command->info("Trovati {$totalTasks} task senza risorsa assegnata");
        
        // Processa ogni task senza risorsa
        Task::whereNull('resource_id')->orderBy('id')->chunk(100, function ($tasks) {
            foreach ($tasks as $task) {
                // Cerca l'attività associata
                $activity = Activity::with('resources')->find($task->activity_id);
                
                if (!$activity) {
                    $this->command->warn("Task ID {$task->id}: Attività non trovata");
                    continue;
                }
                
                $resourceId = null;
                
                // Strategie per determinare la risorsa da assegnare
                if (!$activity->has_multiple_resources && $activity->resource_id) {
                    // Se l'attività ha una singola risorsa, usa quella
                    $resourceId = $activity->resource_id;
                    $this->command->line("Task ID {$task->id}: Assegnato alla risorsa principale dell'attività (ID: {$resourceId})");
                } elseif ($activity->has_multiple_resources && $activity->resources->count() > 0) {
                    // Se l'attività ha più risorse, distribuisci in modo equo:
                    // Conta quanti task di questa attività sono già assegnati a ciascuna risorsa
                    $assignedResourceCounts = [];
                    
                    foreach ($activity->resources as $resource) {
                        $count = Task::where('activity_id', $activity->id)
                                     ->where('resource_id', $resource->id)
                                     ->count();
                                     
                        $assignedResourceCounts[$resource->id] = $count;
                    }
                    
                    // Assegna alla risorsa con meno task
                    if (!empty($assignedResourceCounts)) {
                        $resourceId = array_keys($assignedResourceCounts, min($assignedResourceCounts))[0];
                        $this->command->line("Task ID {$task->id}: Assegnato alla risorsa con minor carico (ID: {$resourceId})");
                    }
                }
                
                // Se è stata trovata una risorsa, assegnala
                if ($resourceId) {
                    $task->resource_id = $resourceId;
                    $task->save();
                    $this->command->info("Task ID {$task->id} aggiornato con risorsa ID: {$resourceId}");
                } else {
                    $this->command->error("Task ID {$task->id}: Nessuna risorsa disponibile per l'assegnazione");
                }
            }
        });
        
        $this->command->info('Migrazione dei task completata');
    }
}