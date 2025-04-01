<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResourceUserMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        
        // Permetti agli amministratori di passare sempre
        if ($user && $user->is_admin) {
            return $next($request);
        }
        
        // Gli utenti risorsa devono avere una risorsa associata
        if (!$user || !$user->resource_id || !$user->resource || !$user->resource->is_active) {
            abort(403, 'Accesso negato. Devi essere un utente risorsa con una risorsa attiva associata.');
        }
        
        return $next($request);
    }
}