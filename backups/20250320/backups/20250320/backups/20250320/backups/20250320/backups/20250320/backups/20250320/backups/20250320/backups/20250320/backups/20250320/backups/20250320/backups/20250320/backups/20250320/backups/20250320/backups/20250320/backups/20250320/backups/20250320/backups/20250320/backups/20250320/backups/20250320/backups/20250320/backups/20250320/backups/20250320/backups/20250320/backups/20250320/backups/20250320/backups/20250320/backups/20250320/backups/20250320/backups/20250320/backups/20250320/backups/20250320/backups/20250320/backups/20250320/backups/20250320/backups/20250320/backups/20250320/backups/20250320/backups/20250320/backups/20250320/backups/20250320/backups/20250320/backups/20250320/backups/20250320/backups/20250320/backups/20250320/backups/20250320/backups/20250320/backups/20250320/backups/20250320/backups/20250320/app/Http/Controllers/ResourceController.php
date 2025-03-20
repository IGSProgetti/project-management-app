<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Resource;
use Illuminate\Support\Facades\Validator;

class ResourceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $resources = Resource::with('activities')->get();
        
        // Carica i dati calcolati per ogni risorsa
        $resources->each(function ($resource) {
            $resource->append([
                'standard_hours_per_year',
                'extra_hours_per_year',
                
                'total_standard_estimated_minutes',
                'total_standard_actual_minutes',
                'total_extra_estimated_minutes',
                'total_extra_actual_minutes',
                
                'total_standard_estimated_hours',
                'total_standard_actual_hours',
                'total_extra_estimated_hours',
                'total_extra_actual_hours',
                
                'remaining_standard_estimated_hours',
                'remaining_standard_actual_hours',
                'remaining_extra_estimated_hours',
                'remaining_extra_actual_hours'
            ]);
        });
        
        return view('resources.index', compact('resources'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('resources.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'role' => 'required|string|max:255',
            'monthly_compensation' => 'required|numeric|min:0',
            'working_days_year' => 'required|integer|min:1|max:365',
            'working_hours_day' => 'required|numeric|min:0.5|max:24',
            'extra_hours_day' => 'nullable|numeric|min:0|max:24',
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'extra_cost_price' => 'nullable|numeric|min:0',
            'extra_selling_price' => 'nullable|numeric|min:0',
            'remuneration_breakdown' => 'required|json',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        Resource::create($request->all());

        return redirect()->route('resources.index')
            ->with('success', 'Risorsa creata con successo.');
    }

    /**
 * Display the specified resource.
 */
public function show(string $id)
{
    $resource = Resource::with(['projects.client', 'activities'])->findOrFail($id);
    
    // Calcolo delle ore disponibili e utilizzate
    $resource->append([
        'standard_hours_per_year',
        'extra_hours_per_year',
        
        'total_standard_estimated_minutes',
        'total_standard_actual_minutes',
        'total_extra_estimated_minutes',
        'total_extra_actual_minutes',
        
        'total_standard_estimated_hours',
        'total_standard_actual_hours',
        'total_extra_estimated_hours',
        'total_extra_actual_hours',
        
        'remaining_standard_estimated_hours',
        'remaining_standard_actual_hours',
        'remaining_extra_estimated_hours',
        'remaining_extra_actual_hours'
    ]);

    
    // Percentuali di utilizzo per ore standard
    $standardEstimatedUsagePercentage = min(100, $resource->standard_hours_per_year > 0 ? 
        ($resource->total_standard_estimated_hours / $resource->standard_hours_per_year) * 100 : 0);
    
    $standardActualUsagePercentage = min(100, $resource->standard_hours_per_year > 0 ? 
        ($resource->total_standard_actual_hours / $resource->standard_hours_per_year) * 100 : 0);
    
    // Percentuali di utilizzo per ore extra
    $extraEstimatedUsagePercentage = min(100, $resource->extra_hours_per_year > 0 ? 
        ($resource->total_extra_estimated_hours / $resource->extra_hours_per_year) * 100 : 0);
    
    $extraActualUsagePercentage = min(100, $resource->extra_hours_per_year > 0 ? 
        ($resource->total_extra_actual_hours / $resource->extra_hours_per_year) * 100 : 0);
    
    return view('resources.show', compact(
        'resource', 
        'standardEstimatedUsagePercentage',
        'standardActualUsagePercentage',
        'extraEstimatedUsagePercentage',
        'extraActualUsagePercentage'
    ));
}

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $resource = Resource::findOrFail($id);
        return view('resources.edit', compact('resource'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'role' => 'required|string|max:255',
            'monthly_compensation' => 'required|numeric|min:0',
            'working_days_year' => 'required|integer|min:1|max:365',
            'working_hours_day' => 'required|numeric|min:0.5|max:24',
            'extra_hours_day' => 'nullable|numeric|min:0|max:24',
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'extra_cost_price' => 'nullable|numeric|min:0',
            'extra_selling_price' => 'nullable|numeric|min:0',
            'remuneration_breakdown' => 'required|json',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $resource = Resource::findOrFail($id);
        
        // Gestisce il checkbox is_active
        $data = $request->all();
        $data['is_active'] = $request->has('is_active') ? true : false;
        
        $resource->update($data);

        return redirect()->route('resources.index')
            ->with('success', 'Risorsa aggiornata con successo.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $resource = Resource::findOrFail($id);
        
        // Controlla se ci sono attività o progetti associati
        if ($resource->activities()->count() > 0) {
            return redirect()->route('resources.index')
                ->with('error', 'Impossibile eliminare la risorsa. Ci sono attività associate.');
        }
        
        if ($resource->projects()->count() > 0) {
            return redirect()->route('resources.index')
                ->with('error', 'Impossibile eliminare la risorsa. Ci sono progetti associati.');
        }
        
        $resource->delete();
        
        return redirect()->route('resources.index')
            ->with('success', 'Risorsa eliminata con successo.');
    }
    
    /**
     * Calculate costs based on input parameters.
     */
    public function calculateCosts(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'monthly_compensation' => 'required|numeric|min:0',
            'working_days_year' => 'required|integer|min:1|max:365',
            'working_hours_day' => 'required|numeric|min:0.5|max:24',
            'extra_hours_day' => 'nullable|numeric|min:0|max:24',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Calcolo per le ore standard
        $yearlyCompensation = $request->monthly_compensation * 12;
        $yearlyHours = $request->working_days_year * $request->working_hours_day;
        $costPrice = $yearlyHours > 0 ? $yearlyCompensation / $yearlyHours : 0;
        $sellingPrice = $this->calculateSellingPrice($costPrice);

        // Calcolo per le ore extra (se specificate)
        $extraCostPrice = null;
        $extraSellingPrice = null;
        
        if ($request->has('extra_hours_day') && $request->extra_hours_day > 0) {
            // Calcola il costo orario extra con maggiorazione del 20%
            $extraCostPrice = $costPrice * 1.2;
            $extraSellingPrice = $this->calculateSellingPrice($extraCostPrice);
        }

        $remunerationSchema = [
            'Costo struttura' => 25,
            'Utile gestore azienda' => 12.5,
            'Utile IGS' => 12.5,
            'Compenso professionista' => 20,
            'Bonus professionista' => 5,
            'Gestore società' => 3,
            'Chi porta il lavoro' => 8,
            'Network IGS' => 14
        ];

        $breakdown = $this->calculateBreakdown($sellingPrice, $remunerationSchema);

        $response = [
            'success' => true,
            'costPrice' => round($costPrice, 2),
            'sellingPrice' => round($sellingPrice, 2),
            'breakdown' => $breakdown
        ];
        
        // Aggiungi dati per ore extra se calcolati
        if ($extraCostPrice !== null && $extraSellingPrice !== null) {
            $response['extraCostPrice'] = round($extraCostPrice, 2);
            $response['extraSellingPrice'] = round($extraSellingPrice, 2);
        }

        return response()->json($response);
    }

    /**
     * Calcola il prezzo di vendita con un markup dinamico.
     */
    private function calculateSellingPrice($costPrice, $baseMarkup = 5)
    {
        // Logica per calcolare il prezzo di vendita basato sul costo
        return $costPrice * $baseMarkup;
    }

    /**
     * Calcola il breakdown remunerativo in base al prezzo di vendita.
     */
    private function calculateBreakdown($sellingPrice, $remunerationSchema)
    {
        $breakdown = [];
        foreach ($remunerationSchema as $key => $percentage) {
            $breakdown[$key] = ($sellingPrice * $percentage) / 100;
        }
        return $breakdown;
    }
    
    /**
     * Get resources assigned to a specific project.
     */
    public function getByProject(string $projectId)
    {
        $resources = Resource::whereHas('projects', function($query) use ($projectId) {
            $query->where('projects.id', $projectId);
        })->with(['projects' => function($query) use ($projectId) {
            $query->where('projects.id', $projectId);
        }])->get();
        
        return response()->json([
            'success' => true,
            'resources' => $resources
        ]);
    }
    
    /**
     * Toggle resource active status.
     */
    public function toggleStatus(string $id)
    {
        $resource = Resource::findOrFail($id);
        $resource->is_active = !$resource->is_active;
        $resource->save();
        
        return redirect()->back()
            ->with('success', 'Stato della risorsa aggiornato con successo.');
    }
    
    /**
     * Get resource statistics and summary.
     */
    public function statistics()
    {
        $totalResources = Resource::count();
        $activeResources = Resource::where('is_active', true)->count();
        
        $resourcesByRole = Resource::select('role')
            ->selectRaw('count(*) as count')
            ->groupBy('role')
            ->get();
            
        $averageCostPrice = Resource::avg('cost_price');
        $averageSellingPrice = Resource::avg('selling_price');
        
        return view('resources.statistics', compact(
            'totalResources', 
            'activeResources', 
            'resourcesByRole', 
            'averageCostPrice', 
            'averageSellingPrice'
        ));
    }
    
    /**
     * Mostra una dashboard con la disponibilità di ore per tutte le risorse.
     */
    public function hoursAvailability()
    {
        $resources = Resource::with('activities')->where('is_active', true)->get();
        
        // Carica i dati calcolati per ogni risorsa
        $resources->each(function ($resource) {
            $resource->append([
                'standard_hours_per_year',
                'extra_hours_per_year',
                'total_standard_estimated_hours',
                'total_standard_actual_hours', 
                'total_extra_estimated_hours',
                'total_extra_actual_hours',
                'remaining_standard_estimated_hours',
                'remaining_standard_actual_hours',
                'remaining_extra_estimated_hours',
                'remaining_extra_actual_hours'
            ]);
        });
        
        return view('resources.hours-availability', compact('resources'));
    }

    public function fixLegacyHoursData(Resource $resource)
{
    try {
        $resource->correctLegacyHoursData();
        return response()->json([
            'success' => true,
            'message' => 'Dati delle ore migrati con successo al nuovo formato.'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Errore durante la migrazione dei dati: ' . $e->getMessage()
        ], 500);
    }
}
}