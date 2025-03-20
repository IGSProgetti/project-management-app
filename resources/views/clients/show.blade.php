@extends('layouts.app')

@section('title', 'Dettagli Cliente')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1>{{ $client->name }}</h1>
        </div>
        <div class="col-md-6 text-end">
            <a href="{{ route('clients.edit', $client->id) }}" class="btn btn-warning">
                <i class="fas fa-edit"></i> Modifica
            </a>
            <a href="{{ route('clients.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Indietro
            </a>
        </div>
    </div>

    <!-- Riassunto Budget e Utilizzo -->
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Riassunto Budget</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="card h-100 border-primary">
                                <div class="card-body text-center">
                                    <h5 class="card-title text-primary">Budget Totale</h5>
                                    <h3>{{ number_format($client->budget, 2) }} €</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="card h-100 {{ $client->budget_usage_percentage > 90 ? 'border-danger' : 'border-warning' }}">
                                <div class="card-body text-center">
                                    <h5 class="card-title {{ $client->budget_usage_percentage > 90 ? 'text-danger' : 'text-warning' }}">Budget Utilizzato</h5>
                                    <h3>{{ number_format($client->total_budget_used, 2) }} €</h3>
                                    <p>{{ $client->budget_usage_percentage }}% del totale</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="card h-100 border-success">
                                <div class="card-body text-center">
                                    <h5 class="card-title text-success">Budget Rimanente</h5>
                                    <h3>{{ number_format($client->remaining_budget, 2) }} €</h3>
                                    <p>{{ 100 - $client->budget_usage_percentage }}% del totale</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="card h-100 border-info">
                                <div class="card-body text-center">
                                    <h5 class="card-title text-info">Progetti Attivi</h5>
                                    <h3>{{ $client->projects->count() }}</h3>
                                    <p>{{ $client->projects->where('status', 'in_progress')->count() }} in corso</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="progress mt-3" style="height: 25px;">
                        <div class="progress-bar {{ $client->budget_usage_percentage > 90 ? 'bg-danger' : 'bg-success' }}" 
                             role="progressbar" 
                             style="width: {{ $client->budget_usage_percentage }}%" 
                             aria-valuenow="{{ $client->budget_usage_percentage }}" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                            {{ $client->budget_usage_percentage }}% Utilizzato
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Riassunto Ore Utilizzo -->
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Riassunto Ore</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Ore Totali -->
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="card h-100 border-info">
                                <div class="card-body text-center">
                                    <h5 class="card-title text-info">Ore Totali</h5>
                                    <div class="row">
                                        <div class="col-6 border-end">
                                            <p class="mb-0 text-muted">Stimate</p>
                                            <h3>{{ number_format($hoursStats['totalEstimatedHours'], 1) }}</h3>
                                        </div>
                                        <div class="col-6">
                                            <p class="mb-0 text-muted">Effettive</p>
                                            <h3>{{ number_format($hoursStats['totalActualHours'], 1) }}</h3>
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <p class="text-{{ $hoursStats['totalEfficiency'] > 100 ? 'danger' : 'success' }} mb-0">
                                            Efficienza: {{ $hoursStats['totalEfficiency'] }}%
                                        </p>
                                    </div>
                                </div>
                                <div class="card-footer bg-transparent">
                                    <div class="progress" style="height: 10px;">
                                        <div class="progress-bar bg-info" style="width: {{ $hoursStats['totalEfficiency'] }}%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Ore Standard -->
                        <div class="col-md-4 col-sm-6 mb-3">
                            <div class="card h-100 border-primary">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="card-title mb-0">Ore Standard</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-6">
                                            <p class="mb-0 text-muted">Stimate</p>
                                            <h3>{{ number_format($hoursStats['standardEstimatedHours'], 1) }}</h3>
                                            <small class="text-muted">{{ $hoursStats['standardEstimatedPercentage'] }}% del totale stimato</small>
                                        </div>
                                        <div class="col-6">
                                            <p class="mb-0 text-muted">Effettive</p>
                                            <h3>{{ number_format($hoursStats['standardActualHours'], 1) }}</h3>
                                            <small class="text-muted">{{ $hoursStats['standardActualPercentage'] }}% del totale effettivo</small>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <p class="text-{{ $hoursStats['standardEfficiency'] > 100 ? 'danger' : 'success' }} mb-0">
                                            Efficienza: {{ $hoursStats['standardEfficiency'] }}%
                                        </p>
                                    </div>
                                </div>
                                <div class="card-footer bg-transparent">
                                    <div class="progress" style="height: 10px;">
                                        <div class="progress-bar bg-primary" style="width: {{ $hoursStats['standardEfficiency'] }}%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Ore Extra -->
                        <div class="col-md-4 col-sm-6 mb-3">
                            <div class="card h-100 border-warning">
                                <div class="card-header bg-warning text-dark">
                                    <h5 class="card-title mb-0">Ore Extra</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-6">
                                            <p class="mb-0 text-muted">Stimate</p>
                                            <h3>{{ number_format($hoursStats['extraEstimatedHours'], 1) }}</h3>
                                            <small class="text-muted">{{ $hoursStats['extraEstimatedPercentage'] }}% del totale stimato</small>
                                        </div>
                                        <div class="col-6">
                                            <p class="mb-0 text-muted">Effettive</p>
                                            <h3>{{ number_format($hoursStats['extraActualHours'], 1) }}</h3>
                                            <small class="text-muted">{{ $hoursStats['extraActualPercentage'] }}% del totale effettivo</small>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <p class="text-{{ $hoursStats['extraEfficiency'] > 100 ? 'danger' : 'success' }} mb-0">
                                            Efficienza: {{ $hoursStats['extraEfficiency'] }}%
                                        </p>
                                    </div>
                                </div>
                                <div class="card-footer bg-transparent">
                                    <div class="progress" style="height: 10px;">
                                        <div class="progress-bar bg-warning" style="width: {{ $hoursStats['extraEfficiency'] }}%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Grafico comparativo -->
                        <div class="col-md-1 d-md-block d-none mb-3">
                            <div class="h-100 d-flex flex-column justify-content-center">
                                <div class="position-relative" style="height: 200px;">
                                    <div class="progress flex-column justify-content-end" style="height: 100%; transform: rotate(180deg);">
                                        <div class="progress-bar bg-primary" role="progressbar" style="width: 100%; height: {{ $hoursStats['standardActualPercentage'] }}%"></div>
                                        <div class="progress-bar bg-warning" role="progressbar" style="width: 100%; height: {{ $hoursStats['extraActualPercentage'] }}%"></div>
                                    </div>
                                    <div class="position-absolute top-0 w-100 text-center">
                                        <span class="badge bg-primary">Standard</span>
                                    </div>
                                    <div class="position-absolute bottom-0 w-100 text-center">
                                        <span class="badge bg-warning">Extra</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Barre di progresso comparativa globale -->
                    <div class="mt-4">
                        <h6>Ripartizione Ore Stimate: {{ number_format($hoursStats['totalEstimatedHours'], 1) }} ore</h6>
                        <div class="progress mb-3" style="height: 25px;">
                            <div class="progress-bar bg-primary" role="progressbar" 
                                 style="width: {{ $hoursStats['standardEstimatedPercentage'] }}%" 
                                 aria-valuenow="{{ $hoursStats['standardEstimatedPercentage'] }}" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                                {{ number_format($hoursStats['standardEstimatedHours'], 1) }} ore Standard ({{ $hoursStats['standardEstimatedPercentage'] }}%)
                            </div>
                            <div class="progress-bar bg-warning" role="progressbar" 
                                 style="width: {{ $hoursStats['extraEstimatedPercentage'] }}%" 
                                 aria-valuenow="{{ $hoursStats['extraEstimatedPercentage'] }}" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                                {{ number_format($hoursStats['extraEstimatedHours'], 1) }} ore Extra ({{ $hoursStats['extraEstimatedPercentage'] }}%)
                            </div>
                        </div>
                        
                        <h6>Ripartizione Ore Effettive: {{ number_format($hoursStats['totalActualHours'], 1) }} ore</h6>
                        <div class="progress" style="height: 25px;">
                            <div class="progress-bar bg-primary" role="progressbar" 
                                 style="width: {{ $hoursStats['standardActualPercentage'] }}%" 
                                 aria-valuenow="{{ $hoursStats['standardActualPercentage'] }}" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                                {{ number_format($hoursStats['standardActualHours'], 1) }} ore Standard ({{ $hoursStats['standardActualPercentage'] }}%)
                            </div>
                            <div class="progress-bar bg-warning" role="progressbar" 
                                 style="width: {{ $hoursStats['extraActualPercentage'] }}%" 
                                 aria-valuenow="{{ $hoursStats['extraActualPercentage'] }}" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                                {{ number_format($hoursStats['extraActualHours'], 1) }} ore Extra ({{ $hoursStats['extraActualPercentage'] }}%)
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtri per progetti -->
    <div class="card mb-4">
        <div class="card-header">
            <h5>Filtri Progetti</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-2">
                    <label for="hoursTypeFilter">Tipo Ore</label>
                    <select id="hoursTypeFilter" class="form-select">
                        <option value="all">Tutti i tipi</option>
                        <option value="standard">Ore Standard</option>
                        <option value="extra">Ore Extra</option>
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <label for="statusFilter">Stato</label>
                    <select id="statusFilter" class="form-select">
                        <option value="all">Tutti gli stati</option>
                        <option value="pending">In attesa</option>
                        <option value="in_progress">In corso</option>
                        <option value="completed">Completato</option>
                        <option value="on_hold">In pausa</option>
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <label for="resourceFilter">Risorsa</label>
                    <select id="resourceFilter" class="form-select">
                        <option value="all">Tutte le risorse</option>
                        @php
                            $resources = collect();
                            foreach($client->projects as $project) {
                                foreach($project->resources as $resource) {
                                    $resources->put($resource->id, $resource);
                                }
                            }
                        @endphp
                        @foreach($resources as $resource)
                            <option value="{{ $resource->id }}">{{ $resource->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <label for="projectNameFilter">Nome Progetto</label>
                    <input type="text" id="projectNameFilter" class="form-control" placeholder="Cerca...">
                </div>
            </div>
        </div>
    </div>

    <!-- Tab per progetti con ore standard/extra -->
    <ul class="nav nav-tabs mb-3" id="projectsTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="all-projects-tab" data-bs-toggle="tab" data-bs-target="#all-projects" type="button" role="tab" aria-controls="all-projects" aria-selected="true">
                Tutti i Progetti ({{ $client->projects->count() }})
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="standard-projects-tab" data-bs-toggle="tab" data-bs-target="#standard-projects" type="button" role="tab" aria-controls="standard-projects" aria-selected="false">
                Progetti con Ore Standard ({{ $client->projects->filter(function($p) { return $p->resources->where('pivot.hours_type', 'standard')->count() > 0; })->count() }})
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="extra-projects-tab" data-bs-toggle="tab" data-bs-target="#extra-projects" type="button" role="tab" aria-controls="extra-projects" aria-selected="false">
                Progetti con Ore Extra ({{ $client->projects->filter(function($p) { return $p->resources->where('pivot.hours_type', 'extra')->count() > 0; })->count() }})
            </button>
        </li>
    </ul>

    <div class="tab-content" id="projectsTabContent">
        <!-- Tab Tutti i Progetti -->
        <div class="tab-pane fade show active" id="all-projects" role="tabpanel" aria-labelledby="all-projects-tab">
            <div class="table-responsive">
                <table class="table table-striped project-table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Stato</th>
                            <th>Budget Totale</th>
                            <th>Budget Utilizzato</th>
                            <th>Budget Rimanente</th>
                            <th>Risorse</th>
                            <th>Progresso</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($client->projects as $project)
                            <tr 
                                data-hours-types="{{ $project->resources->pluck('pivot.hours_type')->unique()->implode(',') }}"
                                data-status="{{ $project->status }}"
                                data-resources="{{ $project->resources->pluck('id')->implode(',') }}"
                                data-name="{{ strtolower($project->name) }}"
                            >
                                <td>{{ $project->name }}</td>
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
                                <td>{{ number_format($project->total_cost, 2) }} €</td>
                                <td>
                                    @php
                                        $usedBudget = $project->activities->sum('actual_cost');
                                        $usagePercentage = $project->total_cost > 0 ? min(100, round(($usedBudget / $project->total_cost) * 100)) : 0;
                                    @endphp
                                    {{ number_format($usedBudget, 2) }} € ({{ $usagePercentage }}%)
                                </td>
                                <td>{{ number_format($project->total_cost - $usedBudget, 2) }} €</td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach($project->resources->groupBy('pivot.hours_type') as $hoursType => $resources)
                                            @foreach($resources as $resource)
                                                <span class="badge {{ $hoursType == 'standard' ? 'bg-primary' : 'bg-warning' }} me-1" title="{{ $resource->name }} ({{ $resource->pivot->hours }} ore {{ $hoursType == 'standard' ? 'standard' : 'extra' }})">
                                                    {{ $resource->name }}
                                                </span>
                                            @endforeach
                                        @endforeach
                                    </div>
                                </td>
                                <td>
                                    <div class="progress" style="height: 10px;">
                                        <div class="progress-bar" role="progressbar" style="width: {{ $project->progress_percentage }}%" aria-valuenow="{{ $project->progress_percentage }}" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <small>{{ $project->progress_percentage }}%</small>
                                </td>
                                <td>
                                    <a href="{{ route('projects.show', $project->id) }}" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tab Progetti con Ore Standard -->
        <div class="tab-pane fade" id="standard-projects" role="tabpanel" aria-labelledby="standard-projects-tab">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Stato</th>
                            <th>Risorsa</th>
                            <th>Ore Standard Allocate</th>
                            <th>Ore Standard Utilizzate</th>
                            <th>Tariffa Oraria</th>
                            <th>Progresso</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($client->projects as $project)
                            @foreach($project->resources->where('pivot.hours_type', 'standard') as $resource)
                                <tr 
                                    data-hours-types="standard"
                                    data-status="{{ $project->status }}"
                                    data-resources="{{ $resource->id }}"
                                    data-name="{{ strtolower($project->name) }}"
                                >
                                    <td>{{ $project->name }}</td>
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
                                    <td>{{ $resource->name }}</td>
                                    <td>{{ $resource->pivot->hours }} ore</td>
                                    <td>
                                        @php
                                            $usedStandardHours = isset($project->standard_actual_hours_by_resource[$resource->id]) 
                                                ? $project->standard_actual_hours_by_resource[$resource->id] 
                                                : 0;
                                            $standardPercentage = $resource->pivot->hours > 0 
                                                ? min(100, round(($usedStandardHours / $resource->pivot->hours) * 100)) 
                                                : 0;
                                        @endphp
                                        {{ number_format($usedStandardHours, 2) }} ore ({{ $standardPercentage }}%)
                                        
                                        <div class="progress mt-1" style="height: 5px;">
                                            <div class="progress-bar {{ $standardPercentage > 90 ? 'bg-danger' : 'bg-success' }}" style="width: {{ $standardPercentage }}%"></div>
                                        </div>
                                    </td>
                                    <td>{{ number_format($resource->pivot->adjusted_rate, 2) }} €/h</td>
                                    <td>
                                        <div class="progress" style="height: 10px;">
                                            <div class="progress-bar" role="progressbar" style="width: {{ $project->progress_percentage }}%" aria-valuenow="{{ $project->progress_percentage }}" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <small>{{ $project->progress_percentage }}%</small>
                                    </td>
                                    <td>
                                        <a href="{{ route('projects.show', $project->id) }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tab Progetti con Ore Extra -->
        <div class="tab-pane fade" id="extra-projects" role="tabpanel" aria-labelledby="extra-projects-tab">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Stato</th>
                            <th>Risorsa</th>
                            <th>Ore Extra Allocate</th>
                            <th>Ore Extra Utilizzate</th>
                            <th>Tariffa Oraria</th>
                            <th>Progresso</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($client->projects as $project)
                            @foreach($project->resources->where('pivot.hours_type', 'extra') as $resource)
                                <tr 
                                    data-hours-types="extra"
                                    data-status="{{ $project->status }}"
                                    data-resources="{{ $resource->id }}"
                                    data-name="{{ strtolower($project->name) }}"
                                >
                                    <td>{{ $project->name }}</td>
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
                                    <td>{{ $resource->name }}</td>
                                    <td>{{ $resource->pivot->hours }} ore</td>
                                    <td>
                                        @php
                                            $usedExtraHours = isset($project->extra_actual_hours_by_resource[$resource->id]) 
                                                ? $project->extra_actual_hours_by_resource[$resource->id] 
                                                : 0;
                                            $extraPercentage = $resource->pivot->hours > 0 
                                                ? min(100, round(($usedExtraHours / $resource->pivot->hours) * 100)) 
                                                : 0;
                                        @endphp
                                        {{ number_format($usedExtraHours, 2) }} ore ({{ $extraPercentage }}%)
                                        
                                        <div class="progress mt-1" style="height: 5px;">
                                            <div class="progress-bar {{ $extraPercentage > 90 ? 'bg-danger' : 'bg-warning' }}" style="width: {{ $extraPercentage }}%"></div>
                                        </div>
                                    </td>
                                    <td>{{ number_format($resource->pivot->adjusted_rate, 2) }} €/h</td>
                                    <td>
                                        <div class="progress" style="height: 10px;">
                                            <div class="progress-bar" role="progressbar" style="width: {{ $project->progress_percentage }}%" aria-valuenow="{{ $project->progress_percentage }}" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <small>{{ $project->progress_percentage }}%</small>
                                    </td>
                                    <td>
                                        <a href="{{ route('projects.show', $project->id) }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* Stili per le card di riepilogo */
    .card {
        transition: all 0.3s ease;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        border-radius: 8px;
    }
    
    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15@push('styles')
<style>
    /* Stili per le card di riepilogo */
    .card {
        transition: all 0.3s ease;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        border-radius: 8px;
    }
    
    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
    }
    
    .card-header {
        border-top-left-radius: 8px;
        border-top-right-radius: 8px;
        font-weight: 600;
    }
    
    /* Stili per i badge */
    .badge {
        font-weight: 500;
        padding: 0.4em 0.8em;
        border-radius: 4px;
    }
    
    /* Stili per le progress bar */
    .progress {
        border-radius: 6px;
        overflow: hidden;
        height: 10px;
        background-color: #f1f1f1;
        margin-top: 5px;
    }
    
    .progress-bar {
        transition: width 0.5s ease;
    }
    
    /* Stili per le tabelle */
    .table-striped tbody tr:nth-of-type(odd) {
        background-color: rgba(0, 0, 0, 0.02);
    }
    
    .table-striped tbody tr:hover {
        background-color: rgba(0, 0, 0, 0.05);
    }
    
    /* Stili per le tabs */
    #projectsTabs .nav-link {
        font-weight: 500;
        padding: 10px 20px;
        border-radius: 8px 8px 0 0;
    }
    
    #projectsTabs .nav-link.active {
        background-color: #f8f9fa;
        border-bottom-color: transparent;
    }
    
    .tab-content {
        background-color: #f8f9fa;
        border-radius: 0 8px 8px 8px;
        padding: 20px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }
    
    /* Stili per i filtri */
    .form-select, .form-control {
        border-radius: 6px;
        padding: 8px 12px;
        border: 1px solid #ced4da;
    }
    
    .form-select:focus, .form-control:focus {
        border-color: #80bdff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Filtri per tutte le tabelle nei vari tab
        const hoursTypeFilter = document.getElementById('hoursTypeFilter');
        const statusFilter = document.getElementById('statusFilter');
        const resourceFilter = document.getElementById('resourceFilter');
        const nameFilter = document.getElementById('projectNameFilter');
        
        // Seleziona tutte le righe delle tabelle in tutti i tab
        const allTables = document.querySelectorAll('.tab-pane table');
        let allRows = [];
        
        allTables.forEach(table => {
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                allRows.push(row);
            });
        });
        
        // Funzione per applicare i filtri
        function applyFilters() {
            const hoursType = hoursTypeFilter.value;
            const status = statusFilter.value;
            const resource = resourceFilter.value;
            const name = nameFilter.value.toLowerCase().trim();
            
            allRows.forEach(row => {
                const rowHoursTypes = row.dataset.hoursTypes ? row.dataset.hoursTypes.split(',') : [];
                const rowStatus = row.dataset.status || '';
                const rowResources = row.dataset.resources ? row.dataset.resources.split(',') : [];
                const rowName = row.dataset.name || '';
                
                // Applica i filtri
                const hoursMatch = hoursType === 'all' || 
                    (hoursType === 'standard' && rowHoursTypes.includes('standard')) ||
                    (hoursType === 'extra' && rowHoursTypes.includes('extra'));
                
                const statusMatch = status === 'all' || rowStatus === status;
                
                const resourceMatch = resource === 'all' || rowResources.includes(resource);
                
                const nameMatch = name === '' || rowName.includes(name);
                
                // Mostra o nascondi la riga
                if (hoursMatch && statusMatch && resourceMatch && nameMatch) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Controlla se ci sono risultati visibili in ciascuna tab
            allTables.forEach(table => {
                const tabPane = table.closest('.tab-pane');
                let visibleRows = 0;
                const rows = table.querySelectorAll('tbody tr');
                
                rows.forEach(row => {
                    if (row.style.display !== 'none') {
                        visibleRows++;
                    }
                });
                
                // Aggiungi/rimuovi messaggio "nessun risultato"
                let noResultsMsg = tabPane.querySelector('.no-results-message');
                
                if (visibleRows === 0) {
                    if (!noResultsMsg) {
                        noResultsMsg = document.createElement('div');
                        noResultsMsg.className = 'alert alert-info no-results-message';
                        noResultsMsg.textContent = 'Nessun progetto corrisponde ai filtri selezionati.';
                        tabPane.appendChild(noResultsMsg);
                    }
                } else if (noResultsMsg) {
                    noResultsMsg.remove();
                }
            });
        }
        
        // Aggiungi event listener ai filtri
        if (hoursTypeFilter) hoursTypeFilter.addEventListener('change', applyFilters);
        if (statusFilter) statusFilter.addEventListener('change', applyFilters);
        if (resourceFilter) resourceFilter.addEventListener('change', applyFilters);
        if (nameFilter) nameFilter.addEventListener('input', applyFilters);
        
        // Aggiungi pulsante per resettare i filtri
        const filtersContainer = document.querySelector('.card-body .row');
        if (filtersContainer) {
            const resetButton = document.createElement('button');
            resetButton.type = 'button';
            resetButton.className = 'btn btn-outline-secondary mt-3';
            resetButton.innerHTML = '<i class="fas fa-sync-alt"></i> Reset Filtri';
            resetButton.onclick = function() {
                hoursTypeFilter.value = 'all';
                statusFilter.value = 'all';
                resourceFilter.value = 'all';
                nameFilter.value = '';
                applyFilters();
            };
            
            const buttonContainer = document.createElement('div');
            buttonContainer.className = 'col-12 text-end';
            buttonContainer.appendChild(resetButton);
            filtersContainer.appendChild(buttonContainer);
        }
    });
</script>
@endpush