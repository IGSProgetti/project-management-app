<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Project;
use App\Models\Area;
use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ActivityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $activities = Activity::with(['project', 'resource', 'area'])->get();
        $projects = Project::all();
        $resources = Resource::all();
        return view('activities.index', compact('activities', 'projects', 'resources'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $projects = Project::all();
        $areas = collect(); // Vuoto per default, verranno popolate via AJAX
        $resources = Resource::where('is_active', true)->get();
        
        return view('activities.create', compact('projects', 'areas', 'resources'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'project_id' => 'required|exists:projects,id',
            'area_id' => 'nullable|exists:areas,id',
            'resource_id' => 'required|exists:resources,id',
            'estimated_minutes' => 'required|integer|min:1',
            'due_date' => 'nullable|date',
            'status' => 'nullable|in:pending,in_progress,completed',
            'hours_type' => 'required|in:standard,extra',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Verifica che l'area appartenga al progetto
        if ($request->area_id) {
            $area = Area::find($request->area_id);
            if ($area && $area->project_id != $request->project_id) {
                return redirect()->back()
                    ->with('error', 'L\'area selezionata non appartiene al progetto selezionato.')
                    ->withInput();
            }
        }

        // Calcola il costo stimato
        $resource = Resource::findOrFail($request->resource_id);
        $project = Project::findOrFail($request->project_id);
        
        // Ottieni la tariffa oraria della risorsa per questo progetto in base al tipo di ore
        $hourlyRate = null;
        $projectResource = $project->resources()
            ->where('resources.id', $resource->id)
            ->wherePivot('hours_type', $request->hours_type)
            ->first();
        
        if ($projectResource) {
            $hourlyRate = $projectResource->pivot->adjusted_rate;
        } else {
            // Se la risorsa non è collegata al progetto con questo tipo di ore, usa la tariffa di base
            if ($request->hours_type == 'standard') {
                $baseRate = $resource->selling_price;
            } else {
                $baseRate = $resource->extra_selling_price ?: $resource->selling_price * 1.2;
            }
            $hourlyRate = $project->calculateAdjustedRate($baseRate);
        }
        
        // Calcola il costo in base ai minuti stimati
        $estimatedCost = ($request->estimated_minutes / 60) * $hourlyRate;
        
        $activity = new Activity();
        $activity->fill($request->all());
        $activity->estimated_cost = $estimatedCost;
        $activity->actual_minutes = 0;
        $activity->actual_cost = 0;
        $activity->status = $request->status ?? 'pending';
        $activity->hours_type = $request->hours_type;
        $activity->save();

        return redirect()->route('activities.show', $activity->id)
            ->with('success', 'Attività creata con successo.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $activity = Activity::with(['project', 'area', 'resource', 'tasks'])->findOrFail($id);
        return view('activities.show', compact('activity'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $activity = Activity::findOrFail($id);
        $projects = Project::all();
        $areas = Area::where('project_id', $activity->project_id)->get();
        $resources = Resource::where('is_active', true)->get();
        
        return view('activities.edit', compact('activity', 'projects', 'areas', 'resources'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'project_id' => 'required|exists:projects,id',
            'area_id' => 'nullable|exists:areas,id',
            'resource_id' => 'required|exists:resources,id',
            'estimated_minutes' => 'required|integer|min:1',
            'actual_minutes' => 'nullable|integer|min:0',
            'due_date' => 'nullable|date',
            'status' => 'nullable|in:pending,in_progress,completed',
            'hours_type' => 'required|in:standard,extra',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Verifica che l'area appartenga al progetto
        if ($request->area_id) {
            $area = Area::find($request->area_id);
            if ($area && $area->project_id != $request->project_id) {
                return redirect()->back()
                    ->with('error', 'L\'area selezionata non appartiene al progetto selezionato.')
                    ->withInput();
            }
        }

        $activity = Activity::findOrFail($id);
        
        // Calcola il costo stimato
        $resource = Resource::findOrFail($request->resource_id);
        $project = Project::findOrFail($request->project_id);
        
        // Ottieni la tariffa oraria della risorsa per questo progetto in base al tipo di ore
        $hourlyRate = null;
        $projectResource = $project->resources()
            ->where('resources.id', $resource->id)
            ->wherePivot('hours_type', $request->hours_type)
            ->first();
        
        if ($projectResource) {
            $hourlyRate = $projectResource->pivot->adjusted_rate;
        } else {
            // Se la risorsa non è collegata al progetto con questo tipo di ore, usa la tariffa di base
            if ($request->hours_type == 'standard') {
                $baseRate = $resource->selling_price;
            } else {
                $baseRate = $resource->extra_selling_price ?: $resource->selling_price * 1.2;
            }
            $hourlyRate = $project->calculateAdjustedRate($baseRate);
        }
        
        // Calcola il costo in base ai minuti stimati
        $estimatedCost = ($request->estimated_minutes / 60) * $hourlyRate;
        
        // Calcola il costo effettivo se sono stati forniti i minuti effettivi
        $actualCost = 0;
        if ($request->has('actual_minutes') && $request->actual_minutes > 0) {
            $actualCost = ($request->actual_minutes / 60) * $hourlyRate;
        }
        
        $activity->fill($request->all());
        $activity->estimated_cost = $estimatedCost;
        
        if ($request->has('actual_minutes')) {
            $activity->actual_minutes = $request->actual_minutes;
            $activity->actual_cost = $actualCost;
        }
        
        $activity->save();

        return redirect()->route('activities.show', $activity->id)
            ->with('success', 'Attività aggiornata con successo.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $activity = Activity::findOrFail($id);
        
        // Elimina anche i task associati
        $activity->tasks()->delete();
        
        $activity->delete();
        
        return redirect()->route('activities.index')
            ->with('success', 'Attività eliminata con successo.');
    }
    
    /**
     * Get activities by project for AJAX requests.
     */
    public function byProject(string $projectId)
    {
        $activities = Activity::with(['resource', 'area'])
            ->where('project_id', $projectId)
            ->get();
        
        return response()->json([
            'success' => true,
            'activities' => $activities
        ]);
    }
    
    /**
     * Get activities by area for AJAX requests.
     */
    public function byArea(string $areaId)
    {
        $activities = Activity::with(['resource', 'project'])
            ->where('area_id', $areaId)
            ->get();
        
        return response()->json([
            'success' => true,
            'activities' => $activities
        ]);
    }
    
    /**
     * Update activity status.
     */
    public function updateStatus(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,in_progress,completed',
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

        $activity = Activity::findOrFail($id);
        $activity->status = $request->status;
        
        // Se l'attività viene completata e non ha minuti effettivi, assumiamo quelli stimati
        if ($request->status == 'completed' && $activity->actual_minutes == 0) {
            $activity->actual_minutes = $activity->estimated_minutes;
            
            // Calcola il costo effettivo basato sui minuti stimati
            $resource = Resource::findOrFail($activity->resource_id);
            $project = Project::findOrFail($activity->project_id);
            
            // Ottieni la tariffa oraria della risorsa considerando il tipo di ore
            $hourlyRate = null;
            $projectResource = $project->resources()
                ->where('resources.id', $resource->id)
                ->wherePivot('hours_type', $activity->hours_type)
                ->first();
            
            if ($projectResource) {
                $hourlyRate = $projectResource->pivot->adjusted_rate;
            } else {
                // Se la risorsa non è collegata al progetto con questo tipo di ore, usa la tariffa di base
                if ($activity->hours_type == 'standard') {
                    $baseRate = $resource->selling_price;
                } else {
                    $baseRate = $resource->extra_selling_price ?: $resource->selling_price * 1.2;
                }
                $hourlyRate = $project->calculateAdjustedRate($baseRate);
            }
            
            $activity->actual_cost = ($activity->actual_minutes / 60) * $hourlyRate;
        }
        
        $activity->save();
        
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'activity' => $activity
            ]);
        }
        
        return redirect()->route('activities.show', $activity->id)
            ->with('success', 'Stato dell\'attività aggiornato con successo.');
    }
    
    /**
     * Calculate estimated cost for AJAX requests.
     */
    public function calculateEstimatedCost(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'resource_id' => 'required|exists:resources,id',
            'project_id' => 'required|exists:projects,id',
            'estimated_minutes' => 'required|integer|min:1',
            'hours_type' => 'required|in:standard,extra',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Calcola il costo stimato
        $resource = Resource::findOrFail($request->resource_id);
        $project = Project::findOrFail($request->project_id);
        
        // Ottieni la tariffa oraria della risorsa per questo progetto in base al tipo di ore
        $hourlyRate = null;
        $projectResource = $project->resources()
            ->where('resources.id', $resource->id)
            ->wherePivot('hours_type', $request->hours_type)
            ->first();
        
        if ($projectResource) {
            $hourlyRate = $projectResource->pivot->adjusted_rate;
        } else {
            // Se la risorsa non è collegata al progetto con questo tipo di ore, usa la tariffa di base
            if ($request->hours_type == 'standard') {
                $baseRate = $resource->selling_price;
            } else {
                $baseRate = $resource->extra_selling_price ?: $resource->selling_price * 1.2;
            }
            $hourlyRate = $project->calculateAdjustedRate($baseRate);
        }
        
        // Calcola il costo in base ai minuti stimati
        $estimatedCost = ($request->estimated_minutes / 60) * $hourlyRate;
        
        return response()->json([
            'success' => true,
            'hourly_rate' => $hourlyRate,
            'estimated_cost' => $estimatedCost,
        ]);
    }
}