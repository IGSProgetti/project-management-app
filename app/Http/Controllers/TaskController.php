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
    
    return view('tasks.create', compact('activities', 'selectedActivity', 'resources'));
}

/**
 * Store a newly created resource in storage.
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
    ]);

    if ($validator->fails()) {
        return redirect()->back()
            ->withErrors($validator)
            ->withInput();
    }

    $user = Auth::user();
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

    return redirect()->route('activities.show', $activity->id)
        ->with('success', 'Task creato con successo.');
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
     * /**
 * Show the form for editing the specified resource.
 */
public function edit(string $id)
{
    $user = Auth::user();
    $task = Task::with('resource')->findOrFail($id);
    
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
    
    return view('tasks.edit', compact('task', 'activities', 'resources'));
}

/**
 * Update the specified resource in storage.
 */
public function update(Request $request, string $id)
{
    $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'activity_id' => 'required|exists:activities,id',
        'resource_id' => 'nullable|exists:resources,id',
        'estimated_minutes' => 'required|integer|min:1',
        'actual_minutes' => 'nullable|integer|min:0',
        'due_date' => 'nullable|date',
        'status' => 'nullable|in:pending,in_progress,completed',
        'order' => 'nullable|integer|min:0',
    ]);

    if ($validator->fails()) {
        return redirect()->back()
            ->withErrors($validator)
            ->withInput();
    }

    $user = Auth::user();
    $task = Task::findOrFail($id);
    $originalActivityId = $task->activity_id;
    $originalResourceId = $task->resource_id;
    
    // Verifica che l'utente risorsa abbia accesso alla nuova attività
    if (!$user->is_admin && $request->activity_id != $originalActivityId) {
        $newActivity = Activity::findOrFail($request->activity_id);
        $hasAccess = false;
        
        if ($newActivity->resource_id == $user->resource_id) {
            $hasAccess = true;
        } elseif ($newActivity->has_multiple_resources) {
            $hasAccess = $newActivity->resources()->where('resources.id', $user->resource_id)->exists();
        }
        
        if (!$hasAccess) {
            return redirect()->back()
                ->with('error', 'Non hai accesso alla nuova attività selezionata.')
                ->withInput();
        }
    }
    
    // Se l'utente non è admin, verifica che possa modificare la risorsa
    if (!$user->is_admin && $request->has('resource_id') && $originalResourceId != $request->resource_id) {
        // Utenti non admin possono assegnare task solo alla propria risorsa
        if ($user->resource_id != $request->resource_id) {
            return redirect()->back()
                ->with('error', 'Non puoi assegnare il task a una risorsa diversa da te stesso.')
                ->withInput();
        }
    }
    
    // Se non è stata specificata una risorsa ma l'utente è una risorsa, usa l'utente corrente
    if (!$request->has('resource_id') && $user->resource_id) {
        $request->merge(['resource_id' => $user->resource_id]);
    }
    
    $task->fill($request->all());
    $task->save();
    
    // Se è cambiata l'attività, aggiorna gli stati di entrambe le attività
    if ($originalActivityId != $request->activity_id) {
        $originalActivity = Activity::find($originalActivityId);
        if ($originalActivity) {
            $originalActivity->updateStatusFromTasks();
            $originalActivity->updateActualMinutesFromTasks();
        }
        
        $newActivity = Activity::find($request->activity_id);
        if ($newActivity) {
            $newActivity->updateStatusFromTasks();
            $newActivity->updateActualMinutesFromTasks();
        }
    } else {
        // Aggiorna solo l'attività corrente
        $task->updateParentActivity();
    }

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
        
        // Aggiorna lo stato e i minuti effettivi dell'attività
        $activity = Activity::find($activityId);
        if ($activity) {
            $activity->updateStatusFromTasks();
            $activity->updateActualMinutesFromTasks();
        }
        
        return redirect()->route('activities.index')
            ->with('success', 'Task eliminato con successo.');
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
    
    /**
     * Update task actual minutes from timer.
     */
    public function updateTaskTimer(Request $request, string $id)
    {
        // Debug: registra i dati della richiesta
        \Log::info('Timer update request', [
            'task_id' => $id,
            'actual_minutes' => $request->actual_minutes,
            'all_data' => $request->all(),
            'headers' => $request->headers->all()
        ]);
        
        $validator = Validator::make($request->all(), [
            'actual_minutes' => 'required|integer|min:0',
        ]);
        
        if ($validator->fails()) {
            \Log::error('Timer update validation failed', [
                'errors' => $validator->errors()->toArray()
            ]);
            
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        try {
            $task = Task::findOrFail($id);
            
            \Log::info('Task found', [
                'task_id' => $task->id,
                'task_name' => $task->name,
                'previous_minutes' => $task->actual_minutes,
                'new_minutes' => $request->actual_minutes
            ]);
            
            $task->actual_minutes = $request->actual_minutes;
            $task->save();
            
            // Aggiorna l'attività parent
            $task->updateParentActivity();
            
            // Calcola bonus
            $bonus = 0;
            if ($task->estimated_minutes >= $task->actual_minutes && $task->actual_minutes > 0) {
                $hourlyRate = $this->getResourceHourlyRate($task);
                $bonus = ($task->actual_minutes / 60) * $hourlyRate * 0.05; // 5% bonus
            }
            
            \Log::info('Timer update successful', [
                'task_id' => $task->id,
                'actual_minutes' => $task->actual_minutes,
                'bonus' => $bonus
            ]);
            
            return response()->json([
                'success' => true,
                'task' => $task,
                'bonus' => round($bonus, 2),
                'message' => 'Minuti effettivi aggiornati con successo'
            ]);
        } catch (\Exception $e) {
            \Log::error('Timer update exception', [
                'exception' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Si è verificato un errore: ' . $e->getMessage()
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
     * Vista alternativa per gestione tempi.
     * Questo metodo serve tutte le rotte che potrebbero portare alla vista di gestione tempi.
     */
    public function timeTracking()
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
            'estimatedMinutes' => $tasks->sum('estimated_minutes'),
            'actualMinutes' => $tasks->sum('actual_minutes'),
            'balance' => $tasks->sum('estimated_minutes') - $tasks->sum('actual_minutes'),
            'bonus' => 0
        ];
        
        // Calcolo del bonus totale
        foreach ($tasks as $task) {
            $balance = $task->estimated_minutes - $task->actual_minutes;
            if ($balance >= 0 && $task->actual_minutes > 0) {
                $hourlyRate = $this->getResourceHourlyRate($task);
                $bonus = ($task->actual_minutes / 60) * $hourlyRate * 0.05;
                $totalStats['bonus'] += $bonus;
            }
        }
        
        return view('tasks.timetracking', compact('tasks', 'projects', 'clients', 'resources', 'totalStats'));
    }
    
    /**
     * Ottiene la tariffa oraria per la risorsa assegnata al task.
     */
    private function getResourceHourlyRate($task)
    {
        if (!$task->activity || !$task->activity->resource) {
            return 0;
        }

        $resource = $task->activity->resource;
        $hoursType = $task->activity->hours_type;

        if ($hoursType == 'standard') {
            return $resource->selling_price;
        } else {
            return $resource->extra_selling_price ?: ($resource->selling_price * 1.2);
        }
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
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="task_time_report.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        $callback = function() use ($tasks) {
            $file = fopen('php://output', 'w');
            
            // Intestazioni
            fputcsv($file, [
                'Nome Task',
                'Cliente',
                'Progetto',
                'Attività',
                'Risorsa',
                'Stato',
                'Minuti Stimati',
                'Minuti Effettivi',
                'Differenza',
                'Bonus (€)'
            ]);
            
            // Dati
            foreach ($tasks as $task) {
                $hourlyRate = $this->getResourceHourlyRate($task);
                $bonus = 0;
                $balance = $task->estimated_minutes - $task->actual_minutes;
                
                if ($balance >= 0 && $task->actual_minutes > 0) {
                    $bonus = ($task->actual_minutes / 60) * $hourlyRate * 0.05;
                }
                
                fputcsv($file, [
                    $task->name,
                    $task->activity && $task->activity->project && $task->activity->project->client ? 
                        $task->activity->project->client->name : 'N/D',
                    $task->activity && $task->activity->project ? $task->activity->project->name : 'N/D',
                    $task->activity ? $task->activity->name : 'N/D',
                    $task->activity && $task->activity->resource ? $task->activity->resource->name : 'N/D',
                    $task->status,
                    $task->estimated_minutes,
                    $task->actual_minutes,
                    $balance,
                    number_format($bonus, 2)
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
}