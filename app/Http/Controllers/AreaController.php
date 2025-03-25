<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;

class AreaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Assicuriamoci che il modello carica solo i campi che esistono nella tabella
        $areas = Area::with('project')->get();
        
        // Verifichiamo se i nuovi campi esistono
        $hasEstimatedMinutes = Schema::hasColumn('areas', 'estimated_minutes');
        $hasActualMinutes = Schema::hasColumn('areas', 'actual_minutes');
        
        $projects = Project::all();
        return view('areas.index', compact('areas', 'projects', 'hasEstimatedMinutes', 'hasActualMinutes'));
    }

    // 

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $projects = Project::all();
        return view('areas.create', compact('projects'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'project_id' => 'required|exists:projects,id',
            'estimated_minutes' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Verifica che il progetto esista e abbia abbastanza minuti disponibili
        $project = Project::findOrFail($request->project_id);
        
        // Creazione dell'area
        $area = new Area();
        $area->fill($request->all());
        $area->actual_minutes = 0; // Inizializza i minuti effettivi a zero
        $area->save();

        return redirect()->route('areas.show', $area->id)
            ->with('success', 'Area creata con successo.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $area = Area::with(['project', 'activities.resource', 'activities.tasks'])->findOrFail($id);
        return view('areas.show', compact('area'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $area = Area::findOrFail($id);
        $projects = Project::all();
        return view('areas.edit', compact('area', 'projects'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'project_id' => 'required|exists:projects,id',
            'estimated_minutes' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $area = Area::findOrFail($id);
        $originalProjectId = $area->project_id;
        
        $area->fill($request->all());
        $area->save();

        return redirect()->route('areas.show', $area->id)
            ->with('success', 'Area aggiornata con successo.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $area = Area::findOrFail($id);
        
        // Verifica se ci sono attività associate
        if ($area->activities()->count() > 0) {
            return redirect()->route('areas.index')
                ->with('error', 'Impossibile eliminare l\'area. Ci sono attività associate.');
        }
        
        $area->delete();
        
        return redirect()->route('areas.index')
            ->with('success', 'Area eliminata con successo.');
    }
    
    /**
     * Get areas by project for AJAX requests.
     */
    public function byProject(string $projectId)
    {
        $areas = Area::where('project_id', $projectId)->get();
        
        return response()->json([
            'success' => true,
            'areas' => $areas
        ]);
    }
    
    /**
     * Update the area's actual minutes from activities.
     */
    public function updateMinutes(string $id)
    {
        $area = Area::findOrFail($id);
        $area->updateActualMinutesFromActivities();
        
        return redirect()->back()
            ->with('success', 'Minuti aggiornati con successo.');
    }
}