@extends('layouts.app')

@section('title', 'Dettagli Risorsa')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1>{{ $resource->name }}</h1>
            <p class="text-muted">{{ $resource->role }}</p>
        </div>
        <div class="col-md-6 text-end">
            <a href="{{ route('resources.edit', $resource->id) }}" class="btn btn-warning">
                <i class="fas fa-edit"></i> Modifica
            </a>
            <a href="{{ route('resources.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Indietro
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-5">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Informazioni Risorsa</h5>
                </div>
                <div class="card-body">
                    <p><strong>Ruolo:</strong> {{ $resource->role }}</p>
                    <p><strong>Email:</strong> {{ $resource->email ?? 'Non specificata' }}</p>
                    <p><strong>Telefono:</strong> {{ $resource->phone ?? 'Non specificato' }}</p>
                    <p><strong>Compenso Mensile:</strong> {{ number_format($resource->monthly_compensation, 2) }} €</p>
                    <p><strong>Giorni Lavorativi/Anno:</strong> {{ $resource->working_days_year }}</p>
                    <p><strong>Ore Lavorative/Giorno:</strong> {{ $resource->working_hours_day }}</p>
                    <p><strong>Ore Extra/Giorno:</strong> {{ $resource->extra_hours_day ?? '0' }}</p>
                    <p><strong>Prezzo di Costo:</strong> {{ number_format($resource->cost_price, 2) }} €/h</p>
                    <p><strong>Prezzo di Vendita:</strong> {{ number_format($resource->selling_price, 2) }} €/h</p>
                    <p><strong>Stato:</strong> 
                        <span class="badge {{ $resource->is_active ? 'bg-success' : 'bg-danger' }}">
                            {{ $resource->is_active ? 'Attivo' : 'Inattivo' }}
                        </span>
                    </p>
                </div>
            </div>
        </div>
        
        <div class="col-md-7">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Disponibilità Ore</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th></th>
                                    <th>Ore Standard</th>
                                    <th>Ore Extra</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Disponibili/Anno</strong></td>
                                    <td>{{ number_format($resource->standard_hours_per_year, 2) }}</td>
                                    <td>{{ number_format($resource->extra_hours_per_year, 2) }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Allocate (stimate)</strong></td>
                                    <td>{{ number_format($resource->total_standard_estimated_hours, 2) }}</td>
                                    <td>{{ number_format($resource->total_extra_estimated_hours, 2) }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Allocate (effettive)</strong></td>
                                    <td>{{ number_format($resource->total_standard_actual_hours, 2) }}</td>
                                    <td>{{ number_format($resource->total_extra_actual_hours, 2) }}</td>
                                </tr>
                                <tr class="table-success">
                                    <td><strong>Rimanenti (stimate)</strong></td>
                                    <td>{{ number_format($resource->remaining_standard_estimated_hours, 2) }}</td>
                                    <td>{{ number_format($resource->remaining_extra_estimated_hours, 2) }}</td>
                                </tr>
                                <tr class="table-primary">
                                    <td><strong>Rimanenti (effettive)</strong></td>
                                    <td>{{ number_format($resource->remaining_standard_actual_hours, 2) }}</td>
                                    <td>{{ number_format($resource->remaining_extra_actual_hours, 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-3">
                        <h6>Utilizzo Ore Standard</h6>
                        <div class="progress mb-3" style="height: 20px;">
                            @php
                                $standardEstimatedUsagePercentage = min(100, $resource->standard_hours_per_year > 0 ? ($resource->total_standard_estimated_hours / $resource->standard_hours_per_year) * 100 : 0);
                            @endphp
                            <div class="progress-bar bg-success" role="progressbar" 
                                style="width: {{ $standardEstimatedUsagePercentage }}%" 
                                aria-valuenow="{{ $standardEstimatedUsagePercentage }}" 
                                aria-valuemin="0" 
                                aria-valuemax="100">
                                {{ number_format($standardEstimatedUsagePercentage, 1) }}% (Stimate)
                            </div>
                        </div>
                        
                        <div class="progress mb-4" style="height: 20px;">
                            @php
                                $standardActualUsagePercentage = min(100, $resource->standard_hours_per_year > 0 ? ($resource->total_standard_actual_hours / $resource->standard_hours_per_year) * 100 : 0);
                            @endphp
                            <div class="progress-bar bg-primary" role="progressbar" 
                                style="width: {{ $standardActualUsagePercentage }}%" 
                                aria-valuenow="{{ $standardActualUsagePercentage }}" 
                                aria-valuemin="0" 
                                aria-valuemax="100">
                                {{ number_format($standardActualUsagePercentage, 1) }}% (Effettive)
                            </div>
                        </div>
                        
                        <h6>Utilizzo Ore Extra</h6>
                        <div class="progress mb-3" style="height: 20px;">
                            @php
                                $extraEstimatedUsagePercentage = min(100, $resource->extra_hours_per_year > 0 ? ($resource->total_extra_estimated_hours / $resource->extra_hours_per_year) * 100 : 0);
                            @endphp
                            <div class="progress-bar bg-warning" role="progressbar" 
                                style="width: {{ $extraEstimatedUsagePercentage }}%" 
                                aria-valuenow="{{ $extraEstimatedUsagePercentage }}" 
                                aria-valuemin="0" 
                                aria-valuemax="100">
                                {{ number_format($extraEstimatedUsagePercentage, 1) }}% (Stimate)
                            </div>
                        </div>
                        
                        <div class="progress" style="height: 20px;">
                            @php
                                $extraActualUsagePercentage = min(100, $resource->extra_hours_per_year > 0 ? ($resource->total_extra_actual_hours / $resource->extra_hours_per_year) * 100 : 0);
                            @endphp
                            <div class="progress-bar bg-danger" role="progressbar" 
                                style="width: {{ $extraActualUsagePercentage }}%" 
                                aria-valuenow="{{ $extraActualUsagePercentage }}" 
                                aria-valuemin="0" 
                                aria-valuemax="100">
                                {{ number_format($extraActualUsagePercentage, 1) }}% (Effettive)
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Progetti Associati</h5>
                </div>
                <div class="card-body">
                    @if($resource->projects->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Nome Progetto</th>
                                        <th>Cliente</th>
                                        <th>Ore Standard</th>
                                        <th>Ore Extra</th>
                                        <th>Stato</th>
                                        <th>Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($resource->projects as $project)
                                        <tr>
                                            <td>{{ $project->name }}</td>
                                            <td>{{ $project->client->name }}</td>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <small>Assegnate: {{ number_format($project->pivot->hours, 2) }}</small>
                                                    <small>Effettive: {{ number_format($project->standard_actual_hours_by_resource[$resource->id] ?? 0, 2) }}</small>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <small>Assegnate: {{ number_format($project->pivot->extra_hours, 2) }}</small>
                                                    <small>Effettive: {{ number_format($project->extra_actual_hours_by_resource[$resource->id] ?? 0, 2) }}</small>
                                                </div>
                                            </td>
                                            <td>
                                                @if($project->status == 'pending')
                                                    <span class="badge bg-warning">In attesa</span>
                                                @elseif($project->status == 'in_progress')
                                                    <span class="badge bg-primary">In corso</span>
                                                @elseif($project->status == 'completed')
                                                    <span class="badge bg-success">Completato</span>
                                                @elseif($project->status == 'on_hold')
                                                    <span class="badge bg-secondary">In pausa</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('projects.show', $project->id) }}" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i> Visualizza
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info">
                            Questa risorsa non è attualmente assegnata a nessun progetto.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection