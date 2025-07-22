<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB; // Aggiunto per il supporto transazioni
use Illuminate\Support\Facades\Auth; // Aggiunto per l'autenticazione
use Illuminate\Support\Facades\Log; // Aggiunto per il logging

class ClientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $clients = Client::withCount('projects')->get();
        return view('clients.index', compact('clients'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('clients.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'budget' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        Client::create($request->all());

        return redirect()->route('clients.index')
            ->with('success', 'Cliente creato con successo.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $client = Client::with([
            'projects.areas.activities.tasks' => function ($query) {
                $query->withTrashed();
            }
        ])->findOrFail($id);
        
        // Calcolo statistiche ore
        $totalEstimatedHours = 0;
        $totalActualHours = 0;
        $totalStandardEstimatedHours = 0;
        $totalExtraEstimatedHours = 0;
        $totalStandardActualHours = 0;
        $totalExtraActualHours = 0;
        
        foreach ($client->projects as $project) {
            foreach ($project->areas as $area) {
                foreach ($area->activities as $activity) {
                    foreach ($activity->tasks as $task) {
                        $totalEstimatedHours += $task->standard_estimated_hours + $task->extra_estimated_hours;
                        $totalActualHours += $task->standard_actual_hours + $task->extra_actual_hours;
                        
                        $totalStandardEstimatedHours += $task->standard_estimated_hours;
                        $totalExtraEstimatedHours += $task->extra_estimated_hours;
                        $totalStandardActualHours += $task->standard_actual_hours;
                        $totalExtraActualHours += $task->extra_actual_hours;
                    }
                }
            }
        }
        
        // Calcolo percentuali
        $standardEstimatedPercentage = $totalEstimatedHours > 0 ? 
            round(($totalStandardEstimatedHours / $totalEstimatedHours) * 100) : 0;
        $extraEstimatedPercentage = $totalEstimatedHours > 0 ? 
            round(($totalExtraEstimatedHours / $totalEstimatedHours) * 100) : 0;
        $standardActualPercentage = $totalActualHours > 0 ? 
            round(($totalStandardActualHours / $totalActualHours) * 100) : 0;
        $extraActualPercentage = $totalActualHours > 0 ? 
            round(($totalExtraActualHours / $totalActualHours) * 100) : 0;
        
        // Calcolo efficienza (ore stimate vs effettive)
        $standardEfficiency = $totalStandardEstimatedHours > 0 ?
            round(($totalStandardActualHours / $totalStandardEstimatedHours) * 100) : 0;
        $extraEfficiency = $totalExtraEstimatedHours > 0 ?
            round(($totalExtraActualHours / $totalExtraEstimatedHours) * 100) : 0;
        $totalEfficiency = $totalEstimatedHours > 0 ?
            round(($totalActualHours / $totalEstimatedHours) * 100) : 0;
            
        $hoursStats = [
            'totalEstimatedHours' => $totalEstimatedHours,
            'totalActualHours' => $totalActualHours,
            'standardEstimatedHours' => $totalStandardEstimatedHours,
            'extraEstimatedHours' => $totalExtraEstimatedHours,
            'standardActualHours' => $totalStandardActualHours,
            'extraActualHours' => $totalExtraActualHours,
            'standardEstimatedPercentage' => $standardEstimatedPercentage,
            'extraEstimatedPercentage' => $extraEstimatedPercentage,
            'standardActualPercentage' => $standardActualPercentage,
            'extraActualPercentage' => $extraActualPercentage,
            'standardEfficiency' => $standardEfficiency,
            'extraEfficiency' => $extraEfficiency,
            'totalEfficiency' => $totalEfficiency
        ];
        
        return view('clients.show', compact('client', 'hoursStats'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $client = Client::findOrFail($id);
        return view('clients.edit', compact('client'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'budget' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $client = Client::findOrFail($id);
        $client->update($request->all());

        return redirect()->route('clients.index')
            ->with('success', 'Cliente aggiornato con successo.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $client = Client::findOrFail($id);
        
        // Verifica se ci sono progetti associati
        if ($client->projects()->count() > 0) {
            return redirect()->route('clients.index')
                ->with('error', 'Impossibile eliminare il cliente. Ci sono progetti associati.');
        }
        
        $client->delete();
        
        return redirect()->route('clients.index')
            ->with('success', 'Cliente eliminato con successo.');
    }

    // ============================================================================
    // NUOVE FUNZIONI AGGIUNTE - GESTIONE CONSOLIDAMENTO TASKS
    // ============================================================================

    /**
     * Consolida un cliente creato da tasks
     * 
     * Questa funzione permette di consolidare un cliente che è stato creato
     * automaticamente dal sistema durante la creazione di task, trasformandolo
     * in un cliente ufficiale con budget definito.
     * 
     * @param Request $request - Contiene budget e note opzionali
     * @param string $id - ID del cliente da consolidare
     * @return \Illuminate\Http\RedirectResponse
     */
    public function consolidate(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'budget' => 'required|numeric|min:0',
            'notes' => 'nullable|string'
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        $client = Client::findOrFail($id);
        
        // Verifica che il cliente sia stato creato da tasks
        if (!$client->created_from_tasks) {
            return redirect()->route('clients.index')
                ->with('error', 'Questo cliente non è stato creato da tasks e non può essere consolidato.');
        }
        
        try {
            DB::beginTransaction();
            
            // Consolida il cliente utilizzando il metodo del modello
            $client->consolidate($request->budget, $request->notes);
            
            // Log dell'operazione per tracciabilità
            Log::info("Cliente consolidato", [
                'client_id' => $client->id,
                'client_name' => $client->name,
                'old_budget' => $client->getOriginal('budget'),
                'new_budget' => $request->budget,
                'user_id' => Auth::id(),
                'timestamp' => now()->toDateTimeString()
            ]);
            
            DB::commit();
            
            return redirect()->route('clients.index')
                ->with('success', 'Cliente consolidato con successo. Ora è un cliente ufficiale del sistema.');
                
        } catch (\Exception $e) {
            DB::rollback();
            
            // Log dell'errore per debugging
            Log::error("Errore durante il consolidamento del cliente", [
                'client_id' => $client->id,
                'client_name' => $client->name,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'timestamp' => now()->toDateTimeString()
            ]);
            
            return redirect()->route('clients.index')
                ->with('error', 'Errore durante il consolidamento del cliente: ' . $e->getMessage());
        }
    }

    /**
     * API per ottenere statistiche sui clienti creati da tasks
     * 
     * Questa funzione fornisce statistiche utili per il dashboard amministrativo
     * riguardo ai clienti creati automaticamente dal sistema e quelli consolidati.
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTasksCreatedStats()
    {
        try {
            // Conta i clienti creati da tasks (non ancora consolidati)
            $totalTasksCreated = Client::createdFromTasks()->count();
            
            // Conta i clienti consolidati (creati normalmente o consolidati)
            $totalConsolidated = Client::createdNormally()->count();
            
            // I clienti in attesa di consolidamento sono quelli creati da tasks
            $pendingConsolidation = $totalTasksCreated;
            
            // Clienti creati da tasks negli ultimi 7 giorni
            $recentTasksCreated = Client::createdFromTasks()
                ->where('tasks_created_at', '>=', now()->subDays(7))
                ->count();
            
            // Statistiche aggiuntive per il dashboard
            $totalClients = $totalTasksCreated + $totalConsolidated;
            $consolidationRate = $totalClients > 0 ? 
                round(($totalConsolidated / $totalClients) * 100, 2) : 0;
            
            return response()->json([
                'success' => true,
                'data' => [
                    'total_tasks_created' => $totalTasksCreated,
                    'total_consolidated' => $totalConsolidated,
                    'pending_consolidation' => $pendingConsolidation,
                    'recent_tasks_created' => $recentTasksCreated,
                    'total_clients' => $totalClients,
                    'consolidation_rate' => $consolidationRate
                ],
                'timestamp' => now()->toDateTimeString()
            ]);
            
        } catch (\Exception $e) {
            Log::error("Errore nel recupero delle statistiche clienti", [
                'error' => $e->getMessage(),
                'timestamp' => now()->toDateTimeString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Errore nel recupero delle statistiche',
                'timestamp' => now()->toDateTimeString()
            ], 500);
        }
    }

    /**
     * Mostra la pagina di consolidamento per un cliente creato da tasks
     * 
     * @param string $id - ID del cliente
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function showConsolidation(string $id)
    {
        $client = Client::findOrFail($id);
        
        // Verifica che il cliente sia stato creato da tasks
        if (!$client->created_from_tasks) {
            return redirect()->route('clients.index')
                ->with('error', 'Questo cliente non necessita di consolidamento.');
        }
        
        // Calcola statistiche del cliente per aiutare nella decisione del budget
        $totalEstimatedBudget = $client->projects()
            ->with(['areas.activities.tasks'])
            ->get()
            ->sum(function ($project) {
                return $project->areas->sum(function ($area) {
                    return $area->activities->sum(function ($activity) {
                        return $activity->tasks->sum(function ($task) {
                            return ($task->standard_estimated_hours + $task->extra_estimated_hours) * 
                                   ($task->hourly_rate ?? 50); // Rate di default se non specificato
                        });
                    });
                });
            });
        
        $suggestedBudget = round($totalEstimatedBudget * 1.2, 2); // +20% di buffer
        
        return view('clients.consolidate', compact('client', 'suggestedBudget'));
    }

    /**
     * Ottiene la lista dei clienti in attesa di consolidamento
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPendingConsolidation()
    {
        try {
            $pendingClients = Client::createdFromTasks()
                ->withCount('projects')
                ->orderBy('tasks_created_at', 'desc')
                ->get()
                ->map(function ($client) {
                    return [
                        'id' => $client->id,
                        'name' => $client->name,
                        'projects_count' => $client->projects_count,
                        'created_at' => $client->tasks_created_at?->format('d/m/Y H:i'),
                        'days_pending' => $client->tasks_created_at ? 
                            $client->tasks_created_at->diffInDays(now()) : 0
                    ];
                });
            
            return response()->json([
                'success' => true,
                'data' => $pendingClients,
                'count' => $pendingClients->count()
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Errore nel recupero dei clienti in attesa'
            ], 500);
        }
    }
}