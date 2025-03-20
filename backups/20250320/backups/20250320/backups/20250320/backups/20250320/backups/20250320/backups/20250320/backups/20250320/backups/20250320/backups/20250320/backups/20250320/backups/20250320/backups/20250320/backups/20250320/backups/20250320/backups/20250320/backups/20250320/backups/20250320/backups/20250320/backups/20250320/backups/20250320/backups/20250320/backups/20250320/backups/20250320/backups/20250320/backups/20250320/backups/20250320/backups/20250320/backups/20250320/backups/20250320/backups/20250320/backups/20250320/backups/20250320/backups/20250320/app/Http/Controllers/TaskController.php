<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tasks = Task::with(['activity.project', 'activity.resource'])->get();
        $activities = Activity::with('project')->get();
        return view('tasks.index', compact('tasks', 'activities'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $activities = Activity::with('project')->get();
        
        // Controlla se c'è un activity_id nella query string
        $selectedActivityId = $request->query('activity_id');
        $selectedActivity = null;
        
        if ($selectedActivityId) {
            $selectedActivity = Activity::find($selectedActivityId);
        }
        
        return view('tasks.create', compact('activities', 'selectedActivity'));
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

        $activity = Activity::findOrFail($request->activity_id);
        
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
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $task = Task::findOrFail($id);
        $activities = Activity::with('project')->get();
        return view('tasks.edit', compact('task', 'activities'));
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

        $task = Task::findOrFail($id);
        $originalActivityId = $task->activity_id;
        
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

        foreach ($request->tasks as $index => $taskId) {
            $task = Task::find($taskId);
            if ($task) {
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
        
        \Log::info('Timer update successful', [
            'task_id' => $task->id,
            'actual_minutes' => $task->actual_minutes
        ]);
        
        return response()->json([
            'success' => true,
            'task' => $task,
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
}