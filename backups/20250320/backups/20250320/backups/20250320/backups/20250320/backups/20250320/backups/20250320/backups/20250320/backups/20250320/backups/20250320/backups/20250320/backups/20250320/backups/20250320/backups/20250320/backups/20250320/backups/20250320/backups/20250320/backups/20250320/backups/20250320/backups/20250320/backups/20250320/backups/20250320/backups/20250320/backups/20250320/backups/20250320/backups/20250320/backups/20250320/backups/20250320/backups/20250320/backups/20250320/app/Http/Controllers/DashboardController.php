<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Project;
use App\Models\Resource;
use App\Models\Activity;
use App\Models\Task;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display the dashboard.
     */
    public function index()
    {
        // Statistiche generali
        $stats = [
            'clients' => Client::count(),
            'projects' => Project::count(),
            'resources' => Resource::count(),
            'activities' => Activity::count(),
        ];
        
        // Progetti recenti
        $recentProjects = Project::with('client')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
        
        // Progetti in corso
        $activeProjects = Project::with(['client', 'activities'])
            ->where('status', 'in_progress')
            ->take(5)
            ->get();
        
        // Attività in scadenza
        $upcomingActivities = Activity::with(['project', 'resource'])
            ->whereIn('status', ['pending', 'in_progress'])
            ->whereNotNull('due_date')
            ->orderBy('due_date')
            ->take(5)
            ->get();
        
        // Risorse più utilizzate
        $topResources = Resource::withCount('activities')
            ->orderBy('activities_count', 'desc')
            ->take(5)
            ->get();
        
        return view('dashboard.index', compact(
            'stats',
            'recentProjects',
            'activeProjects',
            'upcomingActivities',
            'topResources'
        ));
    }
}