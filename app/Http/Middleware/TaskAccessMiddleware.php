<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;

class TaskAccessMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        
        // Gli amministratori hanno accesso a tutti i task
        if ($user->is_admin) {
            return $next($request);
        }
        
        // Ottieni l'ID del task dalla rotta
        $taskId = $request->route('task');
        
        // Se non c'è un ID task, passa alla richiesta successiva
        if (!$taskId) {
            return $next($request);
        }
        
        $task = Task::find($taskId);
        
        // Se il task non esiste, passa alla richiesta successiva
        if (!$task) {
            return $next($request);
        }
        
        $resourceId = $user->resource_id;
        $activity = $task->activity;
        
        // Se l'attività non esiste, nega l'accesso
        if (!$activity) {
            return redirect()->route('home')->with('error', 'Non hai accesso a questo task.');
        }
        
        $hasAccess = false;
        
        // Controlla se l'utente è la risorsa principale dell'attività
        if ($activity->resource_id == $resourceId) {
            $hasAccess = true;
        } 
        // Controlla se l'utente è una delle risorse multiple dell'attività
        elseif ($activity->has_multiple_resources) {
            $hasAccess = $activity->resources()->where('resources.id', $resourceId)->exists();
        }
        
        if (!$hasAccess) {
            return redirect()->route('home')->with('error', 'Non hai accesso a questo task.');
        }
        
        return $next($request);
    }
}