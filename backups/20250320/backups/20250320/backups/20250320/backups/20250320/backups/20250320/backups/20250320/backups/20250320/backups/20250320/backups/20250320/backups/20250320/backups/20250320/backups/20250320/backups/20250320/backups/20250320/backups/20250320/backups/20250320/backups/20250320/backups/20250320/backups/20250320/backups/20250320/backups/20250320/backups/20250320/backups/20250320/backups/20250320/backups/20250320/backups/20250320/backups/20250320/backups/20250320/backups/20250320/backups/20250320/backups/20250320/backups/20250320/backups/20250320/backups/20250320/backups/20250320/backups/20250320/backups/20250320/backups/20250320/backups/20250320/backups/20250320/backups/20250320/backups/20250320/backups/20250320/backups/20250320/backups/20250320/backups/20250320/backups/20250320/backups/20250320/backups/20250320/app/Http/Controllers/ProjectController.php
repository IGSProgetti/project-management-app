<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Client;
use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $projects = Project::with('client')->get();
        $clients = Client::all();
        return view('projects.index', compact('projects', 'clients'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $clients = Client::all();
        $resources = Resource::where('is_active', true)->get();
        
        return view('projects.create', compact('clients', 'resources'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'client_id' => 'required|exists:clients,id',
            'cost_steps' => 'nullable|array',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'default_hours_type' => 'required|in:standard,extra',
            'resources' => 'nullable|array',
            'resources.*' => 'exists:resources,id',
            'resource_standard_hours' => 'nullable|array',
            'resource_extra_hours' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Crea il progetto
        $project = new Project();
        $project->fill($request->only(['name', 'description', 'client_id', 'start_date', 'end_date', 'default_hours_type']));
        $project->cost_steps = $request->cost_steps ?? [1, 2, 3, 4, 5, 6, 7, 8];
        $project->status = 'pending';
        $project->save();

        // Associa le risorse al progetto
        if ($request->has('resources') && is_array($request->resources)) {
            $resourceData = [];
            
            foreach ($request->resources as $resourceId) {
                $resource = Resource::findOrFail($resourceId);
                
                // Determina ore standard e extra da assegnare
                $standardHours = $request->resource_standard_hours[$resourceId] ?? 0;
                $extraHours = $request->resource_extra_hours[$resourceId] ?? 0;
                
                if ($standardHours > 0) {
                    // Se ci sono ore standard, aggiungi questa risorsa con tipo 'standard'
                    $standardAdjustedRate = $project->calculateAdjustedRate($resource->selling_price);
                    
                    $resourceData[] = [
                        'resource_id' => $resourceId,
                        'hours' => $standardHours,
                        'hours_type' => 'standard',
                        'adjusted_rate' => $standardAdjustedRate,
                        'cost' => $standardHours * $standardAdjustedRate
                    ];
                }
                
                if ($extraHours > 0) {
                    // Se ci sono ore extra, aggiungi questa risorsa con tipo 'extra'
                    $extraAdjustedRate = $project->calculateAdjustedRate($resource->extra_selling_price ?: $resource->selling_price);
                    
                    $resourceData[] = [
                        'resource_id' => $resourceId,
                        'hours' => $extraHours,
                        'hours_type' => 'extra',
                        'adjusted_rate' => $extraAdjustedRate,
                        'cost' => $extraHours * $extraAdjustedRate
                    ];
                }
            }
            
            // Associa le risorse al progetto
            foreach ($resourceData as $data) {
                $project->resources()->attach($data['resource_id'], [
                    'hours' => $data['hours'],
                    'hours_type' => $data['hours_type'],
                    'adjusted_rate' => $data['adjusted_rate'],
                    'cost' => $data['cost']
                ]);
            }
            
            // Aggiorna il costo totale del progetto
            $project->updateTotalCost();
        }

        return redirect()->route('projects.show', $project->id)
            ->with('success', 'Progetto creato con successo.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $project = Project::with(['client', 'resources', 'areas', 'activities'])->findOrFail($id);
        
        return view('projects.show', compact('project'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $project = Project::with('resources')->findOrFail($id);
        $clients = Client::all();
        $resources = Resource::where('is_active', true)->get();
        
        return view('projects.edit', compact('project', 'clients', 'resources'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'client_id' => 'required|exists:clients,id',
            'cost_steps' => 'nullable|array',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'nullable|in:pending,in_progress,completed,on_hold',
            'default_hours_type' => 'required|in:standard,extra',
            'resources' => 'nullable|array',
            'resources.*' => 'exists:resources,id',
            'resource_standard_hours' => 'nullable|array',
            'resource_extra_hours' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $project = Project::findOrFail($id);
        $project->fill($request->only(['name', 'description', 'client_id', 'start_date', 'end_date', 'status', 'default_hours_type']));
        $project->cost_steps = $request->cost_steps ?? [1, 2, 3, 4, 5, 6, 7, 8];
        $project->save();

        // Aggiorna le risorse del progetto
        if ($request->has('resources')) {
            // Rimuovi le risorse esistenti
            $project->resources()->detach();
            
            if (is_array($request->resources)) {
                $resourceData = [];
                
                foreach ($request->resources as $resourceId) {
                    $resource = Resource::findOrFail($resourceId);
                    
                    // Determina ore standard e extra da assegnare
                    $standardHours = $request->resource_standard_hours[$resourceId] ?? 0;
                    $extraHours = $request->resource_extra_hours[$resourceId] ?? 0;
                    
                    if ($standardHours > 0) {
                        // Se ci sono ore standard, aggiungi questa risorsa con tipo 'standard'
                        $standardAdjustedRate = $project->calculateAdjustedRate($resource->selling_price);
                        
                        $resourceData[] = [
                            'resource_id' => $resourceId,
                            'hours' => $standardHours,
                            'hours_type' => 'standard',
                            'adjusted_rate' => $standardAdjustedRate,
                            'cost' => $standardHours * $standardAdjustedRate
                        ];
                    }
                    
                    if ($extraHours > 0) {
                        // Se ci sono ore extra, aggiungi questa risorsa con tipo 'extra'
                        $extraAdjustedRate = $project->calculateAdjustedRate($resource->extra_selling_price ?: $resource->selling_price);
                        
                        $resourceData[] = [
                            'resource_id' => $resourceId,
                            'hours' => $extraHours,
                            'hours_type' => 'extra',
                            'adjusted_rate' => $extraAdjustedRate,
                            'cost' => $extraHours * $extraAdjustedRate
                        ];
                    }
                }
                
                // Associa le risorse al progetto
                foreach ($resourceData as $data) {
                    $project->resources()->attach($data['resource_id'], [
                        'hours' => $data['hours'],
                        'hours_type' => $data['hours_type'],
                        'adjusted_rate' => $data['adjusted_rate'],
                        'cost' => $data['cost']
                    ]);
                }
            }
        }
        
        // Aggiorna il costo totale del progetto
        $project->updateTotalCost();

        return redirect()->route('projects.show', $project->id)
            ->with('success', 'Progetto aggiornato con successo.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $project = Project::findOrFail($id);
        
        // Verifica se ci sono attività associate
        if ($project->activities()->count() > 0) {
            return redirect()->route('projects.index')
                ->with('error', 'Impossibile eliminare il progetto. Ci sono attività associate.');
        }
        
        // Dissocia le risorse
        $project->resources()->detach();
        
        // Elimina le aree
        $project->areas()->delete();
        
        $project->delete();
        
        return redirect()->route('projects.index')
            ->with('success', 'Progetto eliminato con successo.');
    }
    
    /**
     * Calculate costs for the project based on resources and cost steps.
     */
    public function calculateCosts(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'resource_ids' => 'required|array',
            'resource_ids.*' => 'exists:resources,id',
            'standard_hours' => 'nullable|array',
            'extra_hours' => 'nullable|array',
            'cost_steps' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $resources = Resource::whereIn('id', $request->resource_ids)->get();
        $costSteps = $request->cost_steps ?? [1, 2, 3, 4, 5, 6, 7, 8];
        
        $summary = [];
        $totalCost = 0;
        
        foreach ($resources as $resource) {
            $resourceId = $resource->id;
            $standardHours = $request->standard_hours[$resourceId] ?? 0;
            $extraHours = $request->extra_hours[$resourceId] ?? 0;
            
            if ($standardHours <= 0 && $extraHours <= 0) {
                continue;
            }
            
            // Calcola il tasso orario in base agli step di costo
            $stepValues = [
                1 => 25,    // Costo struttura
                2 => 12.5,  // Utile gestore azienda
                3 => 12.5,  // Utile IGS
                4 => 20,    // Compenso professionista
                5 => 5,     // Bonus professionista
                6 => 3,     // Gestore società
                7 => 8,     // Chi porta il lavoro
                8 => 14     // Network IGS
            ];

            $totalDeduction = 0;
            foreach ($stepValues as $step => $percentage) {
                if (!in_array($step, $costSteps)) {
                    $totalDeduction += $percentage;
                }
            }
            
            // Calcola la tariffa oraria standard e extra
            $standardAdjustedRate = $resource->selling_price * (1 - $totalDeduction / 100);
            $extraAdjustedRate = ($resource->extra_selling_price ?: $resource->selling_price) * (1 - $totalDeduction / 100);
            
            // Calcola i costi standard e extra
            $standardCost = $standardHours * $standardAdjustedRate;
            $extraCost = $extraHours * $extraAdjustedRate;
            $totalResourceCost = $standardCost + $extraCost;
            
            // Aggiungi alla somma totale
            $totalCost += $totalResourceCost;
            
            $summary[] = [
                'id' => $resourceId,
                'name' => $resource->name,
                'role' => $resource->role,
                'standard_hours' => $standardHours,
                'extra_hours' => $extraHours,
                'standard_adjusted_rate' => $standardAdjustedRate,
                'extra_adjusted_rate' => $extraAdjustedRate,
                'standard_cost' => $standardCost,
                'extra_cost' => $extraCost,
                'total_cost' => $totalResourceCost,
            ];
        }
        
        return response()->json([
            'success' => true,
            'summary' => $summary,
            'total_cost' => $totalCost,
        ]);
    }
    
    /**
     * Get a summary of the project for API/AJAX requests.
     */
    public function getSummary(string $id)
    {
        $project = Project::with(['client', 'resources', 'activities.resource', 'areas'])->findOrFail($id);
        
        $activityStats = [
            'total' => $project->activities->count(),
            'completed' => $project->activities->where('status', 'completed')->count(),
            'in_progress' => $project->activities->where('status', 'in_progress')->count(),
            'pending' => $project->activities->where('status', 'pending')->count(),
        ];
        
        $resourceStats = $project->resources->map(function ($resource) use ($project) {
            $activities = $project->activities->where('resource_id', $resource->id);
            
            return [
                'id' => $resource->id,
                'name' => $resource->name,
                'hours' => $resource->pivot->hours,
                'hours_type' => $resource->pivot->hours_type,
                'cost' => $resource->pivot->cost,
                'activities_count' => $activities->count(),
                'completed_activities' => $activities->where('status', 'completed')->count(),
            ];
        });
        
        return response()->json([
            'success' => true,
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'status' => $project->status,
                'default_hours_type' => $project->default_hours_type,
                'client' => [
                    'id' => $project->client->id,
                    'name' => $project->client->name,
                ],
                'total_cost' => $project->total_cost,
                'progress' => $project->progress_percentage,
                'start_date' => $project->start_date ? $project->start_date->format('Y-m-d') : null,
                'end_date' => $project->end_date ? $project->end_date->format('Y-m-d') : null,
                'activity_stats' => $activityStats,
                'resource_stats' => $resourceStats,
                'areas_count' => $project->areas->count(),
            ],
        ]);
    }
}