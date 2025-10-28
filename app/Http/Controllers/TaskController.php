<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Activity;
use App\Models\Project;
use App\Models\Client;
use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class TaskController extends Controller
{
    /**
     * Constructor - Applica middleware di autenticazione
     */
    public function __construct()
    {
        $this->middleware('auth');
        // Il middleware task.access controlla che gli utenti risorsa 
        // possano accedere solo ai propri task
        $this->middleware('task.access')->only([
            'show', 'edit', 'update', 'destroy', 
            'updateStatus', 'complete', 'start', 'updateTaskTimer'
        ]);
    }
    
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();
        
        // Se l'utente è un amministratore, mostra tutti i task
        if ($user->is_admin) {
            $tasks = Task::with(['activity.project', 'activity.resource'])->get();
            $activities = Activity::with('project')->get();
        } else {
            // Se l'utente è una risorsa, mostra solo i task relativi alle attività della risorsa
            $resourceId = $user->resource_id;
            
            // Ottieni i task dalle attività in cui l'utente è la risorsa principale
            $taskQuery = Task::whereHas('activity', function ($query) use ($resourceId) {
                $query->where('resource_id', $resourceId);
            });
            
            // Aggiungi anche i task dalle attività in cui l'utente è una delle risorse multiple
            $taskQuery->orWhereHas('activity', function ($query) use ($resourceId) {
                $query->where('has_multiple_resources', true)
                      ->whereHas('resources', function ($q) use ($resourceId) {
                          $q->where('resources.id', $resourceId);
                      });
            });
            
            $tasks = $taskQuery->with(['activity.project', 'activity.resource'])->get();
            
            // Filtra le attività visibili per l'utente
            $activities = Activity::where(function ($query) use ($resourceId) {
                $query->where('resource_id', $resourceId)
                      ->orWhere(function ($q) use ($resourceId) {
                          $q->where('has_multiple_resources', true)
                            ->whereHas('resources', function ($innerQ) use ($resourceId) {
                                $innerQ->where('resources.id', $resourceId);
                            });
                      });
            })->with('project')->get();
        }
        
        $projects = Project::all();
        $clients = Client::all();
        $resources = Resource::where('is_active', true)->get();
        
        // Calcola statistiche totali
        $totalStats = [
            'pending' => $tasks->where('status', 'pending')->count(),
            'in_progress' => $tasks->where('status', 'in_progress')->count(),
            'completed' => $tasks->where('status', 'completed')->count(),
            'total' => $tasks->count(),
            'estimatedMinutes' => $tasks->sum('estimated_minutes'),
            'actualMinutes' => $tasks->sum('actual_minutes'),
            'balance' => $tasks->sum('estimated_minutes') - $tasks->sum('actual_minutes'),
            'bonus' => 0
        ];
        
        return view('tasks.index', compact('tasks', 'activities', 'projects', 'clients', 'resources', 'totalStats'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $user = Auth::user();
        
        // Carica tutti i clienti per la select
        $clients = Client::orderBy('name')->get();
        
        // Se l'utente è un amministratore, mostra tutte le attività
        if ($user->is_admin) {
            $activities = Activity::with('project')->get();
            $resources = Resource::where('is_active', true)->get();
        } else {
            // Se l'utente è una risorsa, mostra solo le attività della risorsa
            $resourceId = $user->resource_id;
            
            $activities = Activity::where(function ($query) use ($resourceId) {
                $query->where('resource_id', $resourceId)
                      ->orWhere(function ($q) use ($resourceId) {
                          $q->where('has_multiple_resources', true)
                            ->whereHas('resources', function ($innerQ) use ($resourceId) {
                                $innerQ->where('resources.id', $resourceId);
                            });
                      });
            })
            ->with('project')
            ->get();
            
            // Se l'utente è una risorsa, pre-seleziona la propria risorsa
            $resources = Resource::where('id', $resourceId)->get();
        }
        
        // Controlla se c'è un activity_id nella query string
        $selectedActivityId = $request->query('activity_id');
        $selectedActivity = null;
        
        if ($selectedActivityId) {
            // Verifica che l'utente risorsa abbia accesso all'attività selezionata
            $selectedActivity = Activity::find($selectedActivityId);
            
            if (!$user->is_admin && $selectedActivity) {
                $hasAccess = false;
                
                if ($selectedActivity->resource_id == $user->resource_id) {
                    $hasAccess = true;
                } elseif ($selectedActivity->has_multiple_resources) {
                    $hasAccess = $selectedActivity->resources()->where('resources.id', $user->resource_id)->exists();
                }
                
                if (!$hasAccess) {
                    $selectedActivity = null;
                }
            }
            
            // Se l'attività ha risorse, mostrare quelle risorse come opzioni
            if ($selectedActivity) {
                if ($selectedActivity->has_multiple_resources) {
                    $resources = $selectedActivity->resources;
                } elseif ($selectedActivity->resource_id) {
                    $resources = Resource::where('id', $selectedActivity->resource_id)->get();
                }
            }
        }
        
        return view('tasks.create', compact('activities', 'selectedActivity', 'resources', 'clients'));
    }

    /**
     * Store a newly created resource in storage.
     * Metodo aggiornato con supporto per creazione cliente/progetto al volo
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'activity_id' => 'required|exists:activities,id',
            'resource_id' => 'nullable|exists:resources,id',
            'estimated_minutes' => 'required|integer|min:1',
            'due_date' => 'nullable|date',
            'status' => 'nullable|in:pending,in_progress,completed',
            'order' => 'nullable|integer|min:0',
            // Campi per creazione al volo
            'new_client_name' => 'nullable|string|max:255',
            'new_client_budget' => 'nullable|numeric|min:0',
            'new_client_notes' => 'nullable|string',
            'new_project_name' => 'nullable|string|max:255',
            'new_project_description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $user = Auth::user();
        
        try {
            DB::beginTransaction();

            // Gestione creazione cliente al volo se necessario
            if ($request->filled('new_client_name')) {
                $client = Client::createFromTasks(
                    $request->new_client_name,
                    $request->new_client_budget ?? 10000
                );
                
                if ($request->new_client_notes) {
                    $client->update(['notes' => $request->new_client_notes]);
                }
                
                $clientId = $client->id;
            } else {
                // Ottieni client_id dall'attività selezionata
                $activity = Activity::with('project')->findOrFail($request->activity_id);
                $clientId = $activity->project->client_id;
            }

            // Gestione creazione progetto al volo se necessario
            if ($request->filled('new_project_name')) {
                $project = Project::createFromTasks(
                    $request->new_project_name,
                    $clientId,
                    $request->new_project_description
                );
                
                // Se abbiamo creato un nuovo progetto, dobbiamo creare anche una nuova attività
                // Per ora utilizziamo l'attività esistente, ma in futuro si potrebbe migliorare
                $projectId = $project->id;
            }

            $activity = Activity::findOrFail($request->activity_id);
            
            // Verifica che l'utente risorsa abbia accesso all'attività
            if (!$user->is_admin) {
                $hasAccess = false;
                
                if ($activity->resource_id == $user->resource_id) {
                    $hasAccess = true;
                } elseif ($activity->has_multiple_resources) {
                    $hasAccess = $activity->resources()->where('resources.id', $user->resource_id)->exists();
                }
                
                if (!$hasAccess) {
                    return redirect()->back()
                        ->with('error', 'Non hai accesso a questa attività.')
                        ->withInput();
                }
            }

            // Se non è stata specificata una risorsa ma l'utente è una risorsa, usa l'utente corrente
            if (!$request->has('resource_id') && $user->resource_id) {
                $request->merge(['resource_id' => $user->resource_id]);
            }
            
            // Se non è stata specificata una risorsa e l'attività ha una singola risorsa, usa quella
            if (!$request->has('resource_id') && $activity->resource_id && !$activity->has_multiple_resources) {
                $request->merge(['resource_id' => $activity->resource_id]);
            }

            $task = new Task();
            $task->fill($request->all());
            $task->status = $request->status ?? 'pending';
            $task->actual_minutes = 0; // Inizializza i minuti effettivi a 0
            
            // Imposta l'ordine
            if (!$request->has('order') || $request->order === null) {
                $lastOrderTask = Task::where('activity_id', $request->activity_id)
                    ->orderBy('order', 'desc')
                    ->first();
                
                $task->order = $lastOrderTask ? $lastOrderTask->order + 1 : 1;
            }
            
            $task->save();
            
            // Aggiorna lo stato dell'attività
            $task->updateParentActivity();

            DB::commit();

            // Log dell'operazione se sono stati creati nuovi elementi
            if ($request->filled('new_client_name') || $request->filled('new_project_name')) {
                \Log::info("Task creato con elementi al volo", [
                    'task_id' => $task->id,
                    'task_name' => $task->name,
                    'new_client' => $request->filled('new_client_name') ? $request->new_client_name : null,
                    'new_project' => $request->filled('new_project_name') ? $request->new_project_name : null,
                    'user_id' => Auth::id()
                ]);
            }

            $successMessage = 'Task creato con successo.';
            if ($request->filled('new_client_name')) {
                $successMessage .= ' Nuovo cliente creato e da consolidare.';
            }
            if ($request->filled('new_project_name')) {
                $successMessage .= ' Nuovo progetto creato e da consolidare.';
            }

            // Se la richiesta è JSON (dal calendario), restituisci JSON
            if ($request->expectsJson() || $request->header('Accept') === 'application/json') {
                return response()->json([
                    'success' => true,
                    'message' => $successMessage,
                    'task' => $task
                ]);
            }
            
            // Altrimenti redirect normale
            return redirect()->route('activities.show', $activity->id)
                ->with('success', $successMessage);

        } catch (\Exception $e) {
            DB::rollback();
            
            // Se la richiesta è JSON, restituisci errore JSON
            if ($request->expectsJson() || $request->header('Accept') === 'application/json') {
                return response()->json([
                    'success' => false,
                    'message' => 'Errore nella creazione del task: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()
                ->with('error', 'Errore nella creazione del task: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $task = Task::with(['activity.project', 'activity.resource'])->findOrFail($id);
        
        return view('tasks.show', compact('task'));
    }

    /**
     * Show the form for editing the specified resource.
     * Metodo aggiornato per includere clienti nella vista
     */
    public function edit(string $id)
    {
        $user = Auth::user();
        $task = Task::with(['resource', 'activity.project.client'])->findOrFail($id);
        
        // Carica tutti i clienti per eventuali modifiche
        $clients = Client::orderBy('name')->get();
        
        // Se l'utente è un amministratore, mostra tutte le attività
        if ($user->is_admin) {
            $activities = Activity::with('project.client')->get();
            $resources = Resource::where('is_active', true)->get();
        } else {
            // Se l'utente è una risorsa, mostra solo le attività della risorsa
            $resourceId = $user->resource_id;
            
            $activities = Activity::where(function ($query) use ($resourceId) {
                $query->where('resource_id', $resourceId)
                      ->orWhere(function ($q) use ($resourceId) {
                          $q->where('has_multiple_resources', true)
                            ->whereHas('resources', function ($innerQ) use ($resourceId) {
                                $innerQ->where('resources.id', $resourceId);
                            });
                      });
            })
            ->with('project.client')
            ->get();
            
            // Se l'utente è una risorsa e non è admin, può vedere solo se stesso e risorse già assegnate al task
            if ($task->resource_id && $task->resource_id != $resourceId) {
                $resources = Resource::whereIn('id', [$resourceId, $task->resource_id])->get();
            } else {
                $resources = Resource::where('id', $resourceId)->get();
            }
        }
        
        // Se il task ha un'attività, aggiungi le risorse dell'attività come opzioni
        if ($task->activity) {
            if ($task->activity->has_multiple_resources) {
                // Se l'admin, usa tutte le risorse attive, altrimenti filtra
                if ($user->is_admin) {
                    $activityResources = $task->activity->resources;
                    // Merge le risorse dell'attività con tutte le risorse attive
                    $resources = $resources->merge($activityResources)->unique('id');
                } else {
                    // Per utenti normali, mostra solo le risorse dell'attività a cui hanno accesso
                    $activityResources = $task->activity->resources()
                        ->where('resources.id', $user->resource_id)
                        ->get();
                    $resources = $resources->merge($activityResources)->unique('id');
                }
            } elseif ($task->activity->resource_id) {
                // Aggiungi la risorsa dell'attività come opzione
                $activityResource = Resource::find($task->activity->resource_id);
                if ($activityResource) {
                    $resources = $resources->merge([$activityResource])->unique('id');
                }
            }
        }
        
        return view('tasks.edit', compact('task', 'activities', 'resources', 'clients'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = Auth::user();
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'activity_id' => 'required|exists:activities,id',
            'resource_id' => 'nullable|exists:resources,id',
            'estimated_minutes' => 'required|integer|min:1',
            'due_date' => 'nullable|date',
            'status' => 'nullable|in:pending,in_progress,completed',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $task = Task::findOrFail($id);
        
        // Verifica che l'utente risorsa abbia accesso all'attività selezionata
        if (!$user->is_admin) {
            $activity = Activity::find($request->activity_id);
            
            if ($activity) {
                $hasAccess = false;
                
                if ($activity->resource_id == $user->resource_id) {
                    $hasAccess = true;
                } elseif ($activity->has_multiple_resources) {
                    $hasAccess = $activity->resources()->where('resources.id', $user->resource_id)->exists();
                }
                
                if (!$hasAccess) {
                    return redirect()->back()
                        ->withErrors(['activity_id' => 'Non hai accesso a questa attività'])
                        ->withInput();
                }
            }
        }

        $task->update([
            'name' => $request->name,
            'description' => $request->description,
            'activity_id' => $request->activity_id,
            'resource_id' => $request->resource_id,
            'estimated_minutes' => $request->estimated_minutes,
            'due_date' => $request->due_date,
            'status' => $request->status ?? $task->status,
        ]);

        return redirect()->route('tasks.show', $task->id)
            ->with('success', 'Task aggiornato con successo.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $task = Task::findOrFail($id);
        $activityId = $task->activity_id;
        $task->delete();

        return redirect()->route('tasks.index')
            ->with('success', 'Task eliminato con successo.');
    }
    
    /**
     * Time tracking view.
     */
    public function timeTracking()
    {
        $user = Auth::user();
        
        // Se l'utente è un amministratore, mostra tutti i task
        if ($user->is_admin) {
            $tasks = Task::with(['activity.project', 'activity.resource'])
                ->where('status', '!=', 'completed')
                ->orderBy('priority', 'desc')
                ->orderBy('due_date')
                ->get();
        } else {
            // Se l'utente è una risorsa, mostra solo i task relativi alle attività della risorsa
            $resourceId = $user->resource_id;
            
            // Ottieni i task dalle attività in cui l'utente è la risorsa principale
            $taskQuery = Task::whereHas('activity', function ($query) use ($resourceId) {
                $query->where('resource_id', $resourceId);
            });
            
            // Aggiungi anche i task dalle attività in cui l'utente è una delle risorse multiple
            $taskQuery->orWhereHas('activity', function ($query) use ($resourceId) {
                $query->where('has_multiple_resources', true)
                      ->whereHas('resources', function ($q) use ($resourceId) {
                          $q->where('resources.id', $resourceId);
                      });
            });
            
            $tasks = $taskQuery->with(['activity.project', 'activity.resource'])
                ->where('status', '!=', 'completed')
                ->orderBy('priority', 'desc')
                ->orderBy('due_date')
                ->get();
        }
        
        return view('tasks.timetracking', compact('tasks'));
    }
    
    /**
     * Update task actual minutes from timer.
     */
    public function updateTaskTimer(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'actual_minutes' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $task = Task::findOrFail($id);
        $task->actual_minutes = $request->actual_minutes;
        $task->save();
        
        // Aggiorna i minuti effettivi dell'attività
        $task->updateParentActivity();
        
        return response()->json([
            'success' => true,
            'task' => $task,
            'activity_actual_minutes' => $task->activity->actual_minutes
        ]);
    }
    
    /**
     * Get tasks by activity for AJAX requests.
     */
    public function byActivity(string $activityId)
    {
        $user = Auth::user();
        
        // Verifica che l'utente risorsa abbia accesso all'attività
        if (!$user->is_admin) {
            $activity = Activity::find($activityId);
            
            if ($activity) {
                $hasAccess = false;
                
                if ($activity->resource_id == $user->resource_id) {
                    $hasAccess = true;
                } elseif ($activity->has_multiple_resources) {
                    $hasAccess = $activity->resources()->where('resources.id', $user->resource_id)->exists();
                }
                
                if (!$hasAccess) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Non hai accesso a questa attività'
                    ], 403);
                }
            }
        }
        
        $tasks = Task::where('activity_id', $activityId)
            ->orderBy('order')
            ->get();
        
        return response()->json([
            'success' => true,
            'tasks' => $tasks
        ]);
    }
    
    /**
     * Update task status.
     */
    public function updateStatus(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,in_progress,completed',
            'actual_minutes' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }
            
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $task = Task::findOrFail($id);
        $task->status = $request->status;
        
        // Se il task viene completato, aggiorna i minuti effettivi se forniti
        if ($request->status == 'completed' && $request->has('actual_minutes')) {
            $task->actual_minutes = $request->actual_minutes;
        }
        
        $task->save();
        
        // Aggiorna lo stato e i minuti effettivi dell'attività
        $task->updateParentActivity();
        
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'task' => $task,
                'activity_status' => $task->activity->status,
                'activity_actual_minutes' => $task->activity->actual_minutes
            ]);
        }
        
        return redirect()->route('tasks.show', $task->id)
            ->with('success', 'Stato del task aggiornato con successo.');
    }
    
    /**
     * Reorder tasks.
     */
    public function reorder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tasks' => 'required|array',
            'tasks.*' => 'exists:tasks,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        
        foreach ($request->tasks as $index => $taskId) {
            $task = Task::find($taskId);
            
            if ($task) {
                // Verifica che l'utente risorsa abbia accesso al task
                if (!$user->is_admin) {
                    $activity = $task->activity;
                    
                    if ($activity) {
                        $hasAccess = false;
                        
                        if ($activity->resource_id == $user->resource_id) {
                            $hasAccess = true;
                        } elseif ($activity->has_multiple_resources) {
                            $hasAccess = $activity->resources()->where('resources.id', $user->resource_id)->exists();
                        }
                        
                        if (!$hasAccess) {
                            continue; // Salta questo task se l'utente non ha accesso
                        }
                    }
                }
                
                $task->order = $index + 1;
                $task->save();
            }
        }
        
        return response()->json([
            'success' => true
        ]);
    }
    
    /**
     * Complete a task.
     */
    public function complete(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'actual_minutes' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $task = Task::findOrFail($id);
        $task->status = 'completed';
        $task->actual_minutes = $request->actual_minutes;
        $task->save();
        
        // Aggiorna lo stato e i minuti effettivi dell'attività
        $task->updateParentActivity();
        
        return redirect()->route('tasks.show', $task->id)
            ->with('success', 'Task completato con successo.');
    }
    
    /**
     * Start a task.
     */
    public function start(string $id)
    {
        $task = Task::findOrFail($id);
        $task->status = 'in_progress';
        $task->save();
        
        // Aggiorna lo stato dell'attività
        $task->activity->updateStatusFromTasks();
        
        return redirect()->route('tasks.show', $task->id)
            ->with('success', 'Task avviato con successo.');
    }

    // ======= NUOVE FUNZIONI AGGIUNTE PER CREAZIONE AL VOLO =======

    /**
     * API per ottenere i tasks di un progetto (per riassegnazione)
     */
    public function getProjectTasks($projectId)
    {
        try {
            $project = Project::findOrFail($projectId);
            
            $tasks = Task::whereHas('activity', function($query) use ($projectId) {
                $query->where('project_id', $projectId);
            })
            ->with(['activity:id,name'])
            ->get(['id', 'name', 'activity_id', 'status', 'estimated_minutes', 'actual_minutes']);

            $formattedTasks = $tasks->map(function($task) {
                return [
                    'id' => $task->id,
                    'name' => $task->name,
                    'activity_name' => $task->activity->name ?? 'N/D',
                    'status' => $task->status,
                    'estimated_minutes' => $task->estimated_minutes,
                    'actual_minutes' => $task->actual_minutes,
                ];
            });

            return response()->json([
                'success' => true,
                'tasks' => $formattedTasks
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore nel caricamento dei tasks'
            ], 500);
        }
    }

    /**
     * API per creare un cliente al volo
     */
    public function createClientFromTasks(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:clients,name',
            'budget' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dati non validi',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $client = Client::createFromTasks(
                $request->name,
                $request->budget ?? 10000
            );

            if ($request->notes) {
                $client->update(['notes' => $request->notes]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Cliente creato con successo',
                'client' => [
                    'id' => $client->id,
                    'name' => $client->name,
                    'budget' => $client->budget,
                    'created_from_tasks' => $client->created_from_tasks
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore nella creazione del cliente: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API per creare un progetto al volo
     */
    public function createProjectFromTasks(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'client_id' => 'required|exists:clients,id',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dati non validi',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Verifica che non esista già un progetto con lo stesso nome per lo stesso cliente
            $existingProject = Project::where('client_id', $request->client_id)
                                    ->where('name', $request->name)
                                    ->first();

            if ($existingProject) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esiste già un progetto con questo nome per il cliente selezionato'
                ], 422);
            }

            $project = Project::createFromTasks(
                $request->name,
                $request->client_id,
                $request->description
            );

            return response()->json([
                'success' => true,
                'message' => 'Progetto creato con successo',
                'project' => [
                    'id' => $project->id,
                    'name' => $project->name,
                    'description' => $project->description,
                    'client_id' => $project->client_id,
                    'created_from_tasks' => $project->created_from_tasks
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore nella creazione del progetto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API per ottenere progetti per cliente
     */
    public function getProjectsByClient($clientId)
    {
        try {
            $projects = Project::where('client_id', $clientId)
                             ->orderBy('name')
                             ->get(['id', 'name', 'created_from_tasks']);

            return response()->json([
                'success' => true,
                'projects' => $projects
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore nel caricamento progetti'
            ], 500);
        }
    }

    /**
     * API per ottenere attività per progetto
     */
    public function getActivitiesByProject($projectId)
    {
        try {
            $activities = Activity::where('project_id', $projectId)
                                ->orderBy('name')
                                ->get(['id', 'name']);

            return response()->json([
                'success' => true,
                'activities' => $activities
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore nel caricamento attività'
            ], 500);
        }
    }

    /**
     * Show the time management dashboard for tasks.
     */
    public function timeManagement()
    {
        $user = Auth::user();
        
        // Se l'utente è un amministratore, mostra tutti i task
        if ($user->is_admin) {
            $tasks = Task::with(['activity.project.client', 'activity.resource'])->get();
        } else {
            // Se l'utente è una risorsa, mostra solo i task relativi alle attività della risorsa
            $resourceId = $user->resource_id;
            
            // Ottieni i task dalle attività in cui l'utente è la risorsa principale
            $taskQuery = Task::whereHas('activity', function ($query) use ($resourceId) {
                $query->where('resource_id', $resourceId);
            });
            
            // Aggiungi anche i task dalle attività in cui l'utente è una delle risorse multiple
            $taskQuery->orWhereHas('activity', function ($query) use ($resourceId) {
                $query->where('has_multiple_resources', true)
                      ->whereHas('resources', function ($q) use ($resourceId) {
                          $q->where('resources.id', $resourceId);
                      });
            });
            
            $tasks = $taskQuery->with(['activity.project.client', 'activity.resource'])->get();
        }
        
        $projects = Project::all();
        $clients = Client::all();
        $resources = Resource::where('is_active', true)->get();
        
        // Calcola statistiche totali
        $totalStats = [
            'estimatedMinutes' => 0,
            'actualMinutes' => 0,
            'balance' => 0,
            'bonus' => 0
        ];
        
        foreach ($tasks as $task) {
            $totalStats['estimatedMinutes'] += $task->estimated_minutes;
            $totalStats['actualMinutes'] += $task->actual_minutes;
            
            $balance = $task->estimated_minutes - $task->actual_minutes;
            $totalStats['balance'] += $balance;
            
            // Calcolo del bonus
            if ($balance >= 0 && $task->actual_minutes > 0) {
                $hourlyRate = $this->getResourceHourlyRate($task);
                $bonus = ($task->actual_minutes / 60) * $hourlyRate * 0.05;
                $totalStats['bonus'] += $bonus;
            }
        }
        
        return view('tasks.time-management', compact('tasks', 'projects', 'clients', 'resources', 'totalStats'));
    }

    /**
     * Calcola la tariffa oraria per una risorsa in base al task
     */
    private function getResourceHourlyRate($task)
    {
        // Se il task ha una risorsa diretta
        if ($task->resource_id) {
            $resource = Resource::find($task->resource_id);
            if ($resource) {
                return $resource->selling_price ?? ($resource->cost_price * 1.2);
            }
        }
        
        // Se il task appartiene ad un'attività con risorsa
        if ($task->activity && $task->activity->resource_id) {
            $resource = $task->activity->resource;
            if ($resource) {
                return $resource->selling_price ?? ($resource->cost_price * 1.2);
            }
        }
        
        // Fallback: usa una tariffa media del progetto o standard
        $project = $task->activity->project ?? null;
        if ($project && $project->hourly_rate) {
            return $project->hourly_rate;
        }
        
        // Tariffa di default
        return 50;
    }

    /**
     * Esporta i dati dei task per report.
     */
    public function exportTimeData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'project_id' => 'nullable|exists:projects,id',
            'client_id' => 'nullable|exists:clients,id',
            'resource_id' => 'nullable|exists:resources,id',
            'format' => 'required|in:csv,excel,pdf'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $user = Auth::user();
        
        // Query di base
        $query = Task::with(['activity.project.client', 'activity.resource']);

        // Applica filtri in base al tipo di utente
        if (!$user->is_admin) {
            $resourceId = $user->resource_id;
            
            $query->where(function($q) use ($resourceId) {
                // Task dove l'utente è la risorsa principale dell'attività
                $q->whereHas('activity', function($innerQ) use ($resourceId) {
                    $innerQ->where('resource_id', $resourceId);
                });
                
                // Task dove l'utente è una delle risorse multiple dell'attività
                $q->orWhereHas('activity', function($innerQ) use ($resourceId) {
                    $innerQ->where('has_multiple_resources', true)
                          ->whereHas('resources', function($resourceQ) use ($resourceId) {
                              $resourceQ->where('resources.id', $resourceId);
                          });
                });
            });
        }

        // Applica filtri aggiuntivi
        if ($request->has('project_id') && $request->project_id) {
            $query->whereHas('activity', function($q) use ($request) {
                $q->where('project_id', $request->project_id);
            });
        }

        if ($request->has('client_id') && $request->client_id) {
            $query->whereHas('activity.project', function($q) use ($request) {
                $q->where('client_id', $request->client_id);
            });
        }

        if ($request->has('resource_id') && $request->resource_id) {
            $query->whereHas('activity', function($q) use ($request) {
                $q->where('resource_id', $request->resource_id);
            });
        }

        $tasks = $query->get();

        // Logica di esportazione a seconda del formato richiesto
        if ($request->format == 'csv') {
            return $this->generateCsvExport($tasks);
        } elseif ($request->format == 'excel') {
            return $this->generateExcelExport($tasks);
        } else { // pdf
            return $this->generatePdfExport($tasks);
        }
    }

    /**
     * Genera un'esportazione CSV dei dati di tempo.
     */
    private function generateCsvExport($tasks)
    {
        $filename = 'task_time_report_' . date('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        $callback = function() use ($tasks) {
            $file = fopen('php://output', 'w');
            
            // Header CSV
            fputcsv($file, [
                'Cliente',
                'Progetto', 
                'Attività',
                'Task',
                'Stato',
                'Minuti Stimati',
                'Minuti Effettivi',
                'Differenza',
                'Risorsa',
                'Data Scadenza'
            ]);

            foreach ($tasks as $task) {
                $balance = $task->estimated_minutes - $task->actual_minutes;
                
                fputcsv($file, [
                    $task->activity->project->client->name ?? 'N/D',
                    $task->activity->project->name ?? 'N/D',
                    $task->activity->name ?? 'N/D',
                    $task->name,
                    ucfirst($task->status),
                    $task->estimated_minutes,
                    $task->actual_minutes,
                    $balance,
                    $task->activity->resource->name ?? 'N/D',
                    $task->due_date ? $task->due_date->format('d/m/Y') : 'N/D'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Genera un'esportazione Excel dei dati di tempo.
     */
    private function generateExcelExport($tasks)
    {
        // Nota: questa implementazione richiede un pacchetto Excel come
        // maatwebsite/excel installato nel progetto
        
        // Esempio di implementazione:
        /* 
        return Excel::download(new TasksExport($tasks), 'task_time_report.xlsx');
        */
        
        // Per ora, reindirizza a CSV come fallback
        return $this->generateCsvExport($tasks);
    }

    /**
     * Genera un'esportazione PDF dei dati di tempo.
     */
    private function generatePdfExport($tasks)
    {
        // Nota: questa implementazione richiede un pacchetto PDF come
        // barryvdh/laravel-dompdf installato nel progetto
        
        // Esempio di implementazione:
        /*
        $data = [
            'tasks' => $tasks,
            'title' => 'Report dei tempi dei task',
            'date' => now()->format('d/m/Y')
        ];
        
        $pdf = PDF::loadView('tasks.reports.time-pdf', $data);
        return $pdf->download('task_time_report.pdf');
        */
        
        // Per ora, reindirizza a CSV come fallback
        return $this->generateCsvExport($tasks);
    }

    /**
     * Crea una nuova attività al volo (da calendario o task)
     */
    public function createActivity(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'project_id' => 'required|exists:projects,id',
            'estimated_minutes' => 'required|integer|min:1',
            'hours_type' => 'required|in:standard,extra'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dati non validi',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $activity = new \App\Models\Activity();
            $activity->name = $request->name;
            $activity->project_id = $request->project_id;
            $activity->estimated_minutes = $request->estimated_minutes;
            $activity->actual_minutes = 0;
            $activity->hours_type = $request->hours_type;
            $activity->status = 'pending';
            $activity->estimated_cost = 0; // AGGIUNTO!
            $activity->actual_cost = 0; // AGGIUNTO!
            
            // Se l'utente è una risorsa, assegna automaticamente
            if (Auth::user()->resource_id) {
                $activity->resource_id = Auth::user()->resource_id;
            }
            
            $activity->save();

            return response()->json([
                'success' => true,
                'message' => 'Attività creata con successo',
                'activity' => [
                    'id' => $activity->id,
                    'name' => $activity->name
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore nella creazione dell\'attività: ' . $e->getMessage()
            ], 500);
        }
    }
}