<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $clients = Client::withCount('projects')->get();
        return view('clients.index', compact('clients'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('clients.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'budget' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        Client::create($request->all());

        return redirect()->route('clients.index')
            ->with('success', 'Cliente creato con successo.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $client = Client::with([
            'projects.resources',
            'projects.activities.resource',
            'projects.areas'
        ])->findOrFail($id);
        
        // Carica i dati delle ore standard e extra per ogni progetto
        foreach ($client->projects as $project) {
            $project->append([
                'standard_actual_hours_by_resource',
                'extra_actual_hours_by_resource',
                'standard_resources',
                'extra_resources',
                'is_over_budget',
                'budget_used',
                'budget_used_percentage',
                'remaining_budget'
            ]);
        }
        
        // Calcolo delle ore totali per il cliente
        $totalStandardEstimatedHours = 0;
        $totalExtraEstimatedHours = 0;
        $totalStandardActualHours = 0;
        $totalExtraActualHours = 0;
        
        foreach ($client->projects as $project) {
            // Ore stimate dalle attività
            foreach ($project->activities as $activity) {
                $hours = $activity->estimated_minutes / 60;
                if ($activity->hours_type === 'standard') {
                    $totalStandardEstimatedHours += $hours;
                } else {
                    $totalExtraEstimatedHours += $hours;
                }
                
                // Ore effettive
                $actualHours = $activity->actual_minutes / 60;
                if ($activity->hours_type === 'standard') {
                    $totalStandardActualHours += $actualHours;
                } else {
                    $totalExtraActualHours += $actualHours;
                }
            }
        }
        
        // Calcolo le ore totali
        $totalEstimatedHours = $totalStandardEstimatedHours + $totalExtraEstimatedHours;
        $totalActualHours = $totalStandardActualHours + $totalExtraActualHours;
        
        // Calcolo percentuali
        $standardEstimatedPercentage = $totalEstimatedHours > 0 ? 
            round(($totalStandardEstimatedHours / $totalEstimatedHours) * 100) : 0;
        $extraEstimatedPercentage = $totalEstimatedHours > 0 ? 
            round(($totalExtraEstimatedHours / $totalEstimatedHours) * 100) : 0;
            
        $standardActualPercentage = $totalActualHours > 0 ? 
            round(($totalStandardActualHours / $totalActualHours) * 100) : 0;
        $extraActualPercentage = $totalActualHours > 0 ? 
            round(($totalExtraActualHours / $totalActualHours) * 100) : 0;
        
        // Calcolo efficienza (ore stimate vs effettive)
        $standardEfficiency = $totalStandardEstimatedHours > 0 ?
            round(($totalStandardActualHours / $totalStandardEstimatedHours) * 100) : 0;
        $extraEfficiency = $totalExtraEstimatedHours > 0 ?
            round(($totalExtraActualHours / $totalExtraEstimatedHours) * 100) : 0;
        $totalEfficiency = $totalEstimatedHours > 0 ?
            round(($totalActualHours / $totalEstimatedHours) * 100) : 0;
            
        $hoursStats = [
            'totalEstimatedHours' => $totalEstimatedHours,
            'totalActualHours' => $totalActualHours,
            'standardEstimatedHours' => $totalStandardEstimatedHours,
            'extraEstimatedHours' => $totalExtraEstimatedHours,
            'standardActualHours' => $totalStandardActualHours,
            'extraActualHours' => $totalExtraActualHours,
            'standardEstimatedPercentage' => $standardEstimatedPercentage,
            'extraEstimatedPercentage' => $extraEstimatedPercentage,
            'standardActualPercentage' => $standardActualPercentage,
            'extraActualPercentage' => $extraActualPercentage,
            'standardEfficiency' => $standardEfficiency,
            'extraEfficiency' => $extraEfficiency,
            'totalEfficiency' => $totalEfficiency
        ];
        
        return view('clients.show', compact('client', 'hoursStats'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $client = Client::findOrFail($id);
        return view('clients.edit', compact('client'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'budget' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $client = Client::findOrFail($id);
        $client->update($request->all());

        return redirect()->route('clients.index')
            ->with('success', 'Cliente aggiornato con successo.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $client = Client::findOrFail($id);
        
        // Verifica se ci sono progetti associati
        if ($client->projects()->count() > 0) {
            return redirect()->route('clients.index')
                ->with('error', 'Impossibile eliminare il cliente. Ci sono progetti associati.');
        }
        
        $client->delete();
        
        return redirect()->route('clients.index')
            ->with('success', 'Cliente eliminato con successo.');
    }
}
