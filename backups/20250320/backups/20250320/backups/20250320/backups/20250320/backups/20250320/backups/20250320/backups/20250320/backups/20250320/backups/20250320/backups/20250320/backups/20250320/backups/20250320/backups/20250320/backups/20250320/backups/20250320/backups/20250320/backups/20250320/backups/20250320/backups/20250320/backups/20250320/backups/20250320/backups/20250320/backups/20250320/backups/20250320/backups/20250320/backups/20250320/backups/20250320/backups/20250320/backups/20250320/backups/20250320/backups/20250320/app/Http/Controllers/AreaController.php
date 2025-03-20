<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AreaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
{
    $areas = Area::with('project')->get();
    $projects = \App\Models\Project::all();
    return view('areas.index', compact('areas', 'projects'));
}

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
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        Area::create($request->all());

        return redirect()->route('areas.index')
            ->with('success', 'Area creata con successo.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $area = Area::with(['project', 'activities.resource'])->findOrFail($id);
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
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $area = Area::findOrFail($id);
        $area->update($request->all());

        return redirect()->route('areas.index')
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
}
