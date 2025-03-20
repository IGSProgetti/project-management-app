@extends('layouts.app')

@section('title', 'Dettagli Progetto')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1>{{ $project->name }}</h1>
            <p class="text-muted">Cliente: {{ $project->client->name }}</p>
        </div>
        <div class="col-md-6 text-end">
            <a href="{{ route('activities.create') }}" class="btn btn-success">
                <i class="fas fa-plus"></i> Nuova Attività
            </a>
            <a href="{{ route('projects.edit', $project->id) }}" class="btn btn-warning">
                <i class="fas fa-edit"></i> Modifica
            </a>
            <a href="{{ route('projects.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Indietro
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Informazioni Progetto</h5>
                </div>
                <div class="card-body">
                    <p><strong>Descrizione:</strong> {{ $project->description ?? 'Nessuna descrizione' }}</p>
                    <p>
                        <strong>Stato:</strong>
                        @if($project->status == 'pending')
                            <span class="badge bg-warning">In attesa</span>
                        @elseif($project->status == 'in_progress')
                            <span class="badge bg-primary">In corso</span>
                        @elseif($project->status == 'completed')
                            <span class="badge bg-success">Completato</span>
                        @elseif($project->status == 'on_hold')
                            <span class="badge bg-secondary">In pausa</span>
                        @endif
                    </p>
                    <p><strong>Data Inizio:</strong> {{ $project->start_date ? $project->start_date->format('d/m/Y') : 'Non specificata' }}</p>
                    <p><strong>Data Fine:</strong> {{ $project->end_date ? $project->end_date->format('d/m/Y') : 'Non specificata' }}</p>
                    <p><strong>Costo Totale:</strong> {{ number_format($project->total_cost, 2) }} €</p>
                    <div class="progress mb-2" style="height: 15px;">
                        <div class="progress-bar {{ $project->progress_percentage > 90 ? 'bg-success' : 'bg-primary' }}" role="progressbar" style="width: {{ $project->progress_percentage }}%" aria-valuenow="{{ $project->progress_percentage }}" aria-valuemin="0" aria-valuemax="100">{{ $project->progress_percentage }}%</div>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Risorse Assegnate</h5>
                </div>
                <div class="card-body">
                    @if($project->resources->count() > 0)
                        <div class="list-group">
                            @foreach($project->resources as $resource)
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">{{ $resource->name }}</h6>
                                        <small>{{ $resource->pivot->hours }} ore</small>
                                    </div>
                                    <p class="mb-1">{{ $resource->role }}</p>
                                    <small>Costo: {{ number_format($resource->pivot->cost, 2) }} €</small>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="alert alert-info">
                            Nessuna risorsa assegnata a questo progetto.
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Aree</h5>
                    <a href="{{ route('areas.create') }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus"></i> Nuova Area
                    </a>
                </div>
                <div class="card-body">
                    @if($project->areas->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Nome</th>
                                        <th>Attività</th>
                                        <th>Progresso</th>
                                        <th>Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($project->areas as $area)
                                        <tr>
                                            <td>{{ $area->name }}</td>
                                            <td>{{ $area->activities->count() }}</td>
                                            <td>
                                                <div class="progress" style="height: 10px;">
                                                    <div class="progress-bar" role="progressbar" style="width: {{ $area->progress_percentage }}%" aria-valuenow="{{ $area->progress_percentage }}" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                                <small>{{ $area->progress_percentage }}%</small>
                                            </td>
                                            <td>
                                                <a href="{{ route('areas.show', $area->id) }}" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info">
                            Nessuna area definita per questo progetto. <a href="{{ route('areas.create') }}">Crea una nuova area</a>.
                        </div>
                    @endif
                </div>
            </div>
            
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Attività</h5>
                    <a href="{{ route('activities.create') }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus"></i> Nuova Attività
                    </a>
                </div>
                <div class="card-body">
                    @if($project->activities->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Nome</th>
                                        <th>Area</th>
                                        <th>Risorsa</th>
                                        <th>Stato</th>
                                        <th>Minuti Stimati</th>
                                        <th>Minuti Effettivi</th>
                                        <th>Costo</th>
                                        <th>Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($project->activities as $activity)
                                        <tr>
                                            <td>{{ $activity->name }}</td>
                                            <td>{{ $activity->area ? $activity->area->name : '-' }}</td>
                                            <td>{{ $activity->resource->name }}</td>
                                            <td>
                                                @if($activity->status == 'pending')
                                                    <span class="badge bg-warning">In attesa</span>
                                                @elseif($activity->status == 'in_progress')
                                                    <span class="badge bg-primary">In corso</span>
                                                @elseif($activity->status == 'completed')
                                                    <span class="badge bg-success">Completata</span>
                                                @endif
                                            </td>
                                            <td>{{ $activity->estimated_minutes }}</td>
                                            <td>{{ $activity->actual_minutes }}</td>
                                            <td>{{ number_format($activity->estimated_cost, 2) }} €</td>
                                            <td>
                                                <a href="{{ route('activities.show', $activity->id) }}" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info">
                            Nessuna attività definita per questo progetto. <a href="{{ route('activities.create') }}">Crea una nuova attività</a>.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection