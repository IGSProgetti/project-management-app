<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Client;
use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ProjectController extends Controller
{
    /**
     * Aggiorna il metodo index per mostrare progetti creati da tasks
     */
    public function index(Request $request)
    {
        $query = Project::with(['client']);
        
        // Filtro per origine (creati da tasks o normalmente)
        if ($request->has('created_from') && $request->created_from !== '') {
            if ($request->created_from === 'tasks') {
                $query->where('created_from_tasks', true);
            } else {
                $query->where('created_from_tasks', '!=', true)->orWhereNull('created_from_tasks');
            }
        }
        
        // Filtro per cliente
        if ($request->has('client') && $request->client !== '') {
            $query->where('client_id', $request->client);
        }
        
        $projects = $query->orderBy('created_at', 'desc')->get();
        $clients = Client::orderBy('name')->get();
        
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
     * Consolida un progetto creato da tasks
     */
    public function consolidate(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'cost_steps' => 'nullable|array',
            'cost_steps.*' => 'integer|between:1,8',
            'default_hours_type' => 'nullable|in:standard,extra'
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        $project = Project::findOrFail($id);
        
        // Verifica che il progetto sia stato creato da tasks
        if (!$project->created_from_tasks) {
            return redirect()->route('projects.index')
                ->with('error', 'Questo progetto non è stato creato da tasks e non può essere consolidato.');
        }
        
        try {
            DB::beginTransaction();
            
            // Prepara i dati per il consolidamento
            $consolidationData = array_filter([
                'description' => $request->description,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'cost_steps' => $request->cost_steps ?? [1,2,3,4,5,6,7,8],
                'default_hours_type' => $request->default_hours_type ?? 'standard'
            ]);
            
            // Consolida il progetto (assumendo che il metodo consolidate() sia implementato nel modello)
            $project->consolidate($consolidationData);
            
            // Log dell'operazione
            Log::info("Progetto consolidato", [
                'project_id' => $project->id,
                'project_name' => $project->name,
                'client_id' => $project->client_id,
                'user_id' => Auth::id()
            ]);
            
            DB::commit();
            
            return redirect()->route('projects.show', $project->id)
                ->with('success', 'Progetto consolidato con successo. Ora è un progetto ufficiale del sistema.');
                
        } catch (\Exception $e) {
            DB::rollback();
            
            return redirect()->route('projects.index')
                ->with('error', 'Errore durante il consolidamento del progetto: ' . $e->getMessage());
        }
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
     * Calculate costs for project resources.
     */
    public function calculateCosts(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_id' => 'required|exists:clients,id',
            'cost_steps' => 'nullable|array',
            'resources' => 'required|array',
            'resources.*.id' => 'required|exists:resources,id',
            'resources.*.standard_hours' => 'nullable|numeric|min:0',
            'resources.*.extra_hours' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $costSteps = $request->cost_steps ?? [1, 2, 3, 4, 5, 6, 7, 8];
        $resources = $request->resources;
        $results = [];
        $totalCost = 0;

        // Crea un progetto temporaneo per calcolare le tariffe
        $tempProject = new Project();
        $tempProject->cost_steps = $costSteps;
        $tempProject->client_id = $request->client_id;

        foreach ($resources as $resourceData) {
            $resource = Resource::find($resourceData['id']);
            if (!$resource) continue;

            $standardHours = floatval($resourceData['standard_hours'] ?? 0);
            $extraHours = floatval($resourceData['extra_hours'] ?? 0);

            $standardRate = $tempProject->calculateAdjustedRate($resource->selling_price);
            $extraRate = $tempProject->calculateAdjustedRate($resource->extra_selling_price ?: $resource->selling_price);

            $standardCost = $standardHours * $standardRate;
            $extraCost = $extraHours * $extraRate;
            $resourceTotalCost = $standardCost + $extraCost;

            $results[] = [
                'resource_id' => $resource->id,
                'resource_name' => $resource->name,
                'standard_hours' => $standardHours,
                'extra_hours' => $extraHours,
                'standard_rate' => $standardRate,
                'extra_rate' => $extraRate,
                'standard_cost' => $standardCost,
                'extra_cost' => $extraCost,
                'total_cost' => $resourceTotalCost
            ];

            $totalCost += $resourceTotalCost;
        }

        return response()->json([
            'success' => true,
            'results' => $results,
            'total_cost' => $totalCost
        ]);
    }

    /**
     * Get project summary for API requests.
     */
    public function getSummary(string $id)
    {
        $project = Project::with(['client', 'resources', 'activities', 'areas'])->findOrFail($id);
        
        // Statistiche attività
        $activityStats = [
            'total' => $project->activities->count(),
            'pending' => $project->activities->where('status', 'pending')->count(),
            'in_progress' => $project->activities->where('status', 'in_progress')->count(),
            'completed' => $project->activities->where('status', 'completed')->count(),
        ];
        
        // Statistiche risorse
        $resourceStats = [
            'total' => $project->resources->count(),
            'standard_hours' => $project->resources->where('pivot.hours_type', 'standard')->sum('pivot.hours'),
            'extra_hours' => $project->resources->where('pivot.hours_type', 'extra')->sum('pivot.hours'),
        ];
        
        return response()->json([
            'success' => true,
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'description' => $project->description,
                'status' => $project->status,
                'total_cost' => $project->total_cost,
                'progress_percentage' => $project->progress_percentage,
                'client' => $project->client->name,
                'start_date' => $project->start_date ? $project->start_date->format('Y-m-d') : null,
                'end_date' => $project->end_date ? $project->end_date->format('Y-m-d') : null,
                'activity_stats' => $activityStats,
                'resource_stats' => $resourceStats,
                'areas_count' => $project->areas->count(),
            ],
        ]);
    }
}