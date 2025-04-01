<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Aggiungi questa rotta nel file routes/api.php

Route::get('/resources/{id}/availability', [App\Http\Controllers\ResourceController::class, 'getAvailability'])->name('api.resource-availability');

// Aggiungi questa rotta al file routes/api.php

Route::get('/activity-resources/{activityId}', function($activityId) {
    $activity = \App\Models\Activity::with('resources')->findOrFail($activityId);
    
    $resources = [];
    
    // Se l'attività ha risorse multiple, restituisci quelle
    if ($activity->has_multiple_resources && $activity->resources->count() > 0) {
        $resources = $activity->resources;
    } 
    // Altrimenti, se ha una singola risorsa, restituisci quella
    elseif ($activity->resource_id) {
        $resource = \App\Models\Resource::find($activity->resource_id);
        if ($resource) {
            $resources = [$resource];
        }
    }
    
    return response()->json([
        'success' => true,
        'resources' => $resources
    ]);
});
