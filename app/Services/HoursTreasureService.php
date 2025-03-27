<?php

namespace App\Services;

use App\Models\Resource;
use App\Models\Project;
use App\Models\Client;
use App\Models\Activity;
use Illuminate\Support\Collection;

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
            $resources = Resource::with(['projects', 'activities.project.client'])
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
                $treasureHours = $estimatedHours - $actualHours;
                
                // Aggiungi al totale della risorsa
                $resourceData['total_estimated_hours'] += $estimatedHours;
                $resourceData['total_actual_hours'] += $actualHours;
                $resourceData['total_treasure_hours'] += $treasureHours;
                
                // Aggiungi ai totali per cliente
                if (!isset($resourceData['by_client'][$clientId])) {
                    $resourceData['by_client'][$clientId] = [
                        'id' => $clientId,
                        'name' => $clientName,
                        'estimated_hours' => 0,
                        'actual_hours' => 0,
                        'treasure_hours' => 0,
                    ];
                }
                
                $resourceData['by_client'][$clientId]['estimated_hours'] += $estimatedHours;
                $resourceData['by_client'][$clientId]['actual_hours'] += $actualHours;
                $resourceData['by_client'][$clientId]['treasure_hours'] += $treasureHours;
                
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
                    ];
                }
                
                $resourceData['by_project'][$projectId]['estimated_hours'] += $estimatedHours;
                $resourceData['by_project'][$projectId]['actual_hours'] += $actualHours;
                $resourceData['by_project'][$projectId]['treasure_hours'] += $treasureHours;
                
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
                    'hours_type' => $activity->hours_type,
                    'status' => $activity->status
                ];
            }
            
            // Converti array associativi in array numerici per JSON
            $resourceData['by_client'] = array_values($resourceData['by_client']);
            $resourceData['by_project'] = array_values($resourceData['by_project']);
            
            // Calcola le statistiche di utilizzo delle ore annuali
            $standardHoursUsage = ($resource->standard_hours_per_year > 0) 
                ? ($resource->total_standard_actual_hours / $resource->standard_hours_per_year) * 100 
                : 0;
                
            $extraHoursUsage = ($resource->extra_hours_per_year > 0) 
                ? ($resource->total_extra_actual_hours / $resource->extra_hours_per_year) * 100 
                : 0;
                
            $resourceData['standard_hours_usage'] = round($standardHoursUsage, 2);
            $resourceData['extra_hours_usage'] = round($extraHoursUsage, 2);
            
            $resourcesData[] = $resourceData;
        }
        
        return $resourcesData;
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
            'efficiency_rate' => 0,
            'by_resource' => []
        ];
        
        foreach ($resourcesData as $resourceData) {
            $stats['total_estimated'] += $resourceData['total_estimated_hours'];
            $stats['total_actual'] += $resourceData['total_actual_hours'];
            $stats['total_treasure'] += $resourceData['total_treasure_hours'];
            
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