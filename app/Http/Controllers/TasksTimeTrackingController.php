<?php
namespace App\Http\Controllers;
use App\Models\Task;
use App\Models\Project;
use App\Models\Client;
use App\Models\Resource;
use App\Models\Activity;

class TasksTimeTrackingController extends Controller
{
    public function __invoke()
    {
        $tasks = Task::with(['activity.project.client', 'activity.resource'])->get();
        $projects = Project::all();
        $clients = Client::all();
        $resources = Resource::where('is_active', true)->get();
        $activities = Activity::with('project')->get();
        
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
                $hourlyRate = 0;
                
                // Determina quale tariffa usare in base al tipo di ore
                if ($task->activity && $task->activity->resource) {
                    if ($task->activity->hours_type == 'standard') {
                        $hourlyRate = $task->activity->resource->selling_price;
                    } else {
                        $hourlyRate = !empty($task->activity->resource->extra_selling_price) 
                            ? $task->activity->resource->extra_selling_price 
                            : $task->activity->resource->selling_price;
                    }
                }
                
                $bonus = ($task->actual_minutes / 60) * $hourlyRate * 0.05;
                $totalStats['bonus'] += $bonus;
            }
        }
        
        return view('tasks.timetracking', compact(
            'tasks', 'projects', 'clients', 'resources', 'activities', 'totalStats'
        ));
    }
}
