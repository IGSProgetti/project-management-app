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
use Illuminate\Support\Facades\Log;

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
        
        // Ottieni i dati dei budget clienti con redistribuzioni
        $clientsBudgetData = $this->getClientsBudgetData($selectedDate);
        
        // 🆕 NUOVO: Ottieni i dati delle ore giornaliere con gestione unificata
        $dailyHoursData = $this->getDailyHoursDataUnified($selectedDate, $selectedClient, $selectedProject);
        
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
            'clientsBudgetData'
        ));
    }
    
    /**
     * 🆕 NUOVO: Ottieni i dati delle ore giornaliere con gestione unificata standard + extra
     */
    private function getDailyHoursDataUnified($date, $clientId = null, $projectId = null)
    {
        $resources = Resource::where('is_active', true)->get();
        $dailyData = [];
        
        foreach ($resources as $resource) {
            // 🆕 CALCOLO CAPACITÀ UNIFICATA
            $standardDailyCapacity = $resource->working_hours_day ?? 5; // Default 5 ore standard
            $extraDailyCapacity = $resource->extra_hours_day ?? 3; // Default 3 ore extra
            $unifiedCapacity = $standardDailyCapacity + $extraDailyCapacity;
            
            // 🆕 TARIFFE DIFFERENZIATE
            $standardHourlyRate = $resource->selling_price ?? 50;
            $extraHourlyRate = $resource->extra_selling_price ?? ($standardHourlyRate * 1.2);
            
            $resourceData = [
                'id' => $resource->id,
                'name' => $resource->name,
                'role' => $resource->role,
                
                // 🆕 CAPACITÀ UNIFICATA
                'standard_daily_capacity' => $standardDailyCapacity,
                'extra_daily_capacity' => $extraDailyCapacity,
                'unified_capacity' => $unifiedCapacity,
                'daily_hours_capacity' => $unifiedCapacity, // Per compatibilità con vista esistente
                
                // 🆕 TARIFFE
                'standard_hourly_rate' => $standardHourlyRate,
                'extra_hourly_rate' => $extraHourlyRate,
                'hourly_rate' => $standardHourlyRate, // Per compatibilità
                
                // Dati attività
                'clients' => [],
                'total_hours_worked' => 0,
                'total_standard_hours_worked' => 0,
                'total_extra_hours_worked' => 0,
                
                // 🆕 ORE RIMANENTI UNIFICATE
                'remaining_standard_hours' => $standardDailyCapacity,
                'remaining_extra_hours' => $extraDailyCapacity,
                'unified_remaining_hours' => $unifiedCapacity,
                'remaining_hours' => $unifiedCapacity, // Per compatibilità
                'remaining_value' => 0
            ];
            
            // Ottieni le attività per questa risorsa
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
            
            // Raggruppa dati per cliente
            $clientsData = [];
            $totalStandardWorked = 0;
            $totalExtraWorked = 0;
            
            foreach ($activities as $activity) {
                $client = $activity->project->client;
                $project = $activity->project;
                
                // Calcola ore lavorate in questa data specifica
                $tasksForDay = $activity->tasks()
                    ->whereDate('updated_at', $date)
                    ->where('actual_minutes', '>', 0)
                    ->get();
                
                $activityStandardHours = 0;
                $activityExtraHours = 0;
                $activityTotalHours = 0;
                $activityTasks = [];
                
                foreach ($tasksForDay as $task) {
                    $taskHours = $task->actual_minutes / 60;
                    $activityTotalHours += $taskHours;
                    
                    // 🆕 DETERMINA SE SONO ORE STANDARD O EXTRA
                    // Logica: prima usa ore standard, poi extra
                    if ($activity->hours_type === 'extra' || 
                        ($totalStandardWorked + $taskHours) > $standardDailyCapacity) {
                        $activityExtraHours += $taskHours;
                        $totalExtraWorked += $taskHours;
                    } else {
                        $activityStandardHours += $taskHours;
                        $totalStandardWorked += $taskHours;
                    }
                    
                    $activityTasks[] = [
                        'id' => $task->id,
                        'name' => $task->name,
                        'hours' => $taskHours,
                        'hours_type' => $activity->hours_type ?? 'standard',
                        'value' => $taskHours * ($activity->hours_type === 'extra' ? $extraHourlyRate : $standardHourlyRate)
                    ];
                }
                
                if ($activityTotalHours > 0 || $tasksForDay->count() > 0) {
                    // Inizializza dati cliente se non esistono
                    if (!isset($clientsData[$client->id])) {
                        $clientsData[$client->id] = [
                            'id' => $client->id,
                            'name' => $client->name,
                            'projects' => [],
                            'total_hours' => 0,
                            'standard_hours' => 0,
                            'extra_hours' => 0,
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
                            'standard_hours' => 0,
                            'extra_hours' => 0,
                            'total_value' => 0
                        ];
                    }
                    
                    // 🆕 CALCOLO VALORE CON TARIFFE DIFFERENZIATE
                    $activityStandardValue = $activityStandardHours * $standardHourlyRate;
                    $activityExtraValue = $activityExtraHours * $extraHourlyRate;
                    $activityTotalValue = $activityStandardValue + $activityExtraValue;
                    
                    $clientsData[$client->id]['projects'][$project->id]['activities'][] = [
                        'id' => $activity->id,
                        'name' => $activity->name,
                        'hours' => $activityTotalHours,
                        'standard_hours' => $activityStandardHours,
                        'extra_hours' => $activityExtraHours,
                        'hours_type' => $activity->hours_type ?? 'standard',
                        'value' => $activityTotalValue,
                        'tasks' => $activityTasks
                    ];
                    
                    // Aggiorna totali progetto
                    $clientsData[$client->id]['projects'][$project->id]['total_hours'] += $activityTotalHours;
                    $clientsData[$client->id]['projects'][$project->id]['standard_hours'] += $activityStandardHours;
                    $clientsData[$client->id]['projects'][$project->id]['extra_hours'] += $activityExtraHours;
                    $clientsData[$client->id]['projects'][$project->id]['total_value'] += $activityTotalValue;
                    
                    // Aggiorna totali cliente
                    $clientsData[$client->id]['total_hours'] += $activityTotalHours;
                    $clientsData[$client->id]['standard_hours'] += $activityStandardHours;
                    $clientsData[$client->id]['extra_hours'] += $activityExtraHours;
                    $clientsData[$client->id]['total_value'] += $activityTotalValue;
                }
            }
            
            // 🆕 CALCOLA ORE RIMANENTI E VALORE
            $resourceData['total_hours_worked'] = $totalStandardWorked + $totalExtraWorked;
            $resourceData['total_standard_hours_worked'] = $totalStandardWorked;
            $resourceData['total_extra_hours_worked'] = $totalExtraWorked;
            
            $resourceData['remaining_standard_hours'] = max(0, $standardDailyCapacity - $totalStandardWorked);
            $resourceData['remaining_extra_hours'] = max(0, $extraDailyCapacity - $totalExtraWorked);
            $resourceData['unified_remaining_hours'] = $resourceData['remaining_standard_hours'] + $resourceData['remaining_extra_hours'];
            $resourceData['remaining_hours'] = $resourceData['unified_remaining_hours']; // Compatibilità
            
            // 🆕 CALCOLO VALORE RIMANENTE CON TARIFFE DIFFERENZIATE
            $remainingStandardValue = $resourceData['remaining_standard_hours'] * $standardHourlyRate;
            $remainingExtraValue = $resourceData['remaining_extra_hours'] * $extraHourlyRate;
            $resourceData['remaining_value'] = $remainingStandardValue + $remainingExtraValue;
            
            // Converti array clienti in lista
            $resourceData['clients'] = array_values($clientsData);
            
            // Converti progetti da associativo a numerico
            foreach ($resourceData['clients'] as &$clientData) {
                $clientData['projects'] = array_values($clientData['projects']);
            }
            
            $dailyData[] = $resourceData;
        }
        
        return $dailyData;
    }
    
    /**
     * Ottieni i dati budget e redistribuzioni per ogni cliente
     */
    private function getClientsBudgetData($date)
    {
        $clients = Client::all();
        $clientsData = [];
        
        foreach ($clients as $client) {
            // Calcola il budget utilizzato attraverso i progetti
            $budgetUsed = 0;
            foreach ($client->projects as $project) {
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
                    $hoursTransferredToday += $redistribution->hours;
                    $valueTransferredToday += $redistribution->total_value;
                } else {
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
     * 🆕 NUOVO: Redistribuzione ore unificata
     */
    public function redistributeUnifiedHours(Request $request)
    {
        $request->validate([
            'resource_id' => 'required|exists:resources,id',
            'client_id' => 'required|exists:clients,id',
            'total_hours' => 'required|numeric|min:0.1',
            'standard_hours' => 'required|numeric|min:0',
            'extra_hours' => 'required|numeric|min:0',
            'action' => 'required|in:transfer,return',
            'date' => 'required|date'
        ]);
        
        $resource = Resource::findOrFail($request->resource_id);
        $toClient = Client::findOrFail($request->client_id);
        $fromClient = $request->from_client_id ? Client::findOrFail($request->from_client_id) : null;
        
        $totalHours = $request->total_hours;
        $standardHours = $request->standard_hours;
        $extraHours = $request->extra_hours;
        
        // 🆕 CALCOLO VALORE CON TARIFFE DIFFERENZIATE
        $standardRate = $resource->selling_price ?? 50;
        $extraRate = $resource->extra_selling_price ?? ($standardRate * 1.2);
        
        $standardValue = $standardHours * $standardRate;
        $extraValue = $extraHours * $extraRate;
        $totalValue = $standardValue + $extraValue;
        
        try {
            DB::beginTransaction();
            
            // 🆕 CREA RECORD DI REDISTRIBUZIONE CON BREAKDOWN
            $redistribution = HoursRedistribution::create([
                'resource_id' => $resource->id,
                'from_client_id' => $fromClient ? $fromClient->id : null,
                'to_client_id' => $toClient->id,
                'user_id' => Auth::id(),
                'redistribution_date' => $request->date,
                'hours' => $totalHours,
                'standard_hours' => $standardHours,
                'extra_hours' => $extraHours,
                'hourly_rate' => $standardRate,
                'extra_hourly_rate' => $extraRate,
                'total_value' => $totalValue,
                'action_type' => $request->action,
                'notes' => $request->notes ?? ''
            ]);
            
            if ($request->action === 'return') {
                $toClient->budget += $totalValue;
                $toClient->save();
                
                $message = "Restituite {$totalHours}h ({$standardHours}h std + {$extraHours}h extra) = €{$totalValue} al budget di {$toClient->name}";
            } else {
                if ($fromClient) {
                    $fromClient->budget -= $totalValue;
                    $fromClient->save();
                }
                
                $toClient->budget += $totalValue;
                $toClient->save();
                
                $message = "Trasferite {$totalHours}h ({$standardHours}h std + {$extraHours}h extra) = €{$totalValue} " . 
                          ($fromClient ? "da {$fromClient->name} " : "") . 
                          "a {$toClient->name}";
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => $message,
                'redistribution_id' => $redistribution->id,
                'breakdown' => [
                    'total_hours' => $totalHours,
                    'standard_hours' => $standardHours,
                    'extra_hours' => $extraHours,
                    'total_value' => $totalValue,
                    'standard_value' => $standardValue,
                    'extra_value' => $extraValue
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Errore redistribuzione unificata: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Errore durante la redistribuzione: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Redistribuzione ore (metodo legacy per compatibilità)
     */
    public function redistributeHours(Request $request)
    {
        // Converte chiamata legacy in chiamata unificata
        $totalHours = $request->hours;
        $standardHours = min($totalHours, 5); // Assume max 5 ore standard
        $extraHours = max(0, $totalHours - $standardHours);
        
        $unifiedRequest = new Request([
            'resource_id' => $request->resource_id,
            'client_id' => $request->client_id,
            'from_client_id' => $request->from_client_id,
            'total_hours' => $totalHours,
            'standard_hours' => $standardHours,
            'extra_hours' => $extraHours,
            'action' => $request->action,
            'date' => $request->date,
            'notes' => $request->notes
        ]);
        
        return $this->redistributeUnifiedHours($unifiedRequest);
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
        
        $dailyHoursData = $this->getDailyHoursDataUnified($date, $clientId, $projectId);
        
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
        
        if ($redistribution->created_at->diffInHours(now()) > 24) {
            return response()->json([
                'success' => false,
                'message' => 'Non è possibile annullare redistribuzioni più vecchie di 24 ore'
            ], 400);
        }
        
        try {
            DB::beginTransaction();
            
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