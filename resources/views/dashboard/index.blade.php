@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="dashboard-container">
    <!-- Statistiche generali -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="stats-icon">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="stats-info">
                        <h5>Clienti</h5>
                        <h3>{{ $stats['clients'] }}</h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="stats-icon">
                        <i class="fas fa-project-diagram"></i>
                    </div>
                    <div class="stats-info">
                        <h5>Progetti</h5>
                        <h3>{{ $stats['projects'] }}</h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="stats-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stats-info">
                        <h5>Risorse</h5>
                        <h3>{{ $stats['resources'] }}</h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="stats-icon">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div class="stats-info">
                        <h5>Attività</h5>
                        <h3>{{ $stats['activities'] }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Progetti attivi e recenti -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Progetti in corso</h5>
                    <a href="{{ route('projects.index') }}" class="btn btn-sm btn-primary">Vedi tutti</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Cliente</th>
                                    <th>Progresso</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($activeProjects as $project)
                                <tr>
                                    <td>{{ $project->name }}</td>
                                    <td>{{ $project->client->name }}</td>
                                    <td>
                                        <div class="progress">
                                            <div class="progress-bar" role="progressbar" style="width: {{ $project->progress_percentage }}%">
                                                {{ $project->progress_percentage }}%
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="{{ route('projects.show', $project->id) }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center">Nessun progetto attivo</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Progetti recenti</h5>
                    <a href="{{ route('projects.index') }}" class="btn btn-sm btn-primary">Vedi tutti</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Cliente</th>
                                    <th>Creato il</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentProjects as $project)
                                <tr>
                                    <td>{{ $project->name }}</td>
                                    <td>{{ $project->client->name }}</td>
                                    <td>{{ $project->created_at->format('d/m/Y') }}</td>
                                    <td>
                                        <a href="{{ route('projects.show', $project->id) }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center">Nessun progetto recente</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Attività in scadenza e risorse più utilizzate -->
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Attività in scadenza</h5>
                    <a href="{{ route('activities.index') }}" class="btn btn-sm btn-primary">Vedi tutte</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Progetto</th>
                                    <th>Scadenza</th>
                                    <th>Stato</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($upcomingActivities as $activity)
                                <tr>
                                    <td>{{ $activity->name }}</td>
                                    <td>{{ $activity->project->name }}</td>
                                    <td>{{ $activity->due_date ? $activity->due_date->format('d/m/Y') : 'N/D' }}</td>
                                    <td>
                                        @if($activity->status == 'pending')
                                            <span class="badge bg-warning">In attesa</span>
                                        @elseif($activity->status == 'in_progress')
                                            <span class="badge bg-primary">In corso</span>
                                        @elseif($activity->status == 'completed')
                                            <span class="badge bg-success">Completata</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center">Nessuna attività in scadenza</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Risorse più utilizzate</h5>
                    <a href="{{ route('resources.index') }}" class="btn btn-sm btn-primary">Vedi tutte</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Ruolo</th>
                                    <th>Attività</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topResources as $resource)
                                <tr>
                                    <td>{{ $resource->name }}</td>
                                    <td>{{ $resource->role }}</td>
                                    <td>{{ $resource->activities_count }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="text-center">Nessuna risorsa trovata</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Widget da aggiungere alla dashboard per monitorare gli elementi creati da tasks -->

<div class="col-md-6 col-lg-4 mb-4">
    <div class="card h-100 border-warning">
        <div class="card-header bg-warning text-dark">
            <h6 class="card-title mb-0">
                <i class="fas fa-exclamation-triangle"></i> Elementi da Consolidare
            </h6>
        </div>
        <div class="card-body">
            <div class="row text-center">
                <div class="col-6">
                    <div class="border-end">
                        <h4 class="text-warning mb-0" id="clientsFromTasks">-</h4>
                        <small class="text-muted">Clienti da Tasks</small>
                    </div>
                </div>
                <div class="col-6">
                    <h4 class="text-warning mb-0" id="projectsFromTasks">-</h4>
                    <small class="text-muted">Progetti da Tasks</small>
                </div>
            </div>
            
            <hr class="my-3">
            
            <div class="row text-center">
                <div class="col-12">
                    <h5 class="text-info mb-0" id="tasksToReassign">-</h5>
                    <small class="text-muted">Tasks da Riassegnare</small>
                </div>
            </div>
        </div>
        <div class="card-footer bg-light">
            <div class="row">
                <div class="col-6">
                    <a href="{{ route('clients.index') }}?created_from=tasks" class="btn btn-outline-warning btn-sm w-100">
                        <i class="fas fa-users"></i> Clienti
                    </a>
                </div>
                <div class="col-6">
                    <a href="{{ route('projects.index') }}?created_from=tasks" class="btn btn-outline-warning btn-sm w-100">
                        <i class="fas fa-project-diagram"></i> Progetti
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Script da aggiungere alla dashboard per caricare i dati
document.addEventListener('DOMContentLoaded', function() {
    loadTasksCreatedStats();
    
    // Aggiorna ogni 5 minuti
    setInterval(loadTasksCreatedStats, 300000);
});

function loadTasksCreatedStats() {
    // Carica statistiche clienti
    fetch('/api/clients/tasks-created-stats')
        .then(response => response.json())
        .then(data => {
            document.getElementById('clientsFromTasks').textContent = data.pending_consolidation || 0;
        })
        .catch(error => {
            console.error('Errore caricamento stats clienti:', error);
            document.getElementById('clientsFromTasks').textContent = '?';
        });
    
    // Carica statistiche progetti
    fetch('/api/projects/tasks-created-stats')
        .then(response => response.json())
        .then(data => {
            document.getElementById('projectsFromTasks').textContent = data.pending_consolidation || 0;
            document.getElementById('tasksToReassign').textContent = data.total_tasks_to_reassign || 0;
        })
        .catch(error => {
            console.error('Errore caricamento stats progetti:', error);
            document.getElementById('projectsFromTasks').textContent = '?';
            document.getElementById('tasksToReassign').textContent = '?';
        });
}
</script>

@endsection

@push('styles')
<style>
    .stats-card {
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease;
    }
    
    .stats-card:hover {
        transform: translateY(-5px);
    }
    
    .stats-card .card-body {
        display: flex;
        align-items: center;
        padding: 1.5rem;
    }
    
    .stats-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: #f0f8ff;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 1rem;
        font-size: 1.5rem;
        color: #2196F3;
    }
    
    .stats-info h5 {
        margin: 0;
        color: #666;
        font-size: 1rem;
    }
    
    .stats-info h3 {
        margin: 0;
        font-size: 1.8rem;
        font-weight: 600;
        color: #333;
    }
    
    .progress {
        height: 8px;
        border-radius: 4px;
    }
</style>
@endpush