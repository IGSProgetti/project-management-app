<?php

namespace App\Http\Controllers;

use App\Models\Resource;
use App\Models\Project;
use App\Models\Client;
use App\Services\HoursTreasureService;
use Illuminate\Http\Request;

class ResourceHoursController extends Controller
{
    protected $treasureService;
    
    /**
     * Crea una nuova istanza del controller.
     *
     * @param HoursTreasureService $treasureService
     * @return void
     */
    public function __construct(HoursTreasureService $treasureService)
    {
        $this->treasureService = $treasureService;
        $this->middleware('auth');
    }
    
    /**
     * Mostra la dashboard di gestione orario.
     */
    public function index(Request $request)
    {
        // Carica tutte le risorse attive con progetti e attività
        $resources = Resource::with(['projects', 'activities.project.client'])
            ->where('is_active', true)
            ->get();
            
        // Carica elenchi per i filtri
        $projects = Project::orderBy('name')->get();
        $clients = Client::orderBy('name')->get();
        
        // Prepara i dati del tesoretto usando il servizio
        $filters = [
            'project_ids' => $request->input('project_ids', []),
            'client_ids' => $request->input('client_ids', []),
        ];
        
        $resourcesData = $this->treasureService->calculateTreasureData($resources, $filters);
        
        return view('hours.index', compact('resources', 'resourcesData', 'projects', 'clients'));
    }
    
    /**
     * Filtra i dati in base ai parametri della richiesta.
     */
    public function filter(Request $request)
    {
        // Validazione dei filtri
        $request->validate([
            'project_ids' => 'nullable|array',
            'project_ids.*' => 'exists:projects,id',
            'client_ids' => 'nullable|array',
            'client_ids.*' => 'exists:clients,id',
            'resource_ids' => 'nullable|array',
            'resource_ids.*' => 'exists:resources,id',
        ]);
        
        // Costruisci la query base
        $resourcesQuery = Resource::with(['projects', 'activities.project.client'])
            ->where('is_active', true);
            
        // Filtra per risorse specifiche se richiesto
        if ($request->has('resource_ids') && !empty($request->resource_ids)) {
            $resourcesQuery->whereIn('id', $request->resource_ids);
        }
        
        // Esegui la query
        $resources = $resourcesQuery->get();
        
        // Prepara i dati del tesoretto usando il servizio
        $filters = [
            'project_ids' => $request->input('project_ids', []),
            'client_ids' => $request->input('client_ids', []),
        ];
        
        $resourcesData = $this->treasureService->calculateTreasureData($resources, $filters);
        
        // Calcola le statistiche di efficienza
        $efficiencyStats = $this->treasureService->calculateEfficiencyStats($resourcesData);
        
        return response()->json([
            'success' => true,
            'resourcesData' => $resourcesData,
            'efficiencyStats' => $efficiencyStats
        ]);
    }
    
    /**
     * Esporta i dati delle ore in formato CSV.
     */
    public function export(Request $request)
    {
        // Validazione
        $request->validate([
            'project_ids' => 'nullable|array',
            'project_ids.*' => 'exists:projects,id',
            'client_ids' => 'nullable|array',
            'client_ids.*' => 'exists:clients,id',
            'resource_ids' => 'nullable|array',
            'resource_ids.*' => 'exists:resources,id',
            'export_type' => 'required|in:resources,clients,projects,activities',
        ]);
        
        // Costruisci la query base
        $resourcesQuery = Resource::with(['projects', 'activities.project.client'])
            ->where('is_active', true);
            
        // Filtra per risorse specifiche se richiesto
        if ($request->has('resource_ids') && !empty($request->resource_ids)) {
            $resourcesQuery->whereIn('id', $request->resource_ids);
        }
        
        // Esegui la query
        $resources = $resourcesQuery->get();
        
        // Prepara i dati del tesoretto
        $filters = [
            'project_ids' => $request->input('project_ids', []),
            'client_ids' => $request->input('client_ids', []),
        ];
        
        $resourcesData = $this->treasureService->calculateTreasureData($resources, $filters);
        
        // Genera il CSV in base al tipo di export richiesto
        $exportType = $request->input('export_type');
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="gestione-orario-' . $exportType . '.csv"',
        ];
        
        $callback = function() use ($resourcesData, $exportType) {
            $handle = fopen('php://output', 'w');
            
            // Intestazioni differenti in base al tipo di export
            switch ($exportType) {
                case 'resources':
                    fputcsv($handle, ['Risorsa', 'Ruolo', 'Ore Standard/Anno', 'Ore Extra/Anno', 'Ore Stimate', 'Ore Effettive', 'Tesoretto', 'Utilizzo Ore Standard %', 'Utilizzo Ore Extra %']);
                    
                    foreach ($resourcesData as $resource) {
                        fputcsv($handle, [
                            $resource['name'],
                            $resource['role'],
                            $resource['standard_hours_per_year'],
                            $resource['extra_hours_per_year'],
                            $resource['total_estimated_hours'],
                            $resource['total_actual_hours'],
                            $resource['total_treasure_hours'],
                            $resource['standard_hours_usage'],
                            $resource['extra_hours_usage']
                        ]);
                    }
                    break;
                    
                case 'clients':
                    fputcsv($handle, ['Risorsa', 'Cliente', 'Ore Stimate', 'Ore Effettive', 'Tesoretto']);
                    
                    foreach ($resourcesData as $resource) {
                        foreach ($resource['by_client'] as $client) {
                            fputcsv($handle, [
                                $resource['name'],
                                $client['name'],
                                $client['estimated_hours'],
                                $client['actual_hours'],
                                $client['treasure_hours']
                            ]);
                        }
                    }
                    break;
                    
                case 'projects':
                    fputcsv($handle, ['Risorsa', 'Progetto', 'Cliente', 'Ore Stimate', 'Ore Effettive', 'Tesoretto']);
                    
                    foreach ($resourcesData as $resource) {
                        foreach ($resource['by_project'] as $project) {
                            fputcsv($handle, [
                                $resource['name'],
                                $project['name'],
                                $project['client_name'],
                                $project['estimated_hours'],
                                $project['actual_hours'],
                                $project['treasure_hours']
                            ]);
                        }
                    }
                    break;
                    
                case 'activities':
                    fputcsv($handle, ['Risorsa', 'Attività', 'Progetto', 'Cliente', 'Tipo Ore', 'Stato', 'Ore Stimate', 'Ore Effettive', 'Tesoretto']);
                    
                    foreach ($resourcesData as $resource) {
                        foreach ($resource['by_activity'] as $activity) {
                            $status = '';
                            switch ($activity['status']) {
                                case 'pending': $status = 'In attesa'; break;
                                case 'in_progress': $status = 'In corso'; break;
                                case 'completed': $status = 'Completato'; break;
                                default: $status = 'N/D'; break;
                            }
                            
                            $hoursType = $activity['hours_type'] == 'standard' ? 'Standard' : 'Extra';
                            
                            fputcsv($handle, [
                                $resource['name'],
                                $activity['name'],
                                $activity['project_name'],
                                $activity['client_name'],
                                $hoursType,
                                $status,
                                $activity['estimated_hours'],
                                $activity['actual_hours'],
                                $activity['treasure_hours']
                            ]);
                        }
                    }
                    break;
            }
            
            fclose($handle);
        };
        
        return response()->stream($callback, 200, $headers);
    }
}