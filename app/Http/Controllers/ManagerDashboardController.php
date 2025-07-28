<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\Project;
use App\Models\Resource;
use App\Models\Activity;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ManagerDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        // Verifica che l'utente sia admin
        if (!Auth::user()->is_admin) {
            abort(403, 'Accesso non autorizzato. Solo gli amministratori possono accedere a questa sezione.');
        }

        // Se è una richiesta AJAX, restituisce solo i dati JSON
        if ($request->ajax()) {
            return response()->json($this->getAllData());
        }

        // Ottieni tutti i dati
        $data = $this->getAllData();
        
        return view('manager.dashboard', $data);
    }

    private function getAllData()
    {
        // 📊 STATISTICHE GENERALI
        $stats = [
            'clients' => Client::count(),
            'projects' => Project::count(),
            'resources' => Resource::where('is_active', true)->count(),
            'activities' => Activity::count(),
            'tasks' => Task::count(),
        ];

        // 💰 DATI FINANZIARI REALI
        $financialData = $this->getFinancialData();
        
        // ⏰ DATI TIMETRACKING REALI
        $timeTrackingData = $this->getTimeTrackingData();
        
        // 👥 DATI RISORSE REALI
        $resourcesData = $this->getResourcesData();
        
        // 🏢 DATI CLIENTI REALI
        $clientsData = $this->getClientsData();
        
        // 📅 ORE GIORNALIERE REALI
        $dailyHoursData = $this->getDailyHoursData();
        
        // 🕐 ORE GENERALI REALI
        $hoursData = $this->getHoursData();

        return compact(
            'stats',
            'financialData', 
            'timeTrackingData',
            'resourcesData',
            'clientsData',
            'dailyHoursData',
            'hoursData'
        );
    }

    private function getFinancialData()
    {
        // CALCOLI REALI basati sui progetti e budget
        $projects = Project::all();
        $totalBudget = $projects->sum('budget') ?: 0;
        
        // Calcolo costi stimati reali dalle attività
        $totalEstimatedCost = 0;
        $totalActualCost = 0;
        
        $activities = Activity::with('resource')->get();
        foreach ($activities as $activity) {
            if ($activity->resource) {
                $hourlyRate = $activity->hours_type === 'extra' 
                    ? ($activity->resource->extra_selling_price ?: $activity->resource->selling_price)
                    : $activity->resource->selling_price;
                
                $estimatedHours = ($activity->estimated_minutes ?: 0) / 60;
                $actualHours = ($activity->actual_minutes ?: 0) / 60;
                
                $totalEstimatedCost += $estimatedHours * ($hourlyRate ?: 0);
                $totalActualCost += $actualHours * ($hourlyRate ?: 0);
            }
        }
        
        $totalBalance = $totalBudget - $totalActualCost;
        
        return [
            'total_budget' => $totalBudget,
            'total_estimated_cost' => $totalEstimatedCost,
            'total_actual_cost' => $totalActualCost,
            'total_balance' => $totalBalance,
            'projects_count' => $projects->count()
        ];
    }

    private function getTimeTrackingData()
    {
        $tasks = Task::with(['activity.project.client', 'activity.resource'])->get();
        
        $totalStats = [
            'estimatedMinutes' => $tasks->sum('estimated_minutes') ?: 0,
            'actualMinutes' => $tasks->sum('actual_minutes') ?: 0,
            'balance' => ($tasks->sum('estimated_minutes') ?: 0) - ($tasks->sum('actual_minutes') ?: 0),
            'bonus' => 0
        ];
        
        // Calcolo bonus reale
        foreach ($tasks as $task) {
            $balance = ($task->estimated_minutes ?: 0) - ($task->actual_minutes ?: 0);
            if ($balance >= 0 && ($task->actual_minutes ?: 0) > 0) {
                $hourlyRate = $this->getResourceHourlyRate($task);
                $bonus = (($task->actual_minutes ?: 0) / 60) * $hourlyRate * 0.05;
                $totalStats['bonus'] += $bonus;
            }
        }
        
        return [
            'stats' => $totalStats,
            'tasks' => $tasks->take(10), // Prime 10 task per la tabella
            'total_tasks' => $tasks->count()
        ];
    }

    private function getResourcesData()
    {
        $resources = Resource::where('is_active', true)->get();
        
        // Aggiungi conteggi reali
        foreach ($resources as $resource) {
            // Conteggio attività primarie
            $primaryActivities = Activity::where('resource_id', $resource->id)->count();
            
            // Conteggio attività multiple (dalla tabella pivot)
            $multipleActivities = DB::table('activity_resource')
                ->where('resource_id', $resource->id)
                ->distinct('activity_id')
                ->count();
            
            $resource->activities_count = $primaryActivities + $multipleActivities;
            
            // Conteggio task reali
            $tasks = Task::whereHas('activity', function($query) use ($resource) {
                $query->where('resource_id', $resource->id)
                      ->orWhereHas('resources', function($q) use ($resource) {
                          $q->where('resources.id', $resource->id);
                      });
            })->count();
            
            $resource->tasks_count = $tasks;
        }
        
        return [
            'resources' => $resources,
            'total_cost' => $resources->sum('cost_price') ?: 0,
            'total_selling' => $resources->sum('selling_price') ?: 0,
            'avg_utilization' => $this->calculateAvgUtilization($resources)
        ];
    }

    private function getClientsData()
    {
        $clients = Client::all();
        
        // Aggiungi conteggi reali
        foreach ($clients as $client) {
            $client->projects_count = Project::where('client_id', $client->id)->count();
        }
            
        return [
            'clients' => $clients,
            'active_clients' => $clients->where('projects_count', '>', 0)->count(),
            'total_projects' => $clients->sum('projects_count')
        ];
    }

    private function getDailyHoursData()
    {
        $today = Carbon::today();
        $weekStart = Carbon::today()->startOfWeek();
        
        $todayHours = 0;
        $weekHours = 0;
        $recentEntries = collect();
        
        try {
            // Prova prima con daily_hours se la tabella esiste
            if (DB::getSchemaBuilder()->hasTable('daily_hours')) {
                $todayHours = DB::table('daily_hours')
                    ->whereDate('date', $today)
                    ->sum('hours');
                    
                $weekHours = DB::table('daily_hours')
                    ->whereBetween('date', [$weekStart, $today])
                    ->sum('hours');
                    
                $recentEntries = DB::table('daily_hours')
                    ->join('resources', 'daily_hours.resource_id', '=', 'resources.id')
                    ->select('daily_hours.*', 'resources.name as resource_name')
                    ->orderBy('date', 'desc')
                    ->limit(10)
                    ->get();
            } else {
                // Fallback usando i task aggiornati oggi
                $todayTasks = Task::whereDate('updated_at', $today)->get();
                $todayHours = $todayTasks->sum('actual_minutes') / 60;
                
                $weekTasks = Task::whereBetween('updated_at', [$weekStart, $today])->get();
                $weekHours = $weekTasks->sum('actual_minutes') / 60;
                
                $recentEntries = Task::with(['activity.resource'])
                    ->where('actual_minutes', '>', 0)
                    ->orderBy('updated_at', 'desc')
                    ->limit(10)
                    ->get()
                    ->map(function($task) {
                        return (object)[
                            'date' => $task->updated_at->format('Y-m-d'),
                            'resource_name' => $task->activity->resource->name ?? 'N/A',
                            'hours' => round(($task->actual_minutes ?: 0) / 60, 1),
                            'notes' => $task->notes ?? '-'
                        ];
                    });
            }
        } catch (\Exception $e) {
            // Se c'è un errore, usa valori di default
            $todayHours = 0;
            $weekHours = 0;
            $recentEntries = collect();
        }
            
        return [
            'today_hours' => round($todayHours, 1),
            'week_hours' => round($weekHours, 1),
            'recent_entries' => $recentEntries
        ];
    }

    private function getHoursData()
    {
        $resources = Resource::where('is_active', true)->get();
        
        $hoursStats = [];
        foreach ($resources as $resource) {
            // 🆕 CALCOLO CAPACITÀ TOTALE ANNUALE REALE
            $standardHoursPerYear = ($resource->working_days_year ?: 250) * ($resource->working_hours_day ?: 8);
            $extraHoursPerYear = ($resource->working_days_year ?: 250) * ($resource->extra_hours_day ?: 0);
            $totalCapacityHours = $standardHoursPerYear + $extraHoursPerYear;
            
            // 🆕 CALCOLO ORE ASSEGNATE REALI (da attività/task)
            $assignedStandardMinutes = $this->getAssignedMinutesByResource($resource->id, 'standard');
            $assignedExtraMinutes = $this->getAssignedMinutesByResource($resource->id, 'extra');
            $totalAssignedHours = ($assignedStandardMinutes + $assignedExtraMinutes) / 60;
            
            // 🆕 CALCOLO ORE LAVORATE EFFETTIVE REALI
            $workedStandardMinutes = $this->getWorkedMinutesByResource($resource->id, 'standard');
            $workedExtraMinutes = $this->getWorkedMinutesByResource($resource->id, 'extra');
            $totalWorkedHours = ($workedStandardMinutes + $workedExtraMinutes) / 60;
            
            // 🆕 CALCOLO ORE DISPONIBILI
            $availableHours = $totalCapacityHours - $totalAssignedHours;
            
            // 🆕 CALCOLO ORE MESE CORRENTE REALI
            $monthlyWorkedHours = $this->getMonthlyWorkedHours($resource->id);
            
            $hoursStats[] = [
                'resource' => $resource,
                'total_capacity_hours' => round($totalCapacityHours, 1),
                'total_assigned_hours' => round($totalAssignedHours, 1),
                'total_worked_hours' => round($totalWorkedHours, 1),
                'available_hours' => round($availableHours, 1),
                'monthly_hours' => round($monthlyWorkedHours, 1),
                'standard_capacity' => round($standardHoursPerYear, 1),
                'extra_capacity' => round($extraHoursPerYear, 1),
                'utilization_percentage' => $totalCapacityHours > 0 ? round(($totalAssignedHours / $totalCapacityHours) * 100, 1) : 0
            ];
        }
        
        return [
            'resources_hours' => $hoursStats,
            'total_capacity_all' => round(collect($hoursStats)->sum('total_capacity_hours'), 1),
            'total_assigned_all' => round(collect($hoursStats)->sum('total_assigned_hours'), 1),
            'total_available_all' => round(collect($hoursStats)->sum('available_hours'), 1)
        ];
    }

    private function getAssignedMinutesByResource($resourceId, $hoursType)
    {
        // Minuti dalle attività primarie (resource_id diretto)
        $primaryMinutes = Activity::where('resource_id', $resourceId)
            ->where('hours_type', $hoursType)
            ->sum('estimated_minutes');
        
        // Minuti dalle attività multiple (tabella pivot activity_resource)
        $multipleMinutes = 0;
        if (DB::getSchemaBuilder()->hasTable('activity_resource')) {
            $multipleMinutes = DB::table('activity_resource')
                ->where('resource_id', $resourceId)
                ->where('hours_type', $hoursType)
                ->sum('estimated_minutes');
        }
            
        return ($primaryMinutes ?: 0) + ($multipleMinutes ?: 0);
    }

    private function getWorkedMinutesByResource($resourceId, $hoursType)
    {
        // Minuti effettivi dalle attività primarie
        $primaryMinutes = Activity::where('resource_id', $resourceId)
            ->where('hours_type', $hoursType)
            ->sum('actual_minutes');
        
        // Minuti effettivi dalle attività multiple
        $multipleMinutes = 0;
        if (DB::getSchemaBuilder()->hasTable('activity_resource')) {
            $multipleMinutes = DB::table('activity_resource')
                ->where('resource_id', $resourceId)
                ->where('hours_type', $hoursType)
                ->sum('actual_minutes');
        }
            
        return ($primaryMinutes ?: 0) + ($multipleMinutes ?: 0);
    }

    private function getMonthlyWorkedHours($resourceId)
    {
        $currentMonth = Carbon::now()->startOfMonth();
        $nextMonth = Carbon::now()->endOfMonth();
        
        try {
            if (DB::getSchemaBuilder()->hasTable('daily_hours')) {
                return DB::table('daily_hours')
                    ->where('resource_id', $resourceId)
                    ->whereBetween('date', [$currentMonth, $nextMonth])
                    ->sum('hours') ?: 0;
            } else {
                // Fallback usando i task del mese corrente
                $tasks = Task::whereHas('activity', function($query) use ($resourceId) {
                    $query->where('resource_id', $resourceId);
                })
                ->whereBetween('updated_at', [$currentMonth, $nextMonth])
                ->get();
                
                return $tasks->sum('actual_minutes') / 60;
            }
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getResourceHourlyRate($task)
    {
        if ($task->activity && $task->activity->resource) {
            $resource = $task->activity->resource;
            if ($task->activity->hours_type === 'extra') {
                return $resource->extra_selling_price ?: $resource->selling_price ?: 50;
            }
            return $resource->selling_price ?: 50;
        }
        
        return 50; // Default
    }

    private function calculateAvgUtilization($resources)
    {
        if ($resources->isEmpty()) return 0;
        
        $totalUtilization = 0;
        $count = 0;
        
        foreach ($resources as $resource) {
            $capacity = (($resource->working_days_year ?: 250) * ($resource->working_hours_day ?: 8));
            if ($capacity > 0) {
                $assigned = $this->getAssignedMinutesByResource($resource->id, 'standard') + 
                           $this->getAssignedMinutesByResource($resource->id, 'extra');
                $assignedHours = $assigned / 60;
                $utilization = ($assignedHours / $capacity) * 100;
                $totalUtilization += $utilization;
                $count++;
            }
        }
        
        return $count > 0 ? round($totalUtilization / $count, 1) : 0;
    }
}