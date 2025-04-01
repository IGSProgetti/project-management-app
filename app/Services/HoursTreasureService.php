<?php
namespace App\Services;
use App\Models\Resource;
use App\Models\Project;
use App\Models\Client;
use App\Models\Activity;
use App\Models\Task;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class HoursTreasureService
{
    /**
     * Calcola i dati del tesoretto per tutte le risorse o per risorse specifiche.
     *
     * @param Collection|null $resources Le risorse per cui calcolare i dati (null = tutte)
     * @param array $filters Filtri da applicare (progetti, clienti, ecc.)
     * @return array Dati del tesoretto per risorsa
     */
    public function calculateTreasureData($resources = null, array $filters = [])
    {
        // Se non vengono fornite risorse, carica tutte le risorse attive
        if ($resources === null) {
            $resources = Resource::with(['projects', 'activities', 'primaryActivities.project.client', 'primaryActivities.tasks'])
                ->where('is_active', true)
                ->get();
        }
        
        return $this->prepareResourcesData($resources, $filters);
    }

    /**
     * Prepara i dati delle risorse con calcolo del tesoretto per tutti i livelli.
     *
     * @param Collection $resources Le risorse
     * @param array $filters Filtri da applicare
     * @return array Dati elaborati
     */
    private function prepareResourcesData($resources, array $filters = [])
{
    // Inizializza l'array dei dati delle risorse
    $resourcesData = [];
    
    // Estrai i filtri
    $projectIds = $filters['project_ids'] ?? [];
    $clientIds = $filters['client_ids'] ?? [];
    
    foreach ($resources as $resource) {
        $resourceData = [
            'id' => $resource->id,
            'name' => $resource->name,
            'role' => $resource->role,
            'standard_hours_per_year' => $resource->standard_hours_per_year,
            'extra_hours_per_year' => $resource->extra_hours_per_year,
            'total_estimated_hours' => 0,
            'total_actual_hours' => 0,
            'total_treasure_hours' => 0,
            // Ore stimate ed effettive per tipo (standard/extra)
            'standard_estimated_hours' => 0,
            'extra_estimated_hours' => 0,
            'standard_actual_hours' => 0,
            'extra_actual_hours' => 0,
            // Ore rimanenti per tipo (standard/extra)
            'remaining_standard_hours' => $resource->standard_hours_per_year,
            'remaining_extra_hours' => $resource->extra_hours_per_year,
            'by_client' => [],
            'by_project' => [],
            'by_activity' => []
        ];
        
        // Raccogli tutte le attività associate a questa risorsa
        $processedActivityIds = []; // Per evitare duplicazioni
        
        // Query consolidata per ottenere tutte le attività della risorsa
        $consolidatedActivities = Activity::with(['project.client', 'tasks'])
            ->where(function($query) use ($resource) {
                // Considera attività dove la risorsa è principale o nelle risorse multiple
                $query->where('resource_id', $resource->id)
                      ->orWhereHas('resources', function($resourceQuery) use ($resource) {
                          $resourceQuery->where('resources.id', $resource->id);
                      });
            })
            // Applica filtri di progetto e cliente
            ->when(!empty($projectIds), function($query) use ($projectIds) {
                $query->whereIn('project_id', $projectIds);
            })
            ->when(!empty($clientIds), function($query) use ($clientIds) {
                $query->whereHas('project.client', function($clientQuery) use ($clientIds) {
                    $clientQuery->whereIn('id', $clientIds);
                });
            })
            ->get()
            ->unique('id');
        
        // Ottieni anche i task assegnati direttamente a questa risorsa
        $tasksWithResource = Task::with(['activity.project.client'])
            ->where('resource_id', $resource->id)
            ->get();
            
        // Raccogli attività dai task assegnati direttamente
        foreach ($tasksWithResource as $task) {
            if ($task->activity && !in_array($task->activity_id, $processedActivityIds)) {
                // Aggiungi l'attività alla collezione
                $consolidatedActivities->push($task->activity);
            }
        }
        
        // Assicurati che la collezione sia unica
        $consolidatedActivities = $consolidatedActivities->unique('id');
        
        foreach ($consolidatedActivities as $activity) {
            // Salta se l'attività è già stata elaborata
            if (in_array($activity->id, $processedActivityIds)) {
                continue;
            }
            $processedActivityIds[] = $activity->id;
            
            // Ignora se non c'è progetto o cliente
            if (!$activity->project || !$activity->project->client) {
                continue;
            }
            
            // Determina il tipo di ore e la contribuzione della risorsa
            $resourcePivot = $activity->resources->firstWhere('id', $resource->id);
            $hoursType = $resourcePivot ? $resourcePivot->pivot->hours_type : $activity->hours_type;
            
            // Ottieni i task direttamente assegnati a questa risorsa
            $resourceTasks = $activity->tasks->filter(function($task) use ($resource) {
                // Se il task ha un resource_id, includi solo se corrisponde alla risorsa corrente
                return !$task->resource_id || $task->resource_id == $resource->id;
            });

            // Se l'attività ha task assegnati alla risorsa, calcola il tesoretto da quei task
            if ($resourceTasks->isNotEmpty()) {
                // Calcola minuti stimati ed effettivi dai task
                $taskEstimatedMinutes = $resourceTasks->sum('estimated_minutes');
                $taskActualMinutes = $resourceTasks->sum('actual_minutes');
                
                // Converti in ore
                $estimatedHours = $taskEstimatedMinutes / 60;
                $actualHours = $taskActualMinutes / 60;
                
                // Calcola la proporzione per aggiornare l'attività
                $resourceProportion = $activity->estimated_minutes > 0 
                    ? $taskEstimatedMinutes / $activity->estimated_minutes 
                    : 1;
            } else {
                // Calcola la proporzione di contribuzione della risorsa dall'attività
                $totalEstimatedMinutes = $activity->estimated_minutes;
                $resourceEstimatedMinutes = $resourcePivot 
                    ? $resourcePivot->pivot->estimated_minutes 
                    : $totalEstimatedMinutes;
                
                $resourceProportion = $totalEstimatedMinutes > 0 
                    ? $resourceEstimatedMinutes / $totalEstimatedMinutes 
                    : 1;
                
                // Calcola ore stimate ed effettive proporzionali
                $estimatedHours = ($activity->estimated_minutes * $resourceProportion) / 60;
                $actualHours = ($activity->actual_minutes * $resourceProportion) / 60;
            }
            
            // Calcola il tesoretto
            $treasureHours = $estimatedHours - $actualHours;
            
            // Aggiungi al totale della risorsa
            $resourceData['total_estimated_hours'] += $estimatedHours;
            $resourceData['total_actual_hours'] += $actualHours;
            $resourceData['total_treasure_hours'] += $treasureHours;
            
            // Aggiungi ai totali per tipo di ore
            if ($hoursType === 'standard') {
                $resourceData['standard_estimated_hours'] += $estimatedHours;
                $resourceData['standard_actual_hours'] += $actualHours;
                $resourceData['remaining_standard_hours'] -= $estimatedHours;
            } else {
                $resourceData['extra_estimated_hours'] += $estimatedHours;
                $resourceData['extra_actual_hours'] += $actualHours;
                $resourceData['remaining_extra_hours'] -= $estimatedHours;
            }
            
            // Gestione dati per cliente, progetto e attività
            $this->updateClientData($resourceData, $activity, $estimatedHours, $actualHours, $treasureHours, $hoursType);
            $this->updateProjectData($resourceData, $activity, $estimatedHours, $actualHours, $treasureHours, $hoursType);
            $this->updateActivityData($resourceData, $activity, $estimatedHours, $actualHours, $treasureHours, $hoursType, $resourceProportion);
        }
        
        // Calcolo utilizzo ore
        $resourceData['standard_hours_usage'] = $this->calculateHoursUsage(
            $resource->standard_hours_per_year, 
            $resourceData['standard_estimated_hours']
        );
        $resourceData['extra_hours_usage'] = $this->calculateHoursUsage(
            $resource->extra_hours_per_year, 
            $resourceData['extra_estimated_hours']
        );
        
        // Converti gli array associativi in array numerici per by_client e by_project
        $resourceData['by_client'] = array_values($resourceData['by_client']);
        $resourceData['by_project'] = array_values($resourceData['by_project']);
        
        $resourcesData[] = $resourceData;
    }
    
    return $resourcesData;
}

// Metodi helper per aggiornare i dati

private function updateClientData(&$resourceData, $activity, $estimatedHours, $actualHours, $treasureHours, $hoursType)
{
    $clientId = $activity->project->client->id;
    $clientName = $activity->project->client->name;
    
    if (!isset($resourceData['by_client'][$clientId])) {
        $resourceData['by_client'][$clientId] = [
            'id' => $clientId,
            'name' => $clientName,
            'estimated_hours' => 0,
            'actual_hours' => 0,
            'treasure_hours' => 0,
            'standard_estimated_hours' => 0,
            'extra_estimated_hours' => 0,
        ];
    }
    
    $resourceData['by_client'][$clientId]['estimated_hours'] += $estimatedHours;
    $resourceData['by_client'][$clientId]['actual_hours'] += $actualHours;
    $resourceData['by_client'][$clientId]['treasure_hours'] += $treasureHours;
    
    if ($hoursType === 'standard') {
        $resourceData['by_client'][$clientId]['standard_estimated_hours'] += $estimatedHours;
    } else {
        $resourceData['by_client'][$clientId]['extra_estimated_hours'] += $estimatedHours;
    }
}

private function updateProjectData(&$resourceData, $activity, $estimatedHours, $actualHours, $treasureHours, $hoursType)
{
    $projectId = $activity->project->id;
    $projectName = $activity->project->name;
    $clientId = $activity->project->client->id;
    $clientName = $activity->project->client->name;
    
    if (!isset($resourceData['by_project'][$projectId])) {
        $resourceData['by_project'][$projectId] = [
            'id' => $projectId,
            'name' => $projectName,
            'client_id' => $clientId,
            'client_name' => $clientName,
            'estimated_hours' => 0,
            'actual_hours' => 0,
            'treasure_hours' => 0,
            'standard_estimated_hours' => 0,
            'extra_estimated_hours' => 0,
        ];
    }
    
    $resourceData['by_project'][$projectId]['estimated_hours'] += $estimatedHours;
    $resourceData['by_project'][$projectId]['actual_hours'] += $actualHours;
    $resourceData['by_project'][$projectId]['treasure_hours'] += $treasureHours;
    
    if ($hoursType === 'standard') {
        $resourceData['by_project'][$projectId]['standard_estimated_hours'] += $estimatedHours;
    } else {
        $resourceData['by_project'][$projectId]['extra_estimated_hours'] += $estimatedHours;
    }
}

private function updateActivityData(&$resourceData, $activity, $estimatedHours, $actualHours, $treasureHours, $hoursType, $resourceProportion)
{
    $resourceData['by_activity'][] = [
        'id' => $activity->id,
        'name' => $activity->name,
        'project_id' => $activity->project->id,
        'project_name' => $activity->project->name,
        'client_id' => $activity->project->client->id,
        'client_name' => $activity->project->client->name,
        'estimated_hours' => $estimatedHours,
        'actual_hours' => $actualHours,
        'treasure_hours' => $treasureHours,
        'hours_type' => $hoursType,
        'status' => $activity->status,
        'resource_contribution' => round($resourceProportion * 100, 2)
    ];
}

private function calculateHoursUsage($totalYearlyHours, $estimatedHours)
{
    return $totalYearlyHours > 0 
        ? round(($estimatedHours / $totalYearlyHours) * 100, 2) 
        : 0;
}

    /**
     * Ottiene i dettagli delle attività e dei task per risorsa con calcolo del tesoretto.
     * Versione corretta per evitare duplicati e calcolare correttamente il tesoretto.
     *
     * @param int $resourceId ID della risorsa
     * @param array $filters Filtri da applicare
     * @return array Dettagli delle attività e task con tesoretto
     */
    public function getTaskDetailsByResource($resourceId, array $filters = [])
{
    // Carica la risorsa specifica
    $resource = Resource::with(['activities.project.client', 'activities.tasks', 'primaryActivities.project.client', 'primaryActivities.tasks'])
        ->where('id', $resourceId)
        ->where('is_active', true)
        ->firstOrFail();
        
    // Estrai i filtri
    $projectIds = $filters['project_ids'] ?? [];
    $clientIds = $filters['client_ids'] ?? [];
    $activityId = $filters['activity_id'] ?? null;
    $uniqueTasks = $filters['unique_tasks'] ?? true;
    
    // Raccogli tutte le attività associate a questa risorsa
    $allActivities = collect();
    
    // Crea un array per tenere traccia degli ID di attività già aggiunti
    $processedActivityIds = [];
    
    // 1. Attività dove questa risorsa è la principale (legacy)
    $legacyActivities = $resource->primaryActivities()
        ->with(['project.client', 'tasks'])
        ->where('has_multiple_resources', false)
        ->get();
        
    foreach ($legacyActivities as $activity) {
        // Controlla se questa attività è già stata elaborata
        if (!in_array($activity->id, $processedActivityIds)) {
            $processedActivityIds[] = $activity->id;
            $allActivities->push([
                'activity' => $activity,
                'estimated_minutes' => $activity->estimated_minutes,
                'actual_minutes' => $activity->actual_minutes,
                'hours_type' => $activity->hours_type,
                'is_pivot' => false
            ]);
        }
    }
    
    // 2. Attività dove questa risorsa è una delle multiple (relazione many-to-many)
    $pivotActivities = $resource->activities()
        ->with(['project.client', 'tasks'])
        ->get();
        
    foreach ($pivotActivities as $activity) {
        // Controlla se questa attività è già stata elaborata
        if (!in_array($activity->id, $processedActivityIds)) {
            $processedActivityIds[] = $activity->id;
            $pivotData = $activity->pivot;
            $allActivities->push([
                'activity' => $activity,
                'estimated_minutes' => $pivotData->estimated_minutes,
                'actual_minutes' => $pivotData->actual_minutes,
                'hours_type' => $pivotData->hours_type,
                'is_pivot' => true
            ]);
        }
    }
    
    // 3. Task assegnati direttamente alla risorsa (nuovo campo resource_id)
    $directlyAssignedTasks = Task::with(['activity.project.client'])
        ->where('resource_id', $resourceId)
        ->get();
        
    foreach ($directlyAssignedTasks as $task) {
        // Ottieni l'attività associata al task
        $activity = $task->activity;
        
        // Verifica che l'attività sia valida e non sia già stata processata
        if ($activity && !in_array($activity->id, $processedActivityIds)) {
            $processedActivityIds[] = $activity->id;
            
            // Determina il tipo di ore
            $hoursType = 'standard'; // Default se non specificato
            
            // Cerca nelle relazioni esistenti per il tipo di ore
            $resourcePivot = $activity->resources->firstWhere('id', $resourceId);
            if ($resourcePivot) {
                $hoursType = $resourcePivot->pivot->hours_type;
            } else if (!$activity->has_multiple_resources) {
                $hoursType = $activity->hours_type;
            }
            
            $allActivities->push([
                'activity' => $activity,
                'estimated_minutes' => $activity->estimated_minutes,
                'actual_minutes' => $activity->actual_minutes,
                'hours_type' => $hoursType,
                'is_pivot' => false,
                'directly_assigned' => true
            ]);
        }
    }
    
    // Filtra le attività
    $filteredActivities = $allActivities->filter(function ($activityData) use ($projectIds, $clientIds, $activityId) {
        $activity = $activityData['activity'];
        
        // Se è specificato un ID attività specifico, filtra solo per quello
        if ($activityId && $activity->id != $activityId) {
            return false;
        }
        
        // Verifica se l'attività appartiene a un progetto selezionato
        if (!empty($projectIds) && !in_array($activity->project_id, $projectIds)) {
            return false;
        }
        
        // Verifica se l'attività appartiene a un cliente selezionato
        if (!empty($clientIds) && $activity->project && !in_array($activity->project->client_id, $clientIds)) {
            return false;
        }
        
        return true;
    });
    
    // Prepara l'array di risposta
    $taskDetails = [];
    $processedTaskKeys = []; // Array per tenere traccia dei task già processati
    
    foreach ($filteredActivities as $activityData) {
        $activity = $activityData['activity'];
        $isPivot = $activityData['is_pivot'];
        $resourceEstimatedMinutes = $activityData['estimated_minutes'];
        $resourceActualMinutes = $activityData['actual_minutes'];
        $hoursType = $activityData['hours_type'];
        $directlyAssigned = $activityData['directly_assigned'] ?? false;
        
        // Ignora se non c'è progetto o cliente
        if (!$activity->project || !$activity->project->client) {
            continue;
        }
        
        // Ottieni i task dell'attività o quelli assegnati direttamente alla risorsa
        $activityTasks = $activity->tasks;
        
        // Se direttamente assegnati, filtra solo i task assegnati a questa risorsa
        if ($directlyAssigned) {
            $activityTasks = $activityTasks->where('resource_id', $resourceId);
        }
        
        if ($activityTasks && $activityTasks->count() > 0) {
            foreach ($activityTasks as $task) {
                // Aggiungi questa condizione: mostra solo i task senza risorsa o assegnati alla risorsa corrente
                if ($task->resource_id && $task->resource_id != $resourceId) {
                    continue; // Salta i task assegnati esplicitamente ad altre risorse
                }
                
                // Crea una chiave unica per il task per evitare duplicati
                $taskKey = $activity->id . '-' . $task->id;
                
                // Se è richiesta l'eliminazione dei duplicati e il task è già stato processato, salta
                if ($uniqueTasks && in_array($taskKey, $processedTaskKeys)) {
                    continue;
                }
                
                // Aggiungi la chiave all'array dei task processati
                $processedTaskKeys[] = $taskKey;
                
                // Per task direttamente assegnati alla risorsa, usa i valori completi
                if ($directlyAssigned || $task->resource_id == $resourceId) {
                    $taskEstimatedMinutes = $task->estimated_minutes;
                    $taskActualMinutes = $task->actual_minutes;
                } else {
                    // Calcola la proporzione di minuti stimati per questa risorsa
                    $resourceProportion = $activity->estimated_minutes > 0
                        ? $resourceEstimatedMinutes / $activity->estimated_minutes
                        : 1;
                    
                    // Calcola minuti stimati ed effettivi proporzionali
                    $taskEstimatedMinutes = $task->estimated_minutes * $resourceProportion;
                    $taskActualMinutes = $task->actual_minutes * $resourceProportion;
                }
                
                // Converti minuti in ore
                $estimatedHours = $taskEstimatedMinutes / 60;
                $actualHours = $taskActualMinutes / 60;
                
                // Calcola il tesoretto
                $treasureHours = $estimatedHours - $actualHours;
                
                // Determina stato per visualizzazione
                $statusLabel = '';
                switch ($task->status) {
                    case 'pending': $statusLabel = 'In attesa'; break;
                    case 'in_progress': $statusLabel = 'In corso'; break;
                    case 'completed': $statusLabel = 'Completato'; break;
                    default: $statusLabel = 'N/D'; break;
                }
                
                $hoursTypeLabel = ($hoursType === 'standard') ? 'Standard' : 'Extra';
                
                // Aggiungi i dettagli del task
                $taskDetails[] = [
                    'id' => $task->id,
                    'name' => $task->name,
                    'activity_id' => $activity->id,
                    'activity_name' => $activity->name,
                    'project_id' => $activity->project_id,
                    'project_name' => $activity->project->name,
                    'client_id' => $activity->project->client_id,
                    'client_name' => $activity->project->client->name,
                    'estimated_hours' => $estimatedHours,
                    'actual_hours' => $actualHours,
                    'treasure_hours' => $treasureHours,
                    'hours_type' => $hoursType,
                    'hours_type_label' => $hoursTypeLabel,
                    'status' => $task->status,
                    'status_label' => $statusLabel,
                    'completion_percentage' => $task->progress_percentage,
                    'is_overdue' => $task->is_overdue,
                    'is_over_estimated' => $task->is_over_estimated,
                    'resource_contribution' => $directlyAssigned || $task->resource_id == $resourceId ? 100 : 
                        ($isPivot ? round($resourceProportion * 100, 1) : 100),
                    'directly_assigned' => $directlyAssigned || $task->resource_id == $resourceId
                ];
            }
        } else {
            // Se non ci sono task, usa i dati dell'attività stessa
            // Genera una chiave unica per l'attività
            $activityKey = 'act-' . $activity->id;
            
            // Se è richiesta l'eliminazione dei duplicati e l'attività è già stata processata, salta
            if ($uniqueTasks && in_array($activityKey, $processedTaskKeys)) {
                continue;
            }
            
            // Aggiungi la chiave all'array dei task processati
            $processedTaskKeys[] = $activityKey;
            
            // Converti minuti in ore
            $estimatedHours = $resourceEstimatedMinutes / 60;
            $actualHours = $resourceActualMinutes / 60;
            
            // Calcola il tesoretto
            $treasureHours = $estimatedHours - $actualHours;
            
            // Determina stato per visualizzazione
            $statusLabel = '';
            switch ($activity->status) {
                case 'pending': $statusLabel = 'In attesa'; break;
                case 'in_progress': $statusLabel = 'In corso'; break;
                case 'completed': $statusLabel = 'Completato'; break;
                default: $statusLabel = 'N/D'; break;
            }
            
            $hoursTypeLabel = ($hoursType === 'standard') ? 'Standard' : 'Extra';
            
            // Calcola percentuale di completamento
            $completionPercentage = $estimatedHours > 0
                ? min(100, ($actualHours / $estimatedHours) * 100)
                : ($activity->status === 'completed' ? 100 : 0);
                
            // Aggiungi i dettagli dell'attività come task
            $taskDetails[] = [
                'id' => $activityKey, // Prefisso per distinguere le attività dai task
                'name' => $activity->name . " (No Tasks)",
                'activity_id' => $activity->id,
                'activity_name' => $activity->name,
                'project_id' => $activity->project_id,
                'project_name' => $activity->project->name,
                'client_id' => $activity->project->client_id,
                'client_name' => $activity->project->client->name,
                'estimated_hours' => $estimatedHours,
                'actual_hours' => $actualHours,
                'treasure_hours' => $treasureHours,
                'hours_type' => $hoursType,
                'hours_type_label' => $hoursTypeLabel,
                'status' => $activity->status,
                'status_label' => $statusLabel,
                'completion_percentage' => $completionPercentage,
                'is_overdue' => false,
                'is_over_estimated' => $actualHours > $estimatedHours && $estimatedHours > 0,
                'resource_contribution' => $isPivot ? ($activity->estimated_minutes > 0 ?
                    round(($resourceEstimatedMinutes / $activity->estimated_minutes) * 100, 1) : 100) : 100,
                'directly_assigned' => $directlyAssigned
            ];
        }
    }
    
    return $taskDetails;
}

    /**
     * Calcola l'efficienza delle risorse (rapporto ore effettive/stimate).
     *
     * @param array $resourcesData Dati delle risorse
     * @return array Statistiche sull'efficienza
     */
    public function calculateEfficiencyStats(array $resourcesData)
    {
        $stats = [
            'total_estimated' => 0,
            'total_actual' => 0,
            'total_treasure' => 0,
            'total_standard_hours' => 0,
            'total_extra_hours' => 0,
            'total_remaining_standard_hours' => 0,
            'total_remaining_extra_hours' => 0,
            'efficiency_rate' => 0,
            'by_resource' => []
        ];
        
        foreach ($resourcesData as $resourceData) {
            $stats['total_estimated'] += $resourceData['total_estimated_hours'];
            $stats['total_actual'] += $resourceData['total_actual_hours'];
            $stats['total_treasure'] += $resourceData['total_treasure_hours'];
            $stats['total_standard_hours'] += $resourceData['standard_hours_per_year'];
            $stats['total_extra_hours'] += $resourceData['extra_hours_per_year'];
            $stats['total_remaining_standard_hours'] += $resourceData['remaining_standard_hours'];
            $stats['total_remaining_extra_hours'] += $resourceData['remaining_extra_hours'];
            
            // Calcola efficienza (< 100% = più efficiente, > 100% = meno efficiente)
            $efficiency = $resourceData['total_estimated_hours'] > 0
                ? ($resourceData['total_actual_hours'] / $resourceData['total_estimated_hours']) * 100
                : 0;
                
            $stats['by_resource'][] = [
                'id' => $resourceData['id'],
                'name' => $resourceData['name'],
                'efficiency' => round($efficiency, 2)
            ];
        }
        
        $stats['efficiency_rate'] = $stats['total_estimated'] > 0
            ? ($stats['total_actual'] / $stats['total_estimated']) * 100
            : 0;
            
        $stats['efficiency_rate'] = round($stats['efficiency_rate'], 2);
        
        return $stats;
    }
}