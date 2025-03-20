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