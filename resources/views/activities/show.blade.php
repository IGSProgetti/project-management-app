@extends('layouts.app')

@section('title', 'Dettaglio Attività')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12 d-flex justify-content-between align-items-center">
            <h1>Dettaglio Attività: {{ $activity->name }}</h1>
            <div>
                <a href="{{ route('activities.edit', $activity->id) }}" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Modifica
                </a>
                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                    <i class="fas fa-trash"></i> Elimina
                </button>
            </div>
        </div>
    </div>
    
    <!-- Aggiornamento sezione risorse per la vista show.blade.php -->
    <div class="card mb-4">
        <div class="card-header">
            <h5>Informazioni Attività</h5>
        </div>
        <div class="card-body">
            <p>
                <strong>Stato:</strong>
                @if($activity->status == 'pending')
                    <span class="badge bg-warning">In attesa</span>
                @elseif($activity->status == 'in_progress')
                    <span class="badge bg-primary">In corso</span>
                @elseif($activity->status == 'completed')
                    <span class="badge bg-success">Completata</span>
                @endif
            </p>
            
            <p>
                <strong>Risorse Assegnate:</strong>
                @if($activity->has_multiple_resources)
                    <ul class="list-group mb-3">
                        @foreach($activity->resources as $resource)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                {{ $resource->name }} ({{ $resource->role }})
                                <div>
                                    <span class="badge bg-primary rounded-pill">
                                        {{ $resource->pivot->estimated_minutes }} min. stimati
                                    </span>
                                    <span class="badge bg-secondary rounded-pill">
                                        {{ $resource->pivot->actual_minutes }} min. effettivi
                                    </span>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    {{ $activity->resource->name }} ({{ $activity->resource->role }})
                @endif
            </p>
            
            <p>
                <strong>Tipo di Ore:</strong>
                @if($activity->hours_type == 'standard')
                    <span class="badge bg-primary">Standard</span>
                @else
                    <span class="badge bg-warning">Extra</span>
                @endif
            </p>
            <p><strong>Minuti Preventivati:</strong> {{ $activity->estimated_minutes }}</p>
            <p><strong>Minuti Effettivi:</strong> {{ $activity->actual_minutes ?? 0 }}</p>
            <p><strong>Costo Preventivato:</strong> {{ number_format($activity->estimated_cost, 2) }} €</p>
            <p><strong>Costo Effettivo:</strong> {{ number_format($activity->actual_cost, 2) }} €</p>
            <p><strong>Data Scadenza:</strong> {{ $activity->due_date ? $activity->due_date->format('d/m/Y') : 'Non specificata' }}</p>
            
            @if($activity->is_overdue)
                <div class="alert alert-danger mt-3">
                    <i class="fas fa-exclamation-circle"></i> Attività scaduta!
                </div>
            @endif
            
            @if($activity->is_over_estimated)
                <div class="alert alert-warning mt-3">
                    <i class="fas fa-exclamation-triangle"></i> I minuti effettivi superano quelli preventivati!
                </div>
            @endif

            <h6 class="mt-4">Progresso</h6>
            <div class="progress mb-2" style="height: 15px;">
                <div class="progress-bar {{ $activity->progress_percentage > 90 ? 'bg-success' : 'bg-primary' }}" role="progressbar" style="width: {{ $activity->progress_percentage }}%" aria-valuenow="{{ $activity->progress_percentage }}" aria-valuemin="0" aria-valuemax="100">{{ $activity->progress_percentage }}%</div>
            </div>
        </div>
    </div>

    @if($activity->has_multiple_resources)
    <!-- Sezione risorse multiple -->
    <div class="card mb-4">
        <div class="card-header">
            <h5>Disponibilità Risorse</h5>
        </div>
        <div class="card-body">
            <div class="accordion" id="resourcesAccordion">
                @foreach($activity->resources as $index => $resource)
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading{{ $resource->id }}">
                            <button class="accordion-button {{ $index > 0 ? 'collapsed' : '' }}" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $resource->id }}" aria-expanded="{{ $index === 0 ? 'true' : 'false' }}" aria-controls="collapse{{ $resource->id }}">
                                {{ $resource->name }} ({{ $resource->role }})
                            </button>
                        </h2>
                        <div id="collapse{{ $resource->id }}" class="accordion-collapse collapse {{ $index === 0 ? 'show' : '' }}" aria-labelledby="heading{{ $resource->id }}" data-bs-parent="#resourcesAccordion">
                            <div class="accordion-body">
                                @php
                                    $hoursType = $resource->pivot->hours_type;
                                    
                                    // Ottieni le ore standard e extra
                                    $standardHoursPerYear = $resource->standard_hours_per_year;
                                    $extraHoursPerYear = $resource->extra_hours_per_year;
                                    
                                    // Ottieni le ore utilizzate
                                    $standardHoursUsed = $resource->total_standard_estimated_hours;
                                    $extraHoursUsed = $resource->total_extra_estimated_hours;
                                    
                                    // Calcola le percentuali di utilizzo
                                    $standardUsagePercentage = min(100, $standardHoursPerYear > 0 ? ($standardHoursUsed / $standardHoursPerYear) * 100 : 0);
                                    $extraUsagePercentage = min(100, $extraHoursPerYear > 0 ? ($extraHoursUsed / $extraHoursPerYear) * 100 : 0);
                                @endphp
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Ore Standard</h6>
                                        <p><strong>Ore Standard/Anno:</strong> {{ number_format($standardHoursPerYear, 2) }}</p>
                                        <p><strong>Ore Standard Utilizzate:</strong> {{ number_format($standardHoursUsed, 2) }}</p>
                                        <p><strong>Ore Standard Residue:</strong> {{ number_format(max(0, $standardHoursPerYear - $standardHoursUsed), 2) }}</p>
                                        
                                        <div class="progress mb-3" style="height: 10px;">
                                            <div class="progress-bar {{ $standardUsagePercentage > 90 ? 'bg-danger' : 'bg-success' }}" 
                                                role="progressbar" 
                                                style="width: {{ $standardUsagePercentage }}%" 
                                                aria-valuenow="{{ $standardUsagePercentage }}" 
                                                aria-valuemin="0" 
                                                aria-valuemax="100">
                                                {{ number_format($standardUsagePercentage, 1) }}%
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Ore Extra</h6>
                                        <p><strong>Ore Extra/Anno:</strong> {{ number_format($extraHoursPerYear, 2) }}</p>
                                        <p><strong>Ore Extra Utilizzate:</strong> {{ number_format($extraHoursUsed, 2) }}</p>
                                        <p><strong>Ore Extra Residue:</strong> {{ number_format(max(0, $extraHoursPerYear - $extraHoursUsed), 2) }}</p>
                                        
                                        <div class="progress mb-3" style="height: 10px;">
                                            <div class="progress-bar {{ $extraUsagePercentage > 90 ? 'bg-danger' : 'bg-warning' }}" 
                                                role="progressbar" 
                                                style="width: {{ $extraUsagePercentage }}%" 
                                                aria-valuenow="{{ $extraUsagePercentage }}" 
                                                aria-valuemin="0" 
                                                aria-valuemax="100">
                                                {{ number_format($extraUsagePercentage, 1) }}%
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Dettaglio contribuzione alla attività -->
                                <div class="mt-4">
                                    <h6>Contribuzione a questa attività</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>Minuti stimati:</strong> {{ $resource->pivot->estimated_minutes }}</p>
                                            <p><strong>% su attività:</strong> 
                                                {{ number_format($activity->estimated_minutes > 0 ? ($resource->pivot->estimated_minutes / $activity->estimated_minutes) * 100 : 0, 1) }}%
                                            </p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>Minuti effettivi:</strong> {{ $resource->pivot->actual_minutes }}</p>
                                            <p><strong>Costo stimato:</strong> {{ number_format($resource->pivot->estimated_cost, 2) }} €</p>
                                        </div>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-info" role="progressbar" 
                                            style="width: {{ number_format($activity->estimated_minutes > 0 ? ($resource->pivot->estimated_minutes / $activity->estimated_minutes) * 100 : 0, 1) }}%" 
                                            aria-valuenow="{{ number_format($activity->estimated_minutes > 0 ? ($resource->pivot->estimated_minutes / $activity->estimated_minutes) * 100 : 0, 1) }}" 
                                            aria-valuemin="0" 
                                            aria-valuemax="100"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @else
    <!-- Originale per singola risorsa -->
    <div class="card mb-4">
        <div class="card-header">
            <h5>Disponibilità Risorsa</h5>
        </div>
        <div class="card-body">
            <h6>{{ $activity->resource->name }}</h6>
            
            @if($activity->hours_type == 'standard')
                <p><strong>Ore Standard/Anno:</strong> {{ number_format($activity->resource->standard_hours_per_year, 2) }}</p>
                <p><strong>Ore Standard Utilizzate:</strong> {{ number_format($activity->resource->total_standard_estimated_hours, 2) }}</p>
                <p><strong>Ore Standard Residue:</strong> {{ number_format($activity->resource->remaining_standard_estimated_hours, 2) }}</p>
                
                <div class="progress mb-3" style="height: 10px;">
                    @php
                        $standardUsagePercentage = min(100, $activity->resource->standard_hours_per_year > 0 ? 
                            ($activity->resource->total_standard_estimated_hours / $activity->resource->standard_hours_per_year) * 100 : 0);
                    @endphp
                    <div class="progress-bar {{ $standardUsagePercentage > 90 ? 'bg-danger' : 'bg-success' }}" 
                        role="progressbar" 
                        style="width: {{ $standardUsagePercentage }}%" 
                        aria-valuenow="{{ $standardUsagePercentage }}" 
                        aria-valuemin="0" 
                        aria-valuemax="100">
                        {{ number_format($standardUsagePercentage, 1) }}%
                    </div>
                </div>
            @else
                <p><strong>Ore Extra/Anno:</strong> {{ number_format($activity->resource->extra_hours_per_year, 2) }}</p>
                <p><strong>Ore Extra Utilizzate:</strong> {{ number_format($activity->resource->total_extra_estimated_hours, 2) }}</p>
                <p><strong>Ore Extra Residue:</strong> {{ number_format($activity->resource->remaining_extra_estimated_hours, 2) }}</p>
                
                <div class="progress mb-3" style="height: 10px;">
                    @php
                        $extraUsagePercentage = min(100, $activity->resource->extra_hours_per_year > 0 ? 
                            ($activity->resource->total_extra_estimated_hours / $activity->resource->extra_hours_per_year) * 100 : 0);
                    @endphp
                    <div class="progress-bar {{ $extraUsagePercentage > 90 ? 'bg-danger' : 'bg-warning' }}" 
                        role="progressbar" 
                        style="width: {{ $extraUsagePercentage }}%" 
                        aria-valuenow="{{ $extraUsagePercentage }}" 
                        aria-valuemin="0" 
                        aria-valuemax="100">
                        {{ number_format($extraUsagePercentage, 1) }}%
                    </div>
                </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Modal di conferma eliminazione -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Conferma eliminazione</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Sei sicuro di voler eliminare questa attività? Questa azione non può essere annullata.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <form action="{{ route('activities.destroy', $activity->id) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Elimina</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection