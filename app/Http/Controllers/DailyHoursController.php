<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Resource;
use App\Models\Task;
use App\Models\Activity;
use App\Models\Project;
use App\Models\Client;
use App\Models\HoursRedistribution;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DailyHoursController extends Controller
{
    /**
     * Display the daily hours management dashboard.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Solo amministratori possono accedere
        if (!$user->isAdmin()) {
            abort(403, 'Accesso non autorizzato');
        }
        
        $selectedDate = $request->get('date', Carbon::today()->format('Y-m-d'));
        $selectedClient = $request->get('client_id');
        $selectedProject = $request->get('project_id');
        
        // Carica dati per i filtri
        $clients = Client::all();
        $projects = Project::when($selectedClient, function($query) use ($selectedClient) {
            return $query->where('client_id', $selectedClient);
        })->get();
        
        // 🆕 NUOVO: Ottieni i dati dei budget clienti con redistribuzioni
        $clientsBudgetData = $this->getClientsBudgetData($selectedDate);
        
        // Ottieni i dati delle ore giornaliere
        $dailyHoursData = $this->getDailyHoursData($selectedDate, $selectedClient, $selectedProject);
        
        // Ottieni le redistribuzioni esistenti per questa data
        $redistributions = HoursRedistribution::forDate($selectedDate)
            ->with(['resource', 'fromClient', 'toClient', 'user'])
            ->get();
        
        return view('daily-hours.index', compact(
            'dailyHoursData', 
            'clients', 
            'projects', 
            'selectedDate',
            'selectedClient',
            'selectedProject',
            'redistributions',
            'clientsBudgetData' // 🆕 NUOVO: Passa i dati budget alla vista
        ));
    }
    
    /**
     * 🆕 NUOVO: Ottieni i dati budget e redistribuzioni per ogni cliente
     */
    private function getClientsBudgetData($date)
    {
        $clients = Client::all();
        $clientsData = [];
        
        foreach ($clients as $client) {
            // Calcola il budget utilizzato attraverso i progetti
            $budgetUsed = 0;
            foreach ($client->projects as $project) {
                // Calcola il costo effettivo delle attività del progetto
                foreach ($project->activities as $activity) {
                    if ($activity->resource) {
                        $hourlyRate = $activity->hours_type === 'standard' 
                            ? $activity->resource->selling_price 
                            : ($activity->resource->extra_selling_price ?? $activity->resource->selling_price * 1.2);
                        $budgetUsed += ($activity->actual_minutes / 60) * $hourlyRate;
                    }
                }
            }
            
            // Ottieni le redistribuzioni per questo cliente nella data selezionata
            $redistributions = HoursRedistribution::forDate($date)
                ->where(function($query) use ($client) {
                    $query->where('to_client_id', $client->id)
                          ->orWhere('from_client_id', $client->id);
                })
                ->with(['resource'])
                ->get();
            
            $hoursTransferredToday = 0;
            $valueTransferredToday = 0;
            
            foreach ($redistributions as $redistribution) {
                if ($redistribution->to_client_id == $client->id) {
                    // Ore ricevute
                    $hoursTransferredToday += $redistribution->hours;
                    $valueTransferredToday += $redistribution->total_value;
                } else {
                    // Ore trasferite ad altri
                    $hoursTransferredToday -= $redistribution->hours;
                    $valueTransferredToday -= $redistribution->total_value;
                }
            }
            
            $budgetRemaining = $client->budget - $budgetUsed;
            $budgetUsagePercentage = $client->budget > 0 ? ($budgetUsed / $client->budget) * 100 : 0;
            
            $clientsData[] = [
                'id' => $client->id,
                'name' => $client->name,
                'budget_total' => $client->budget,
                'budget_used' => $budgetUsed,
                'budget_remaining' => $budgetRemaining,
                'budget_usage_percentage' => $budgetUsagePercentage,
                'hours_transferred_today' => $hoursTransferredToday,
                'value_transferred_today' => $valueTransferredToday,
                'redistributions_count' => $redistributions->count()
            ];
        }
        
        return $clientsData;
    }
    
    /**
     * Get daily hours data for all resources.
     */
    private function getDailyHoursData($date, $clientId = null, $projectId = null)
{
    $resources = Resource::where('is_active', true)->get();
    $dailyData = [];
    
    foreach ($resources as $resource) {
        $resourceData = [
            'id' => $resource->id,
            'name' => $resource->name,
            'role' => $resource->role,
            'daily_hours_capacity' => $resource->working_hours_day ?? 8, // Default 8 ore
            'hourly_rate' => $resource->selling_price,
            'tasks' => [],
            'clients' => [],
            'total_hours_worked' => 0,
            'remaining_hours' => 0,
            'remaining_value' => 0,
            'redistributions' => []
        ];
        
        // 🆕 SEMPRE calcola le ore lavorate (anche se sono 0)
        $activitiesQuery = Activity::where(function($query) use ($resource) {
            $query->where('resource_id', $resource->id)
                  ->orWhereHas('resources', function($q) use ($resource) {
                      $q->where('resources.id', $resource->id);
                  });
        });
        
        // Applica filtri se specificati
        if ($clientId) {
            $activitiesQuery->whereHas('project', function($query) use ($clientId) {
                $query->where('client_id', $clientId);
            });
        }
        
        if ($projectId) {
            $activitiesQuery->where('project_id', $projectId);
        }
        
        $activities = $activitiesQuery->with(['project.client', 'tasks'])->get();
        
        // Raggruppa per cliente
        $clientsData = [];
        
        foreach ($activities as $activity) {
            $client = $activity->project->client;
            $project = $activity->project;
            
            // 🆕 CALCOLA ore lavorate in questa data specifica
            $tasksForDay = $activity->tasks()->whereDate('updated_at', $date)
                                             ->where('actual_minutes', '>', 0)
                                             ->get();
            
            $activityHours = 0;
            $activityTasks = [];
            
            foreach ($tasksForDay as $task) {
                $taskHours = $task->actual_minutes / 60;
                $activityHours += $taskHours;
                
                $activityTasks[] = [
                    'id' => $task->id,
                    'name' => $task->name,
                    'hours' => $taskHours,
                    'value' => $taskHours * $resource->selling_price
                ];
            }
            
            // 🆕 INCLUDE anche attività con 0 ore se hanno task per questa data
            if ($activityHours > 0 || $tasksForDay->count() > 0) {
                // Inizializza dati cliente se non esistono
                if (!isset($clientsData[$client->id])) {
                    $clientsData[$client->id] = [
                        'id' => $client->id,
                        'name' => $client->name,
                        'projects' => [],
                        'total_hours' => 0,
                        'total_value' => 0
                    ];
                }
                
                // Inizializza dati progetto se non esistono
                if (!isset($clientsData[$client->id]['projects'][$project->id])) {
                    $clientsData[$client->id]['projects'][$project->id] = [
                        'id' => $project->id,
                        'name' => $project->name,
                        'activities' => [],
                        'total_hours' => 0,
                        'total_value' => 0
                    ];
                }
                
                $activityValue = $activityHours * $resource->selling_price;
                
                $clientsData[$client->id]['projects'][$project->id]['activities'][] = [
                    'id' => $activity->id,
                    'name' => $activity->name,
                    'hours' => $activityHours,
                    'value' => $activityValue,
                    'tasks' => $activityTasks
                ];
                
                // Aggiorna totali
                $clientsData[$client->id]['projects'][$project->id]['total_hours'] += $activityHours;
                $clientsData[$client->id]['projects'][$project->id]['total_value'] += $activityValue;
                $clientsData[$client->id]['total_hours'] += $activityHours;
                $clientsData[$client->id]['total_value'] += $activityValue;
                $resourceData['total_hours_worked'] += $activityHours;
            }
        }
        
        // Ottieni le redistribuzioni esistenti per questa risorsa e data
        $redistributions = HoursRedistribution::forResource($resource->id)
            ->forDate($date)
            ->with(['fromClient', 'toClient', 'user'])
            ->get();
        
        $resourceData['redistributions'] = $redistributions->toArray();
        
        // 🆕 CALCOLA SEMPRE le ore rimanenti (anche se 0 ore lavorate)
        $redistributedHours = $redistributions->sum('hours');
        $remainingHours = $resourceData['daily_hours_capacity'] - $resourceData['total_hours_worked'] - $redistributedHours;
        
        // 🆕 MOSTRA sempre le ore rimanenti se > 0
        $resourceData['remaining_hours'] = max(0, $remainingHours);
        $resourceData['remaining_value'] = $resourceData['remaining_hours'] * $resource->selling_price;
        $resourceData['clients'] = array_values($clientsData);
        
        // 🆕 AGGIUNGI sempre la risorsa (anche con 0 ore lavorate)
        $dailyData[] = $resourceData;
    }
    
    return $dailyData;
}
    
    /**
     * Redistribute remaining hours to a client.
     */
    public function redistributeHours(Request $request)
    {
        $request->validate([
            'resource_id' => 'required|exists:resources,id',
            'client_id' => 'required|exists:clients,id',
            'hours' => 'required|numeric|min:0',
            'action' => 'required|in:return,transfer',
            'date' => 'required|date',
            'from_client_id' => 'nullable|exists:clients,id'
        ]);
        
        $resource = Resource::findOrFail($request->resource_id);
        $toClient = Client::findOrFail($request->client_id);
        $fromClient = $request->from_client_id ? Client::findOrFail($request->from_client_id) : null;
        $hours = $request->hours;
        $value = $hours * $resource->selling_price;
        
        try {
            DB::beginTransaction();
            
            // Crea il record di redistribuzione
            $redistribution = HoursRedistribution::create([
                'resource_id' => $resource->id,
                'from_client_id' => $fromClient ? $fromClient->id : null,
                'to_client_id' => $toClient->id,
                'user_id' => Auth::id(),
                'redistribution_date' => $request->date,
                'hours' => $hours,
                'hourly_rate' => $resource->selling_price,
                'total_value' => $value,
                'action_type' => $request->action,
                'notes' => $request->notes
            ]);
            
            if ($request->action === 'return') {
                // Rimetti le ore nel budget del cliente
                $toClient->budget += $value;
                $toClient->save();
                
                $message = "Restituite {$hours} ore (€{$value}) al budget di {$toClient->name}";
            } else {
                // Trasferimento: sottrai dal cliente di origine (se specificato) e aggiungi al destinatario
                if ($fromClient) {
                    $fromClient->budget -= $value;
                    $fromClient->save();
                }
                
                $toClient->budget += $value;
                $toClient->save();
                
                $message = "Trasferite {$hours} ore (€{$value}) " . 
                          ($fromClient ? "da {$fromClient->name} " : "") . 
                          "a {$toClient->name}";
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => $message,
                'redistribution_id' => $redistribution->id
            ]);
            
        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => 'Errore durante la redistribuzione: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get projects by client for AJAX calls.
     */
    public function getProjectsByClient(Request $request)
    {
        $clientId = $request->get('client_id');
        $projects = Project::where('client_id', $clientId)->get(['id', 'name']);
        
        return response()->json($projects);
    }
    
    /**
     * Export daily hours data.
     */
    public function export(Request $request)
    {
        $date = $request->get('date', Carbon::today()->format('Y-m-d'));
        $clientId = $request->get('client_id');
        $projectId = $request->get('project_id');
        
        $dailyHoursData = $this->getDailyHoursData($date, $clientId, $projectId);
        
        // Qui puoi implementare l'export Excel/CSV usando Laravel Excel
        // Per ora restituisco JSON
        return response()->json([
            'data' => $dailyHoursData,
            'export_date' => $date,
            'filters' => [
                'client_id' => $clientId,
                'project_id' => $projectId
            ]
        ]);
    }
    
    /**
     * Undo a redistribution
     */
    public function undoRedistribution(Request $request, $id)
    {
        $redistribution = HoursRedistribution::findOrFail($id);
        
        // Verifica che la redistribuzione sia stata fatta oggi (o permetti solo entro un certo tempo)
        if ($redistribution->created_at->diffInHours(now()) > 24) {
            return response()->json([
                'success' => false,
                'message' => 'Non è possibile annullare redistribuzioni più vecchie di 24 ore'
            ], 400);
        }
        
        try {
            DB::beginTransaction();
            
            // Inverti l'operazione sul budget
            if ($redistribution->action_type === 'return') {
                $redistribution->toClient->budget -= $redistribution->total_value;
                $redistribution->toClient->save();
            } else {
                $redistribution->toClient->budget -= $redistribution->total_value;
                $redistribution->toClient->save();
                
                if ($redistribution->fromClient) {
                    $redistribution->fromClient->budget += $redistribution->total_value;
                    $redistribution->fromClient->save();
                }
            }
            
            // Elimina il record di redistribuzione
            $redistribution->delete();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Redistribuzione annullata con successo'
            ]);
            
        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'annullamento: ' . $e->getMessage()
            ], 500);
        }
    }
}