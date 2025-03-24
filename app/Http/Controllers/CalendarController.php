<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Activity;
use App\Models\Task;
use App\Models\Resource;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    /**
     * Mostra la vista del calendario.
     */
    public function index()
    {
        // Ottieni tutte le risorse per il filtro
        $resources = Resource::where('is_active', true)->get();
        
        return view('calendar.index', compact('resources'));
    }
    
    /**
 * API per ottenere gli eventi del calendario in formato JSON.
 */
public function getEvents(Request $request)
{
    // Inizializza array degli eventi
    $events = [];
    
    // Filtri
    $eventType = $request->input('event_type', 'all');
    $resourceId = $request->input('resource_id');
    $status = $request->input('status');
    
    // Recupera i progetti con scadenza
    if ($eventType == 'all' || $eventType == 'projects') {
        $projectsQuery = Project::whereNotNull('end_date');
        
        // Filtra per stato se specificato
        if ($status) {
            $projectsQuery->where('status', $status);
        }
        
        // Filtra per risorsa se specificata (progetto contiene la risorsa)
        if ($resourceId) {
            $projectsQuery->whereHas('resources', function($query) use ($resourceId) {
                $query->where('resources.id', $resourceId);
            });
        }
        
        $projects = $projectsQuery->get();
        
        foreach ($projects as $project) {
            $resourceNames = $project->resources->pluck('name')->join(', ');
            
            $events[] = [
                'id' => 'project_' . $project->id,
                'title' => $project->name,
                'start' => $project->end_date->format('Y-m-d'),
                'color' => $this->getStatusColor($project->status),
                'textColor' => '#fff',
                'extendedProps' => [
                    'type' => 'project',
                    'client' => $project->client->name,
                    'resources' => $resourceNames,
                    'status' => $project->status,
                    'description' => $project->description,
                    'url' => route('projects.show', $project->id)
                ]
            ];
        }
    }
    
    // Recupera le attività con scadenza
    if ($eventType == 'all' || $eventType == 'activities') {
        $activitiesQuery = Activity::whereNotNull('due_date');
        
        // Filtra per stato se specificato
        if ($status) {
            $activitiesQuery->where('status', $status);
        }
        
        // Filtra per risorsa se specificata
        if ($resourceId) {
            $activitiesQuery->where('resource_id', $resourceId);
        }
        
        $activities = $activitiesQuery->get();
        
        foreach ($activities as $activity) {
            $events[] = [
                'id' => 'activity_' . $activity->id,
                'title' => $activity->name,
                'start' => $activity->due_date->format('Y-m-d'),
                'color' => $this->getStatusColor($activity->status),
                'textColor' => '#fff',
                'extendedProps' => [
                    'type' => 'activity',
                    'project' => $activity->project->name,
                    'resource' => $activity->resource->name,
                    'status' => $activity->status,
                    'url' => route('activities.show', $activity->id)
                ]
            ];
        }
    }
    
    // Recupera i task con scadenza
    if ($eventType == 'all' || $eventType == 'tasks') {
        $tasksQuery = Task::whereNotNull('due_date');
        
        // Filtra per stato se specificato
        if ($status) {
            $tasksQuery->where('status', $status);
        }
        
        // Filtra per risorsa se specificata
        if ($resourceId) {
            $tasksQuery->whereHas('activity', function($query) use ($resourceId) {
                $query->where('resource_id', $resourceId);
            });
        }
        
        $tasks = $tasksQuery->with('activity.resource')->get();
        
        foreach ($tasks as $task) {
            if (!$task->activity) continue; // Salta se non ha un'attività associata
            
            $events[] = [
                'id' => 'task_' . $task->id,
                'title' => $task->name,
                'start' => $task->due_date->format('Y-m-d'),
                'color' => $this->getStatusColor($task->status),
                'textColor' => '#fff',
                'extendedProps' => [
                    'type' => 'task',
                    'project' => $task->activity->project->name ?? 'N/D',
                    'activity' => $task->activity->name,
                    'resource' => $task->activity->resource->name ?? 'N/D',
                    'status' => $task->status,
                    'url' => route('tasks.show', $task->id)
                ]
            ];
        }
    }
    
    return response()->json($events);
}

/**
 * Ottiene il colore in base allo stato.
 */
private function getStatusColor($status)
{
    $colors = [
        'pending' => '#ffc107',    // Giallo per "in attesa"
        'in_progress' => '#0d6efd', // Blu per "in corso"
        'completed' => '#198754',   // Verde per "completato"
        'on_hold' => '#6c757d'      // Grigio per "in pausa" (progetti)
    ];
    
    return $colors[$status] ?? '#6c757d'; // Default grigio
}
}