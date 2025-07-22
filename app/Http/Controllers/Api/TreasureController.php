<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Resource;
use App\Models\ProjectResourceTreasure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class TreasureController extends Controller
{
    /**
     * Salva le allocazioni del tesoretto per un progetto
     */
    public function storeTreasureAllocations(Request $request, $projectId)
    {
        $validator = Validator::make($request->all(), [
            'allocations' => 'required|array',
            'allocations.*.resource_id' => 'required|exists:resources,id',
            'allocations.*.allocated_hours' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $project = Project::findOrFail($projectId);

        DB::beginTransaction();
        try {
            foreach ($request->allocations as $allocation) {
                $resource = Resource::findOrFail($allocation['resource_id']);
                $allocatedHours = $allocation['allocated_hours'];

                // Verifica che la risorsa abbia abbastanza tesoretto disponibile
                if ($allocatedHours > $resource->treasure_available_hours) {
                    throw new \Exception("Risorsa {$resource->name}: ore richieste ({$allocatedHours}) superiori al tesoretto disponibile ({$resource->treasure_available_hours})");
                }

                // Calcola la tariffa oraria del tesoretto usando gli step di costo del progetto
                $treasureRate = $this->calculateTreasureHourlyRate($resource, $project->cost_steps ?? []);

                // Alloca il tesoretto
                $resource->allocateTreasureToProject(
                    $projectId,
                    $allocatedHours,
                    $treasureRate
                );
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Allocazioni tesoretto salvate con successo',
                'data' => [
                    'project_id' => $projectId,
                    'allocations_count' => count($request->allocations)
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Rimuove tutte le allocazioni del tesoretto per un progetto
     */
    public function removeTreasureAllocations($projectId)
    {
        $project = Project::findOrFail($projectId);

        DB::beginTransaction();
        try {
            // Ottieni tutte le allocazioni per il progetto
            $allocations = ProjectResourceTreasure::where('project_id', $projectId)->get();

            foreach ($allocations as $allocation) {
                // Dealloca il tesoretto dalla risorsa
                $allocation->resource->deallocateTreasureFromProject($projectId);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Allocazioni tesoretto rimosse con successo',
                'data' => [
                    'project_id' => $projectId,
                    'removed_count' => $allocations->count()
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Ottiene le allocazioni del tesoretto per un progetto
     */
    public function getTreasureAllocations($projectId)
    {
        $project = Project::findOrFail($projectId);

        $allocations = ProjectResourceTreasure::with('resource')
            ->where('project_id', $projectId)
            ->get()
            ->map(function ($allocation) {
                return [
                    'resource_id' => $allocation->resource_id,
                    'resource_name' => $allocation->resource->name,
                    'allocated_hours' => $allocation->allocated_treasure_hours,
                    'hourly_rate' => $allocation->treasure_hourly_rate,
                    'total_cost' => $allocation->treasure_total_cost,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $allocations
        ]);
    }

    /**
     * Ottiene il tesoretto disponibile per tutte le risorse
     */
    public function getResourcesTreasure()
    {
        $resources = Resource::where('is_active', true)
            ->get(['id', 'name', 'role', 'selling_price', 'extra_selling_price', 
                   'treasure_days', 'treasure_hours_per_day', 'treasure_total_hours', 'treasure_available_hours'])
            ->map(function ($resource) {
                return [
                    'id' => $resource->id,
                    'name' => $resource->name,
                    'role' => $resource->role,
                    'selling_price' => $resource->selling_price,
                    'extra_selling_price' => $resource->extra_selling_price,
                    'treasure_total_hours' => $resource->treasure_total_hours,
                    'treasure_available_hours' => $resource->treasure_available_hours,
                    'treasure_usage_percentage' => $resource->treasure_usage_percentage,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $resources
        ]);
    }

    /**
     * Aggiorna il tesoretto di una risorsa
     */
    public function updateResourceTreasure(Request $request, $resourceId)
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

        $resource = Resource::findOrFail($resourceId);

        try {
            $resource->updateTreasure(
                $request->treasure_days,
                $request->treasure_hours_per_day
            );

            return response()->json([
                'success' => true,
                'message' => 'Tesoretto aggiornato con successo',
                'data' => [
                    'resource_id' => $resource->id,
                    'treasure_total_hours' => $resource->treasure_total_hours,
                    'treasure_available_hours' => $resource->treasure_available_hours,
                    'treasure_usage_percentage' => $resource->treasure_usage_percentage,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'aggiornamento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calcola la tariffa oraria del tesoretto applicando gli step di costo
     */
    private function calculateTreasureHourlyRate(Resource $resource, array $costSteps)
    {
        $baseRate = $resource->selling_price;

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

        return $baseRate * (1 - $totalDeduction / 100);
    }

    /**
     * Ottiene statistiche del tesoretto per dashboard
     */
    public function getTreasureStatistics()
    {
        $totalResources = Resource::where('is_active', true)->count();
        $resourcesWithTreasure = Resource::where('is_active', true)
            ->where('treasure_total_hours', '>', 0)
            ->count();

        $totalTreasureHours = Resource::where('is_active', true)
            ->sum('treasure_total_hours');

        $totalAllocatedHours = ProjectResourceTreasure::sum('allocated_treasure_hours');
        $totalAvailableHours = $totalTreasureHours - $totalAllocatedHours;

        $utilizationPercentage = $totalTreasureHours > 0 
            ? round(($totalAllocatedHours / $totalTreasureHours) * 100, 2)
            : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'total_resources' => $totalResources,
                'resources_with_treasure' => $resourcesWithTreasure,
                'total_treasure_hours' => $totalTreasureHours,
                'total_allocated_hours' => $totalAllocatedHours,
                'total_available_hours' => $totalAvailableHours,
                'utilization_percentage' => $utilizationPercentage,
            ]
        ]);
    }
}

// Rotte per la gestione del tesoretto
Route::prefix('api')->middleware(['web', 'auth'])->group(function () {
    
    // Gestione allocazioni tesoretto per progetti
    Route::post('/projects/{project}/treasure-allocations', [TreasureController::class, 'storeTreasureAllocations'])
        ->name('api.projects.treasure.store');
    
    Route::delete('/projects/{project}/treasure-allocations', [TreasureController::class, 'removeTreasureAllocations'])
        ->name('api.projects.treasure.remove');
    
    Route::get('/projects/{project}/treasure-allocations', [TreasureController::class, 'getTreasureAllocations'])
        ->name('api.projects.treasure.get');

    // Gestione tesoretto risorse
    Route::get('/resources/treasure', [TreasureController::class, 'getResourcesTreasure'])
        ->name('api.resources.treasure.index');
    
    Route::put('/resources/{resource}/treasure', [TreasureController::class, 'updateResourceTreasure'])
        ->name('api.resources.treasure.update');

    // Statistiche tesoretto
    Route::get('/treasure/statistics', [TreasureController::class, 'getTreasureStatistics'])
        ->name('api.treasure.statistics');
});


// Rotte per la gestione del tesoretto
Route::prefix('api')->middleware(['web', 'auth'])->group(function () {
    
    // Gestione allocazioni tesoretto per progetti
    Route::post('/projects/{project}/treasure-allocations', [TreasureController::class, 'storeTreasureAllocations'])
        ->name('api.projects.treasure.store');
    
    Route::delete('/projects/{project}/treasure-allocations', [TreasureController::class, 'removeTreasureAllocations'])
        ->name('api.projects.treasure.remove');
    
    Route::get('/projects/{project}/treasure-allocations', [TreasureController::class, 'getTreasureAllocations'])
        ->name('api.projects.treasure.get');

    // Gestione tesoretto risorse
    Route::get('/resources/treasure', [TreasureController::class, 'getResourcesTreasure'])
        ->name('api.resources.treasure.index');
    
    Route::put('/resources/{resource}/treasure', [TreasureController::class, 'updateResourceTreasure'])
        ->name('api.resources.treasure.update');

    // Statistiche tesoretto
    Route::get('/treasure/statistics', [TreasureController::class, 'getTreasureStatistics'])
        ->name('api.treasure.statistics');
});