<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Resource;
use App\Models\ProjectResourceTreasure;
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
                'remaining_extra_actual_hours',
                
                // Aggiungi anche i campi del tesoretto
                'treasure_usage_percentage'
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
            // Campi tesoretto
            'treasure_days' => 'nullable|integer|min:0',
            'treasure_hours_per_day' => 'nullable|numeric|min:0',
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
            'remaining_extra_actual_hours',
            
            // Campi tesoretto
            'treasure_usage_percentage'
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
            // Campi tesoretto
            'treasure_days' => 'nullable|integer|min:0',
            'treasure_hours_per_day' => 'nullable|numeric|min:0',
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

        // Controlla se ci sono allocazioni di tesoretto
        if ($resource->treasureAllocations()->count() > 0) {
            return redirect()->route('resources.index')
                ->with('error', 'Impossibile eliminare la risorsa. Ci sono allocazioni di tesoretto attive.');
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
    private function calculateSellingPrice($costPrice)
    {
        // Markup del 80% per ottenere il prezzo base
        $baseSellingPrice = $costPrice * 1.8;
        
        // Aggiungi un markup variabile in base al costo
        if ($costPrice > 50) {
            return $baseSellingPrice * 1.25; // 25% aggiuntivo per costi alti
        } elseif ($costPrice > 30) {
            return $baseSellingPrice * 1.15; // 15% aggiuntivo per costi medi
        } else {
            return $baseSellingPrice * 1.10; // 10% aggiuntivo per costi bassi
        }
    }

    /**
     * Calcola il breakdown della remunerazione.
     */
    private function calculateBreakdown($sellingPrice, $schema)
    {
        $breakdown = [];
        $remaining = $sellingPrice;
        
        foreach ($schema as $category => $percentage) {
            $amount = $sellingPrice * ($percentage / 100);
            $breakdown[$category] = [
                'percentage' => $percentage,
                'amount' => round($amount, 2)
            ];
            $remaining -= $amount;
        }
        
        // Il residuo va al compenso del professionista
        if (isset($breakdown['Compenso professionista'])) {
            $breakdown['Compenso professionista']['amount'] += round($remaining, 2);
        }
        
        return $breakdown;
    }

    // ============================================
    // METODI PER LA GESTIONE DEL TESORETTO
    // ============================================

    /**
     * Aggiorna il tesoretto di una risorsa
     */
    public function updateTreasure(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'treasure_days' => 'required|integer|min:0',
            'treasure_hours_per_day' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $resource = Resource::findOrFail($id);
        
        try {
            $resource->updateTreasure(
                $request->treasure_days,
                $request->treasure_hours_per_day
            );

            return response()->json([
                'success' => true,
                'message' => 'Tesoretto aggiornato con successo.',
                'data' => [
                    'treasure_total_hours' => $resource->treasure_total_hours,
                    'treasure_available_hours' => $resource->treasure_available_hours,
                    'treasure_usage_percentage' => $resource->treasure_usage_percentage
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'aggiornamento del tesoretto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ottiene le informazioni del tesoretto per una risorsa
     */
    public function getTreasureInfo(string $id)
    {
        $resource = Resource::with('treasureAllocations.project')->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => [
                'treasure_days' => $resource->treasure_days,
                'treasure_hours_per_day' => $resource->treasure_hours_per_day,
                'treasure_total_hours' => $resource->treasure_total_hours,
                'treasure_available_hours' => $resource->treasure_available_hours,
                'treasure_usage_percentage' => $resource->treasure_usage_percentage,
                'allocations' => $resource->treasureAllocations->map(function ($allocation) {
                    return [
                        'project_id' => $allocation->project_id,
                        'project_name' => $allocation->project->name,
                        'allocated_hours' => $allocation->allocated_treasure_hours,
                        'hourly_rate' => $allocation->treasure_hourly_rate,
                        'total_cost' => $allocation->treasure_total_cost,
                    ];
                })
            ]
        ]);
    }
}