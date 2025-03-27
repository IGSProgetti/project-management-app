<?php

namespace App\Services;

use App\Models\Resource;
use App\Models\Project;
use App\Models\Client;
use App\Models\Activity;
use App\Models\Task;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

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
            $resources = Resource::with(['projects', 'activities.project.client', 'activities.tasks'])
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
            
            // Filtra le attività in base ai parametri forniti
            $filteredActivities = $resource->activities->filter(function ($activity) use ($projectIds, $clientIds) {
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
            
            // Elabora le attività filtrate
            foreach ($filteredActivities as $activity) {
                // Ignora se non c'è progetto o cliente
                if (!$activity->project || !$activity->project->client) {
                    continue;
                }
                
                $clientId = $activity->project->client->id;
                $clientName = $activity->project->client->name;
                $projectId = $activity->project->id;
                $projectName = $activity->project->name;
                
                // Converti minuti in ore
                $estimatedHours = $activity->estimated_minutes / 60;
                $actualHours = $activity->actual_minutes / 60;
                
                // Calcola il tesoretto solo per attività completate
                $treasureHours = 0;
                if ($activity->status === 'completed') {
                    // Calcola la differenza tra ore stimate ed effettive (può essere negativo)
                    $treasureHours = $estimatedHours - $actualHours;
                    
                    // Debug: stampa i valori per verificare i calcoli
                    Log::debug("Activity: {$activity->name}, Estimated: {$estimatedHours}, Actual: {$actualHours}, Treasure: {$treasureHours}");
                }
                
                // Determina il tipo di ore (standard o extra)
                $hoursType = $activity->hours_type ?: 'standard';
                
                // Aggiungi al totale della risorsa
                $resourceData['total_estimated_hours'] += $estimatedHours;
                $resourceData['total_actual_hours'] += $actualHours;
                $resourceData['total_treasure_hours'] += $treasureHours;
                
                // Aggiungi ai totali per tipo di ore
                if ($hoursType === 'standard') {
                    $resourceData['standard_estimated_hours'] += $estimatedHours;
                    $resourceData['standard_actual_hours'] += $actualHours;
                    
                    // Sottrai le ore stimate dalle ore standard rimanenti
                    $resourceData['remaining_standard_hours'] -= $estimatedHours;
                } else {
                    $resourceData['extra_estimated_hours'] += $estimatedHours;
                    $resourceData['extra_actual_hours'] += $actualHours;
                    
                    // Sottrai le ore stimate dalle ore extra rimanenti
                    $resourceData['remaining_extra_hours'] -= $estimatedHours;
                }
                
                // Aggiungi ai totali per cliente
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
                
                // Aggiungi ai totali per tipo di ore per cliente
                if ($hoursType === 'standard') {
                    $resourceData['by_client'][$clientId]['standard_estimated_hours'] += $estimatedHours;
                } else {
                    $resourceData['by_client'][$clientId]['extra_estimated_hours'] += $estimatedHours;
                }
                
                // Aggiungi ai totali per progetto
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
                
                // Aggiungi ai totali per tipo di ore per progetto
                if ($hoursType === 'standard') {
                    $resourceData['by_project'][$projectId]['standard_estimated_hours'] += $estimatedHours;
                } else {
                    $resourceData['by_project'][$projectId]['extra_estimated_hours'] += $estimatedHours;
                }
                
                // Aggiungi i dati per attività
                $resourceData['by_activity'][] = [
                    'id' => $activity->id,
                    'name' => $activity->name,
                    'project_id' => $projectId,
                    'project_name' => $projectName,
                    'client_id' => $clientId,
                    'client_name' => $clientName,
                    'estimated_hours' => $estimatedHours,
                    'actual_hours' => $actualHours,
                    'treasure_hours' => $treasureHours,
                    'hours_type' => $hoursType,
                    'status' => $activity->status
                ];
            }
            
            // Converti array associativi in array numerici per JSON
            $resourceData['by_client'] = array_values($resourceData['by_client']);
            $resourceData['by_project'] = array_values($resourceData['by_project']);
            
            // Calcola le statistiche di utilizzo delle ore annuali
            $standardHoursUsage = ($resource->standard_hours_per_year > 0) 
                ? ($resourceData['standard_estimated_hours'] / $resource->standard_hours_per_year) * 100 
                : 0;
                
            $extraHoursUsage = ($resource->extra_hours_per_year > 0) 
                ? ($resourceData['extra_estimated_hours'] / $resource->extra_hours_per_year) * 100 
                : 0;
                
            $resourceData['standard_hours_usage'] = round($standardHoursUsage, 2);
            $resourceData['extra_hours_usage'] = round($extraHoursUsage, 2);
            
            $resourcesData[] = $resourceData;
        }
        
        return $resourcesData;
    }
    
    /**
     * Ottiene i dettagli delle attività e dei task per risorsa con calcolo del tesoretto.
     * 
     * @param int $resourceId ID della risorsa
     * @param array $filters Filtri da applicare
     * @return array Dettagli delle attività e task con tesoretto
     */
    public function getTaskDetailsByResource($resourceId, array $filters = [])
    {
        // Carica la risorsa specifica con le sue attività e task
        $resource = Resource::with(['activities.project.client', 'activities.tasks'])
            ->where('id', $resourceId)
            ->where('is_active', true)
            ->firstOrFail();
            
        // Estrai i filtri
        $projectIds = $filters['project_ids'] ?? [];
        $clientIds = $filters['client_ids'] ?? [];
        $activityId = $filters['activity_id'] ?? null;
        
        // Filtra le attività
        $filteredActivities = $resource->activities->filter(function ($activity) use ($projectIds, $clientIds, $activityId) {
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
        
        foreach ($filteredActivities as $activity) {
            // Ignora se non c'è progetto o cliente
            if (!$activity->project || !$activity->project->client) {
                continue;
            }
            
            // Recupera i task dell'attività
            if ($activity->tasks && $activity->tasks->count() > 0) {
                foreach ($activity->tasks as $task) {
                    // Converti minuti in ore
                    $estimatedHours = $task->estimated_minutes / 60;
                    $actualHours = $task->actual_minutes / 60;
                    
                    // Calcola il tesoretto solo per task completati
                    $treasureHours = 0;
                    if ($task->status === 'completed') {
                        $treasureHours = $estimatedHours - $actualHours;
                    }
                    
                    // Determina stato per visualizzazione
                    $statusLabel = '';
                    switch ($task->status) {
                        case 'pending': $statusLabel = 'In attesa'; break;
                        case 'in_progress': $statusLabel = 'In corso'; break;
                        case 'completed': $statusLabel = 'Completato'; break;
                        default: $statusLabel = 'N/D'; break;
                    }
                    
                    $hoursType = $activity->hours_type ?: 'standard';
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
                        'is_over_estimated' => $task->is_over_estimated
                    ];
                }
            } else {
                // Se non ci sono task, usa i dati dell'attività stessa
                $estimatedHours = $activity->estimated_minutes / 60;
                $actualHours = $activity->actual_minutes / 60;
                
                // Calcola il tesoretto solo per attività completate
                $treasureHours = 0;
                if ($activity->status === 'completed') {
                    $treasureHours = $estimatedHours - $actualHours;
                }
                
                // Determina stato per visualizzazione
                $statusLabel = '';
                switch ($activity->status) {
                    case 'pending': $statusLabel = 'In attesa'; break;
                    case 'in_progress': $statusLabel = 'In corso'; break;
                    case 'completed': $statusLabel = 'Completato'; break;
                    default: $statusLabel = 'N/D'; break;
                }
                
                $hoursType = $activity->hours_type ?: 'standard';
                $hoursTypeLabel = ($hoursType === 'standard') ? 'Standard' : 'Extra';
                
                // Calcola percentuale di completamento
                $completionPercentage = $estimatedHours > 0 
                    ? min(100, ($actualHours / $estimatedHours) * 100) 
                    : ($activity->status === 'completed' ? 100 : 0);
                
                // Aggiungi i dettagli dell'attività come task
                $taskDetails[] = [
                    'id' => "act-" . $activity->id,  // Prefisso per distinguere le attività dai task
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
                    'is_over_estimated' => $actualHours > $estimatedHours && $estimatedHours > 0
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