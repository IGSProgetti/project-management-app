<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Resource;
use App\Models\Area;
use App\Models\Activity;
use Illuminate\Http\Request;

class CalendarApiController extends Controller
{
    /**
     * Ottieni tutti i progetti con il nome del cliente
     */
    public function getProjects()
    {
        $projects = Project::with('client')
            ->get()
            ->map(function($project) {
                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'client_name' => $project->client->name ?? 'N/D'
                ];
            });
        
        return response()->json($projects);
    }
    
    /**
     * Ottieni tutte le risorse attive
     */
    public function getResources()
    {
        $resources = Resource::where('is_active', true)
            ->get()
            ->map(function($resource) {
                return [
                    'id' => $resource->id,
                    'name' => $resource->name,
                    'role' => $resource->role
                ];
            });
        
        return response()->json($resources);
    }

    /**
     * Ottieni tutti i clienti
     */
    public function getClients()
    {
        $clients = \App\Models\Client::all()
            ->map(function($client) {
                return [
                    'id' => $client->id,
                    'name' => $client->name,
                    'created_from_tasks' => $client->created_from_tasks ?? false
                ];
            });
        
        return response()->json($clients);
    }
    
    /**
     * Ottieni i progetti di un cliente specifico
     */
    public function getProjectsByClient($clientId)
    {
        $projects = \App\Models\Project::where('client_id', $clientId)
            ->get()
            ->map(function($project) {
                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'created_from_tasks' => $project->created_from_tasks ?? false
                ];
            });
        
        return response()->json(['projects' => $projects]);
    }
    
    /**
     * Ottieni le aree di un progetto specifico
     */
    public function getAreasByProject($projectId)
    {
        $areas = Area::where('project_id', $projectId)
            ->get()
            ->map(function($area) {
                return [
                    'id' => $area->id,
                    'name' => $area->name
                ];
            });
        
        return response()->json(['areas' => $areas]);
    }
    
    /**
     * Ottieni le attività di un progetto specifico
     */
    public function getActivitiesByProject($projectId)
    {
        $activities = Activity::whereHas('area', function($query) use ($projectId) {
                $query->where('project_id', $projectId);
            })
            ->get()
            ->map(function($activity) {
                return [
                    'id' => $activity->id,
                    'name' => $activity->name
                ];
            });
        
        return response()->json(['activities' => $activities]);
    }
}