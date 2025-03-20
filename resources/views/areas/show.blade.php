@extends('layouts.app')

@section('title', 'Dettagli Area')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1>{{ $area->name }}</h1>
            <p class="text-muted">Progetto: {{ $area->project->name }}</p>
        </div>
        <div class="col-md-6 text-end">
            <a href="{{ route('activities.create') }}" class="btn btn-success">
                <i class="fas fa-plus"></i> Nuova Attività
            </a>
            <a href="{{ route('areas.edit', $area->id) }}" class="btn btn-warning">
                <i class="fas fa-edit"></i> Modifica
            </a>
            <a href="{{ route('areas.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Indietro
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Informazioni Area</h5>
                </div>
                <div class="card-body">
                    <p><strong>Descrizione:</strong> {{ $area->description ?? 'Nessuna descrizione' }}</p>
                    <p><strong>Progetto:</strong> <a href="{{ route('projects.show', $area->project_id) }}">{{ $area->project->name }}</a></p>
                    <p><strong>Cliente:</strong> {{ $area->project->client->name }}</p>
                    <p><strong>Numero di attività:</strong> {{ $area->activities->count() }}</p>
                    <p><strong>Costo stimato:</strong> {{ number_format($area->total_estimated_cost, 2) }} €</p>
                    <p><strong>Costo effettivo:</strong> {{ number_format($area->total_actual_cost, 2) }} €</p>
                    
                    <h6 class="mt-3">Progresso</h6>
                    <div class="progress mb-2" style="height: 15px;">
                        <div class="progress-bar" role="progressbar" style="width: {{ $area->progress_percentage }}%" aria-valuenow="{{ $area->progress_percentage }}" aria-valuemin="0" aria-valuemax="100">{{ $area->progress_percentage }}%</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Attività</h5>
                    <a href="{{ route('activities.create') }}?area_id={{ $area->id }}&project_id={{ $area->project_id }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus"></i> Nuova Attività
                    </a>
                </div>
                <div class="card-body">
                    @if($area->activities->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Nome</th>
                                        <th>Risorsa</th>
                                        <th>Stato</th>
                                        <th>Minuti Stimati</th>
                                        <th>Minuti Effettivi</th>
                                        <th>Costo Stimato</th>
                                        <th>Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($area->activities as $activity)
                                        <tr>
                                            <td>{{ $activity->name }}</td>
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
                            Nessuna attività definita per questa area. <a href="{{ route('activities.create') }}?area_id={{ $area->id }}&project_id={{ $area->project_id }}">Crea una nuova attività</a>.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection